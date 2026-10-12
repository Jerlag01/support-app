<?php

namespace App\Console\Commands;

use App\Support\Updater;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

/**
 * Pulls the latest code from the git remote and brings the installation in line:
 * composer, npm build, migrations and caches. Must run as the owner of the files
 * (www-data); the scheduler runs it when an update is requested in the admin panel.
 */
class UpdateFromGit extends Command
{
    protected $signature = 'app:update
        {--check : Only show whether an update is available}
        {--if-requested : Only run when an update was requested in the admin panel (used by the scheduler)}
        {--allow-root : Run even as root (files pulled will be owned by root)}';

    protected $description = 'Update this installation from its git repository';

    /** @var list<string> */
    protected array $log = [];

    protected string $path;

    /** The PHP binary every step runs with (config updater.php). */
    protected string $php;

    /** @var array<string, string> */
    protected array $environment = [];

    public function handle(Updater $updater): int
    {
        $this->path = $updater->path();
        $request = $updater->pendingRequest();

        if ($this->option('if-requested') && $request === null) {
            return self::SUCCESS;
        }

        if (! $updater->isRepository()) {
            return $this->failWith($updater, $request, 'This installation is not a git repository.');
        }

        if ($this->option('check')) {
            return $this->check($updater);
        }

        if (function_exists('posix_geteuid') && posix_geteuid() === 0 && ! $this->option('allow-root')) {
            $user = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner($updater->path()))['name'] ?? 'www-data') : 'www-data';

