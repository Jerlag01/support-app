<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\SystemUpdate;
use App\Models\User;
use App\Support\Updater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\TestCase;

class SystemUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected string $root;

    protected string $clone;

    protected function setUp(): void
    {
        parent::setUp();

        // A throw-away remote (bare repo) with one commit, and a clone that plays "this installation".
        $this->root = sys_get_temp_dir().'/updater-test-'.uniqid();
        $this->clone = $this->root.'/app';
        File::ensureDirectoryExists($this->root.'/seed');
        $this->sh($this->root, ['git', 'init', '-q', '--bare', '-b', 'main', 'remote.git']);
        $this->sh($this->root.'/seed', ['git', 'init', '-q', '-b', 'main']);
        File::put($this->root.'/seed/README.md', 'v1');
        $this->commitAndPush($this->root.'/seed', 'First version', remote: true);
        $this->sh($this->root, ['git', 'clone', '-q', 'remote.git', 'app']);

        $this->app->instance(Updater::class, new Updater($this->clone, $this->root.'/state'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    /**
     * @param  list<string>  $command
     */
    private function sh(string $path, array $command): string
    {
        return Process::path($path)
            ->env(['GIT_AUTHOR_NAME' => 'Test', 'GIT_AUTHOR_EMAIL' => 't@example.com', 'GIT_COMMITTER_NAME' => 'Test', 'GIT_COMMITTER_EMAIL' => 't@example.com'])
            ->run($command)
            ->throw()
            ->output();
    }

    private function commitAndPush(string $path, string $message, bool $remote = false): void
    {
        if ($remote) {
            $this->sh($path, ['git', 'remote', 'add', 'origin', $this->root.'/remote.git']);
        }

        $this->sh($path, ['git', 'add', '-A']);
        $this->sh($path, ['git', 'commit', '-q', '-m', $message]);
        $this->sh($path, ['git', 'push', '-q', 'origin', 'main']);
    }

    public function test_it_reads_the_installed_commit_and_detects_a_newer_remote_commit(): void
    {
        $updater = app(Updater::class);

        $this->assertSame('main', $updater->branch());
        $this->assertSame('First version', $updater->commit()['subject']);
        $this->assertFalse($updater->updateAvailable());

        File::put($this->root.'/seed/README.md', 'v2');
        $this->commitAndPush($this->root.'/seed', 'Second version');

        $this->assertFalse($updater->updateAvailable(), 'The remote state is cached until checked again.');
        $updater->remoteState(fresh: true);
        $this->assertTrue($updater->updateAvailable());
        $this->assertSame('First version', $updater->commit()['subject'], 'Checking must not change the installation.');
    }

    public function test_it_reports_local_changes_and_builds_web_urls(): void
    {
        $updater = app(Updater::class);
        $this->assertSame([], $updater->localChanges());

        File::put($this->clone.'/README.md', 'edited on the server');
        File::put($this->clone.'/untracked.txt', 'ignored');
        $this->assertSame([' M README.md'], $updater->localChanges());

        $this->sh($this->clone, ['git', 'remote', 'set-url', 'origin', 'https://user:secret@github.com/Jerlag01/power-app.git']);
        $this->assertSame('https://github.com/Jerlag01/power-app', $updater->webUrl());
        $this->sh($this->clone, ['git', 'remote', 'set-url', 'origin', 'git@github.com:Jerlag01/power-app.git']);
        $this->assertSame('https://github.com/Jerlag01/power-app', $updater->webUrl());
    }

    public function test_scheduler_run_without_a_request_does_nothing(): void
    {
        $this->artisan('app:update', ['--if-requested' => true])->assertSuccessful();
        $this->assertNull(app(Updater::class)->lastRun());
    }

    public function test_update_records_an_up_to_date_run_and_takes_the_request(): void
    {
        $updater = app(Updater::class);
        $updater->requestUpdate(['user' => 'Admin <admin@example.com>']);

        $this->artisan('app:update', ['--if-requested' => true, '--allow-root' => true])->assertSuccessful();

        $this->assertNull($updater->pendingRequest());
        $this->assertSame('up_to_date', $updater->lastRun()['status']);
        $this->assertSame('Admin <admin@example.com>', $updater->lastRun()['requested_by']);
    }

    public function test_update_refuses_to_overwrite_local_changes(): void
    {
        File::put($this->root.'/seed/README.md', 'v2');
        $this->commitAndPush($this->root.'/seed', 'Second version');
        File::put($this->clone.'/README.md', 'edited on the server');

        $this->artisan('app:update', ['--allow-root' => true])->assertFailed();

        $run = app(Updater::class)->lastRun();
        $this->assertSame('failed', $run['status']);
        $this->assertStringContainsString('README.md', $run['error']);
        $this->assertSame('edited on the server', File::get($this->clone.'/README.md'));
        $this->assertSame('First version', app(Updater::class)->commit()['subject']);
    }

    public function test_check_option_only_reports(): void
    {
        File::put($this->root.'/seed/README.md', 'v2');
        $this->commitAndPush($this->root.'/seed', 'Second version');

        $this->artisan('app:update', ['--check' => true])
            ->expectsOutputToContain('An update is available')
            ->assertSuccessful();

        $this->assertSame('First version', app(Updater::class)->commit()['subject']);
        $this->assertNull(app(Updater::class)->lastRun());
    }

    public function test_only_super_admins_open_the_page_and_request_an_update(): void
    {
        $this->actingAs(User::factory()->agent()->create());
        $this->get('/admin/system-update')->assertForbidden();

        $this->actingAs(User::factory()->admin()->create(['name' => 'Admin']));
        $this->get('/admin/system-update')->assertOk()->assertSee('First version')->assertSee('main');

        Livewire::test(SystemUpdate::class)
            ->callAction('update')
            ->assertNotified();

        $this->assertStringContainsString('Admin', app(Updater::class)->pendingRequest()['user']);

        Livewire::test(SystemUpdate::class)->callAction('cancel');
        $this->assertNull(app(Updater::class)->pendingRequest());
    }

    public function test_heartbeat_marks_the_scheduler_as_running(): void
    {
        $updater = app(Updater::class);
        $this->assertFalse($updater->schedulerIsRunning());

        $updater->heartbeat();
        $this->assertTrue($updater->schedulerIsRunning());
    }

    public function test_update_stops_before_changing_anything_when_files_have_another_owner(): void
    {
        File::put($this->root.'/seed/README.md', 'v2');
        $this->commitAndPush($this->root.'/seed', 'Second version');
        File::ensureDirectoryExists($this->clone.'/vendor/fakerphp');
        chown($this->clone.'/vendor/fakerphp', fileowner($this->clone) === 33 ? 0 : 33);

        $this->artisan('app:update', ['--allow-root' => true])->assertFailed();

        $run = app(Updater::class)->lastRun();
        $this->assertSame('failed', $run['status']);
        $this->assertStringContainsString('vendor: vendor/fakerphp', $run['error']);
        $this->assertStringContainsString('chown -R', $run['error']);
        $this->assertSame('First version', app(Updater::class)->commit()['subject'], 'Nothing may be pulled.');
    }

    public function test_update_refuses_to_start_without_the_configured_php(): void
    {
        config(['updater.php' => 'php-that-does-not-exist']);
        app(Updater::class)->requestUpdate(['user' => 'Admin']);

        $this->artisan('app:update', ['--if-requested' => true, '--allow-root' => true])->assertFailed();

        $this->assertStringContainsString('UPDATER_PHP', app(Updater::class)->lastRun()['error']);
    }

    public function test_steps_run_with_the_configured_php_and_caches_inside_the_app(): void
    {
        $updater = app(Updater::class);

        config(['updater.php' => PHP_BINARY]);
        $this->assertSame(PHP_BINARY, $updater->php());
        $this->assertSame(PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION, $updater->phpVersion());

        $environment = $updater->environment();
        $this->assertSame($this->root.'/state/cache/composer/cache', $environment['COMPOSER_CACHE_DIR']);
        $this->assertSame($this->root.'/state/cache/home', $environment['HOME']);
        $this->assertSame($this->root.'/state/cache/npm', $environment['npm_config_cache']);
        $this->assertArrayNotHasKey('GIT_SSH_COMMAND', $environment);

        config(['updater.cache_path' => '/srv/updater-cache/', 'updater.ssh_key' => 'storage/app/updater/deploy_key']);
        $this->assertSame('/srv/updater-cache/composer', $updater->environment()['COMPOSER_HOME']);
        $this->assertStringContainsString($this->clone.'/storage/app/updater/deploy_key', $updater->environment()['GIT_SSH_COMMAND']);
    }
}
