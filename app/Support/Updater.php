<?php

namespace App\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Reads the git state of this installation and keeps the files the admin page
 * and the update command share: a pending request, the last run and the
 * scheduler heartbeat (all in storage/app/updater).
 */
class Updater
{
    protected string $path;

    protected string $stateDirectory;

    public function __construct(?string $path = null, ?string $stateDirectory = null)
    {
        $this->path = $path ?? base_path();
        $this->stateDirectory = $stateDirectory ?? storage_path('app/updater');
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * Run git in the installation directory. --no-optional-locks keeps read-only
     * commands (status) from writing the index, which PHP-FPM may not do.
     *
     * @param  list<string>  $arguments
     */
    public function git(array $arguments, int $timeout = 60): ProcessResult
    {
        return Process::path($this->path)
            ->timeout($timeout)
            ->env([...$this->environment(), 'LC_ALL' => 'C'])
            ->run(['git', '--no-optional-locks', ...$arguments]);
    }

    public function isRepository(): bool
    {
        return $this->git(['rev-parse', '--is-inside-work-tree'])->successful();
    }

    public function remote(): string
    {
        return (string) config('updater.remote', 'origin');
    }

    public function branch(): ?string
    {
        if (filled($branch = config('updater.branch'))) {
            return $branch;
        }

        $result = $this->git(['rev-parse', '--abbrev-ref', 'HEAD']);
        $branch = trim($result->output());

        return $result->successful() && $branch !== 'HEAD' ? $branch : null;
    }

    /**
     * @return array{hash: string, short: string, date: Carbon, subject: string, author: string}|null
     */
    public function commit(string $revision = 'HEAD'): ?array
    {
        $result = $this->git(['log', '-1', '--format=%H%x1f%h%x1f%cI%x1f%s%x1f%an', $revision, '--']);

        if ($result->failed() || blank($result->output())) {
            return null;
        }

        [$hash, $short, $date, $subject, $author] = array_pad(explode("\x1f", trim($result->output())), 5, '');

        return ['hash' => $hash, 'short' => $short, 'date' => Carbon::parse($date), 'subject' => $subject, 'author' => $author];
    }

    public function remoteUrl(): ?string
    {
        $result = $this->git(['remote', 'get-url', $this->remote()]);

        return $result->successful() ? trim($result->output()) : null;
    }

    /**
     * The repository's web address (GitHub/GitLab style), without credentials.
     */
    public function webUrl(): ?string
    {
        $url = (string) $this->remoteUrl();

        if (preg_match('#^git@([^:]+):(.+?)(?:\.git)?$#', $url, $match)) {
            return "https://{$match[1]}/{$match[2]}";
        }

        if (preg_match('#^https?://(?:[^@/]+@)?(.+?)(?:\.git)?/?$#', $url, $match)) {
            return 'https://'.$match[1];
        }

        return null;
    }

    /**
     * Tracked files that differ from the last commit; an update refuses to run over them.
     *
     * @return list<string>
     */
    public function localChanges(): array
    {
        $result = $this->git(['status', '--porcelain', '--untracked-files=no']);

        return $result->successful()
            ? array_values(array_filter(array_map('rtrim', explode("\n", $result->output()))))
            : [];
    }

    /**
     * The newest commit on the remote branch, asked without fetching (nothing is written).
     * Cached for 10 minutes unless $fresh.
     *
     * @return array{hash: ?string, checked_at: string, error: ?string}
     */
    public function remoteState(bool $fresh = false): array
    {
        $key = 'updater.remote.'.md5($this->path);

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(10), function (): array {
            $branch = $this->branch();
            $result = $branch ? $this->git(['ls-remote', $this->remote(), "refs/heads/{$branch}"], 30) : null;
            $hash = $result?->successful() ? strtok(trim($result->output()), "\t") : false;

            return [
                'hash' => $hash ?: null,
                'checked_at' => now()->toIso8601String(),
                'error' => match (true) {
                    $branch === null => 'No branch is checked out.',
                    $result->failed() => trim($result->errorOutput()) ?: 'git ls-remote failed.',
                    ! $hash => "Branch {$branch} was not found on {$this->remote()}.",
                    default => null,
                },
            ];
        });
    }

    public function updateAvailable(): bool
    {
        $remote = $this->remoteState()['hash'];

        return $remote !== null && $remote !== $this->commit()['hash'];
    }

    public function compareUrl(): ?string
    {
        $web = $this->webUrl();
        $remote = $this->remoteState()['hash'];
        $local = $this->commit()['hash'] ?? null;

        return $web && $remote && $local && $remote !== $local ? "{$web}/compare/{$local}...{$remote}" : null;
    }