            return $this->failWith($updater, $request, "Do not run the update as root; the files must stay owned by {$user}: sudo -u {$user} ".config('updater.php')." artisan {$this->getName()}");
        }

        if (! $php = $updater->php()) {
            return $this->failWith($updater, $request, 'PHP binary "'.config('updater.php').'" was not found. Set UPDATER_PHP in .env to the PHP version the website runs on (e.g. php8.4 or /usr/bin/php8.4).');
        }

        $this->php = $php;
        $this->environment = $updater->environment();

        $lock = Cache::lock('updater.run', 1800);

        if (! $lock->get()) {
            $this->warn('An update is already running.');

            return self::FAILURE;
        }

        // Taken first, so a failing update is not retried every minute.
        $updater->cancelRequest();

        try {
            return $this->runUpdate($updater, $request);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>|null  $request
     */
    protected function runUpdate(Updater $updater, ?array $request): int
    {
        $branch = $updater->branch() ?? throw new RuntimeException('No branch is checked out.');
        $remote = $updater->remote();
        $old = $updater->commit()['hash'];
        $startedAt = now();
        $maintenance = false;
        $new = $old;

        $run = fn (string $status, ?string $error = null): array => [
            'status' => $status,
            'branch' => $branch,
            'from' => $old,
            'to' => $new,
            'requested_by' => $request['user'] ?? 'CLI',
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => in_array($status, ['running'], true) ? null : now()->toIso8601String(),
            'error' => $error,
            'log' => mb_substr(implode("\n", $this->log), -30000),
        ];

        try {
            if ($changes = $updater->localChanges()) {
                throw new RuntimeException("Local changes would be overwritten; commit or discard them first:\n".implode("\n", $changes));
            }

            // Checked before anything changes, so a permission problem never leaves a half-updated site.
            if ($problems = $updater->ownershipProblems()) {
                $owner = $updater->owner();

                throw new RuntimeException("These files are not owned by {$owner}, so the update cannot replace them:\n"
                    .collect($problems)->map(fn (string $file, string $folder): string => "- {$folder}: {$file}")->implode("\n")
                    ."\nFix it as root, then update again: chown -R {$owner}:{$owner} {$updater->path()}");
            }

            $updater->prepareCache();
            $this->log[] = '# PHP '.($updater->phpVersion() ?? '?')." ({$this->php}), started with PHP ".PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'; caches in '.str_replace($this->path.'/', '', $updater->cachePath());

            $updater->saveRun($run('running'));

            $this->step('Fetching', ['git', 'fetch', '--prune', $remote, $branch], 180);
            $new = trim($this->step('Remote revision', ['git', 'rev-parse', "{$remote}/{$branch}"]));

            if ($new === $old) {
                $this->info('Already up to date ('.substr($old, 0, 7).').');
                $updater->saveRun($run('up_to_date'));

                return self::SUCCESS;
            }

            $commits = $this->step('New commits', ['git', 'log', '--oneline', '--no-decorate', "{$old}..{$new}"]);

            $this->line($commits);
            $changed = array_filter(explode("\n", trim($this->step('Changed files', ['git', 'diff', '--name-only', $old, $new], quiet: true))));

            $this->step('Maintenance mode on', [$this->php, 'artisan', 'down', '--retry=30']);
            $maintenance = true;

            $this->step('Pulling', ['git', 'merge', '--ff-only', "{$remote}/{$branch}"]);

            $this->runComposer($updater);
            $this->runNpm($updater, $changed);

            $this->step('Migrating the database', [$this->php, 'artisan', 'migrate', '--force'], 600);

            if (app()->isProduction()) {
                $this->step('Building caches', [$this->php, 'artisan', 'optimize']);
                $this->step('Building Filament caches', [$this->php, 'artisan', 'filament:optimize']);
            } else {
                $this->step('Clearing caches', [$this->php, 'artisan', 'optimize:clear']);
                $this->step('Clearing Filament caches', [$this->php, 'artisan', 'filament:optimize-clear']);
            }

            $this->step('Maintenance mode off', [$this->php, 'artisan', 'up']);
            $maintenance = false;

            $updater->remoteState(fresh: true);
            $updater->saveRun($run('success'));
            $this->info('Updated '.substr($old, 0, 7).' → '.substr($new, 0, 7).'.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if ($maintenance) {
                // Never leave the website down because a step failed.
                Process::path($this->path)->env($this->environment)->run([$this->php, 'artisan', 'up']);
                $this->log[] = '$ artisan up (after failure)';
            }

            $new = $updater->commit()['hash'] ?? $new;

            return $this->failWith($updater, $request, $exception->getMessage(), $run);
        }
    }

    /**
     * Read-only: compares with the remote without fetching, so it is safe as any user.
     */
    protected function check(Updater $updater): int
    {
        $remote = $updater->remoteState(fresh: true);
        $local = $updater->commit();

        $this->line('Branch:  '.($updater->branch() ?? '-'));
        $this->line('PHP:     '.config('updater.php').' → '.($updater->php() ? $updater->php().' ('.($updater->phpVersion() ?? '?').')' : 'NOT FOUND, set UPDATER_PHP'));
        $this->line('Local:   '.$local['short'].' '.$local['subject'].' ('.$local['date']->format('d/m/Y H:i').')');

        if ($remote['error']) {
            $this->error($remote['error']);

            return self::FAILURE;
        }

        $this->line('Remote:  '.substr($remote['hash'], 0, 7));

        foreach ($updater->localChanges() as $change) {
            $this->warn('Local change: '.$change);
        }

        foreach ($updater->ownershipProblems() as $folder => $file) {
            $this->warn("Not owned by {$updater->owner()}: {$folder} ({$file})");
        }

        $updater->updateAvailable()
            ? $this->info('An update is available'.(($url = $updater->compareUrl()) ? ": {$url}" : '.'))
            : $this->info('Up to date.');

        return self::SUCCESS;
    }

    protected function runComposer(Updater $updater): void
    {
        $composer = $updater->executable('composer') ?? throw new RuntimeException('composer was not found; set UPDATER_COMPOSER in .env.');

        // Run composer with the configured PHP, not whichever "php" comes first in PATH.
        $command = str_ends_with($composer, '.bat') ? [$composer] : [$this->php, $composer];

        $this->step('Installing PHP packages', [
            ...$command, 'install', '--no-interaction', '--prefer-dist', '--optimize-autoloader',
            ...(app()->isProduction() ? ['--no-dev'] : []),
        ], 900);

        $this->step('Publishing Livewire assets', [$this->php, 'artisan', 'vendor:publish', '--tag=livewire:assets', '--force']);
    }

    /**
     * @param  list<string>  $changed
     */
    protected function runNpm(Updater $updater, array $changed): void
    {
        $touched = fn (array $patterns): bool => collect($changed)->contains(fn (string $file): bool => collect($patterns)->contains(fn (string $pattern): bool => str_starts_with($file, $pattern)));
        $installNeeded = ! File::isDirectory($this->path.'/node_modules') || $touched(['package.json', 'package-lock.json']);
        $buildNeeded = $installNeeded || ! File::exists($this->path.'/public/build/manifest.json') || $touched(['resources/', 'vite.config', 'package', 'tailwind', 'postcss']);

        if (! $buildNeeded) {
            $this->log[] = '# Frontend unchanged, no npm build needed.';

            return;
        }

        $npm = $updater->executable('npm') ?? throw new RuntimeException('npm was not found; set UPDATER_NPM in .env.');

        if ($installNeeded) {
            $this->step('Installing frontend packages', [$npm, 'ci', '--no-audit', '--no-fund'], 900);
        }

        $this->step('Building frontend', [$npm, 'run', 'build'], 900);
    }

    /**
     * Run one step, log it and return its output; throws when it fails.
     *
     * @param  list<string>  $command
     */
    protected function step(string $label, array $command, int $timeout = 300, bool $quiet = false): string
    {
        $this->components->task($label);

        $process = Process::path($this->path)
            ->timeout($timeout)
            ->env($this->environment)
            ->run($command);

        $output = trim($process->output()."\n".$process->errorOutput());
        $this->log[] = '$ '.implode(' ', array_map(fn (string $part): string => str_replace($this->path.'/', '', $part), $command));

        if (! $quiet && $output !== '') {
            $this->log[] = $output;
        }

        if ($process->failed()) {
            throw new RuntimeException("{$label} failed (exit code {$process->exitCode()}):\n".mb_substr($output, -4000));
        }

        return $process->output();
    }

    /**
     * @param  array<string, mixed>|null  $request
     */
    protected function failWith(Updater $updater, ?array $request, string $message, ?\Closure $run = null): int
    {
        $this->log[] = 'ERROR: '.$message;
        $this->error($message);

        if ($run !== null || $request !== null) {
            $updater->saveRun($run ? $run('failed', $message) : [
                'status' => 'failed',
                'requested_by' => $request['user'] ?? 'CLI',
                'started_at' => now()->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'error' => $message,
                'log' => implode("\n", $this->log),
            ]);
        }

        return self::FAILURE;
    }
}
