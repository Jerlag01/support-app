<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AdminNavigationGroup;
use App\Support\Updater;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Shows the installed git version and queues an update from the repository.
 * PHP-FPM may not write the code, so the scheduler (cron as www-data) runs `artisan app:update`.
 */
class SystemUpdate extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::System;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'system-update';

    public static function canAccess(): bool
    {
        return static::userMayUpdate();
    }

    public static function getNavigationLabel(): string
    {
        return __('updater.title');
    }

    public function getTitle(): string
    {
        return __('updater.title');
    }

    public static function getNavigationBadge(): ?string
    {
        // Only the cached remote state: the menu must never wait for the network.
        return cache()->has('updater.remote.'.md5(base_path())) && app(Updater::class)->updateAvailable() ? '1' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('updater.available');
    }

    protected static function userMayUpdate(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function updater(): Updater
    {
        return app(Updater::class);
    }

    protected function isBusy(): bool
    {
        return $this->updater()->pendingRequest() !== null || ($this->updater()->lastRun()['status'] ?? null) === 'running';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check')
                ->label(__('updater.actions.check'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    $state = $this->updater()->remoteState(fresh: true);

                    Notification::make()
                        ->title(match (true) {
                            $state['error'] !== null => __('updater.check_failed'),
                            $this->updater()->updateAvailable() => __('updater.available'),
                            default => __('updater.up_to_date'),
                        })
                        ->body($state['error'])
                        ->status($state['error'] ? 'danger' : 'success')
                        ->send();
                }),
            Action::make('update')
                ->label(__('updater.actions.update'))
                ->icon(Heroicon::OutlinedCloudArrowDown)
                ->requiresConfirmation()
                ->modalHeading(__('updater.actions.update'))
                ->modalDescription(__('updater.confirm'))
                ->disabled(fn (): bool => $this->isBusy())
                ->action(function (): void {
                    $this->updater()->requestUpdate(['user' => auth()->user()->name.' <'.auth()->user()->email.'>']);

                    Notification::make()
                        ->title(__('updater.requested'))
                        ->body($this->updater()->schedulerIsRunning() ? __('updater.requested_body') : __('updater.no_scheduler'))
                        ->status($this->updater()->schedulerIsRunning() ? 'success' : 'warning')
                        ->send();
                }),
            Action::make('cancel')
                ->label(__('updater.actions.cancel'))
                ->color('danger')
                ->visible(fn (): bool => $this->updater()->pendingRequest() !== null)
                ->action(fn () => $this->updater()->cancelRequest()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $updater = $this->updater();
        $commit = $updater->commit();
        $remote = $updater->remoteState();
        $changes = $updater->localChanges();
        $request = $updater->pendingRequest();
        $run = $updater->lastRun();
        $seen = $updater->schedulerSeenAt();
        $date = fn (?string $value): string => $value ? Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i:s') : '-';

        return $schema
            ->components([
                Section::make(__('updater.sections.version'))
                    ->columns(['md' => 3])
                    ->extraAttributes($this->isBusy() ? ['wire:poll.5s' => ''] : [])
                    ->schema([
                        TextEntry::make('branch')
                            ->label(__('updater.fields.branch'))
                            ->state($updater->branch() ?? '-')
                            ->badge(),
                        TextEntry::make('commit')
                            ->label(__('updater.fields.installed'))
                            ->state($commit ? $commit['short'].' · '.$commit['subject'] : __('updater.not_a_repository'))
                            ->fontFamily(FontFamily::Mono)
                            ->helperText($commit ? $commit['date']->timezone(config('app.timezone'))->format('d/m/Y H:i').' · '.$commit['author'] : null)
                            ->url($commit && $updater->webUrl() ? $updater->webUrl().'/commit/'.$commit['hash'] : null, shouldOpenInNewTab: true)
                            ->columnSpan(['md' => 2]),
                        TextEntry::make('remote')
                            ->label(__('updater.fields.remote'))
                            ->state(match (true) {
                                $remote['error'] !== null => $remote['error'],
                                $updater->updateAvailable() => __('updater.available').' ('.substr($remote['hash'], 0, 7).')',
                                default => __('updater.up_to_date'),
                            })
                            ->color(match (true) {
                                $remote['error'] !== null => 'danger',
                                $updater->updateAvailable() => 'warning',
                                default => 'success',
                            })
                            ->badge()
                            ->helperText(__('updater.checked_at', ['time' => $date($remote['checked_at'])])),
                        TextEntry::make('compare')
                            ->label(__('updater.fields.changes'))
                            ->state($updater->compareUrl() ? __('updater.view_changes') : '-')
                            ->url($updater->compareUrl(), shouldOpenInNewTab: true)
                            ->color('primary'),
                        TextEntry::make('repository')
                            ->label(__('updater.fields.repository'))
                            ->state($updater->webUrl() ?? $updater->remoteUrl() ?? '-')
                            ->url($updater->webUrl(), shouldOpenInNewTab: true),
                        TextEntry::make('local_changes')
                            ->label(__('updater.fields.local_changes'))
                            ->state($changes === [] ? __('updater.no_local_changes') : implode("\n", $changes))
                            ->color($changes === [] ? 'success' : 'danger')
                            ->helperText($changes === [] ? null : __('updater.local_changes_help'))
                            ->fontFamily($changes === [] ? null : FontFamily::Mono)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('updater.sections.status'))
                    ->columns(['md' => 3])
                    ->schema([
                        TextEntry::make('scheduler')
                            ->label(__('updater.fields.scheduler'))
                            ->state($updater->schedulerIsRunning() ? __('updater.scheduler_ok') : __('updater.scheduler_missing'))
                            ->color($updater->schedulerIsRunning() ? 'success' : 'danger')
                            ->badge()
                            ->helperText($seen ? __('updater.last_seen', ['time' => $date($seen->toIso8601String())]) : null),
                        TextEntry::make('request')
                            ->label(__('updater.fields.request'))
                            ->state($request ? __('updater.waiting', ['user' => $request['user'] ?? '-', 'time' => $date($request['requested_at'] ?? null)]) : '-'),
                        TextEntry::make('last_status')
                            ->label(__('updater.fields.last_run'))
                            ->state($run ? __('updater.statuses.'.($run['status'] ?? 'failed')) : '-')
                            ->color(match ($run['status'] ?? null) {
                                'success', 'up_to_date' => 'success',
                                'running' => 'warning',
                                null => 'gray',
                                default => 'danger',
                            })
                            ->badge()
                            ->helperText($run ? $date($run['finished_at'] ?? $run['started_at'] ?? null).' · '.($run['requested_by'] ?? '-').(isset($run['from'], $run['to']) && $run['from'] !== $run['to'] ? ' · '.substr($run['from'], 0, 7).' → '.substr($run['to'], 0, 7) : '') : null),
                        TextEntry::make('scheduler_help')
                            ->hiddenLabel()
                            ->visible(! $updater->schedulerIsRunning())
                            ->state(new HtmlString(__('updater.scheduler_help', [
                                'cron' => '<code style="font-family:monospace;padding:2px 4px;border-radius:4px;background:rgba(127,127,127,.15)">* * * * * cd '.e(base_path()).' &amp;&amp; '.e('php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION).' artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>',
                            ])))
                            ->columnSpanFull(),
                        TextEntry::make('error')
                            ->label(__('updater.fields.error'))
                            ->visible(filled($run['error'] ?? null))
                            ->state($run['error'] ?? null)
                            ->color('danger')
                            ->columnSpanFull(),
                        TextEntry::make('log')
                            ->label(__('updater.fields.log'))
                            ->visible(filled($run['log'] ?? null))
                            ->state(new HtmlString('<pre style="white-space:pre-wrap;word-break:break-all;font-family:monospace;font-size:12px;line-height:1.5;max-height:28rem;overflow:auto;margin:0">'.e($run['log'] ?? '').'</pre>'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