    /**
     * @param  array<string, mixed>  $request
     */
    public function requestUpdate(array $request): void
    {
        $this->write('request.json', [...$request, 'requested_at' => now()->toIso8601String()]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pendingRequest(): ?array
    {
        return $this->read('request.json');
    }

    public function cancelRequest(): void
    {
        File::delete($this->stateDirectory.'/request.json');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lastRun(): ?array
    {
        return $this->read('last-run.json');
    }

    /**
     * @param  array<string, mixed>  $run
     */
    public function saveRun(array $run): void
    {
        $this->write('last-run.json', $run);
    }

    /**
     * Called every minute by the scheduler, so the admin page can tell whether cron runs.
     */
    public function heartbeat(): void
    {
        File::ensureDirectoryExists($this->stateDirectory);
        File::put($this->stateDirectory.'/heartbeat', now()->toIso8601String());
    }

    public function schedulerSeenAt(): ?Carbon
    {
        $file = $this->stateDirectory.'/heartbeat';

        return File::exists($file) ? Carbon::parse(trim(File::get($file))) : null;
    }

    public function schedulerIsRunning(): bool
    {
        return $this->schedulerSeenAt()?->isAfter(now()->subMinutes(3)) ?? false;
    }

    /**
     * The PHP binary every update step runs with (config updater.php, default php8.4).
     */
    public function php(): ?string
    {
        $php = (string) config('updater.php', 'php8.4');

        if (str_contains($php, '/')) {
            return is_executable($php) ? $php : null;
        }

        return (new ExecutableFinder)->find($php, null, ['/usr/local/bin', '/usr/bin', '/bin']);
    }

    /**
     * The "major.minor" version of the update PHP, e.g. "8.4", or null when it cannot be run.
     */
    public function phpVersion(): ?string
    {
        if (! $php = $this->php()) {
            return null;
        }

        $result = Process::timeout(10)->run([$php, '-r', 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;']);

        return $result->successful() && preg_match('/^\d+\.\d+$/', trim($result->output())) ? trim($result->output()) : null;
    }

    /**
     * Writable folder for the update steps' home and caches (composer, npm).
     */
    public function cachePath(): string
    {
        $path = config('updater.cache_path');

        return filled($path) ? rtrim($path, '/') : $this->stateDirectory.'/cache';
    }

    /**
     * Environment for every process the updater starts. HOME and the composer/npm caches
     * point into cachePath(), never into the web user's own home folder.
     *
     * @return array<string, string>
     */
    public function environment(): array
    {
        $cache = $this->cachePath();
        $key = config('updater.ssh_key');

        return array_filter([
            'HOME' => $cache.'/home',
            'COMPOSER_HOME' => $cache.'/composer',
            'COMPOSER_CACHE_DIR' => $cache.'/composer/cache',
            'COMPOSER_NO_INTERACTION' => '1',
            'npm_config_cache' => $cache.'/npm',
            'npm_config_update_notifier' => 'false',
            'GIT_TERMINAL_PROMPT' => '0',
            'GIT_SSH_COMMAND' => filled($key) ? 'ssh -i '.escapeshellarg($this->absolute($key)).' -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new' : null,
            'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
        ], fn (?string $value): bool => $value !== null);
    }

    public function prepareCache(): void
    {
        foreach (['home', 'composer/cache', 'npm'] as $directory) {
            File::ensureDirectoryExists($this->cachePath().'/'.$directory);
        }
    }

    /**
     * The user the files belong to (owner of the installation folder), e.g. www-data.
     */
    public function owner(): string
    {
        $uid = @fileowner($this->path);

        return $uid !== false && function_exists('posix_getpwuid') ? (posix_getpwuid($uid)['name'] ?? (string) $uid) : 'www-data';
    }

    /**
     * Folders an update writes to that contain something not owned by the installation's owner,
     * each with the first offending file. An update stops before changing anything when this is not empty.
     *
     * @return array<string, string>
     */
    public function ownershipProblems(): array
    {
        $uid = @fileowner($this->path);
        $problems = [];

        if ($uid === false) {
            return [$this->path => $this->path];
        }

        foreach (['.git', 'vendor', 'node_modules', 'public', 'bootstrap/cache', 'storage', 'app', 'config', 'database', 'lang', 'resources', 'routes'] as $folder) {
            $path = $this->path.'/'.$folder;

            if (! file_exists($path)) {
                continue;
            }

            $found = trim(Process::timeout(60)->run(['find', $path, '!', '-uid', (string) $uid, '-print', '-quit'])->output());

            if ($found !== '') {
                $problems[$folder] = str_replace($this->path.'/', '', $found);
            }
        }

        // Files in the root (composer.json, package.json, artisan…) are replaced by git too.
        $found = trim(Process::timeout(10)->run(['find', $this->path, '-maxdepth', '1', '!', '-uid', (string) $uid, '-print', '-quit'])->output());

        if ($found !== '') {
            $problems['.'] = str_replace($this->path.'/', '', $found);
        }

        return $problems;
    }

    protected function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : $this->path.'/'.$path;
    }

    /**
     * Full path of a tool such as composer or npm; cron runs with a minimal PATH.
     */
    public function executable(string $name): ?string
    {
        if (filled($configured = config("updater.{$name}"))) {
            return $configured;
        }

        return (new ExecutableFinder)->find($name, null, ['/usr/local/bin', '/usr/bin', '/bin', '/opt/homebrew/bin']);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function read(string $file): ?array
    {
        $path = $this->stateDirectory.'/'.$file;

        return File::exists($path) ? (json_decode(File::get($path), true) ?: null) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function write(string $file, array $data): void
    {
        File::ensureDirectoryExists($this->stateDirectory);
        File::put($this->stateDirectory.'/'.$file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
