<?php

namespace App\Filament\Agent\Resources\Tickets\Pages;

use App\Actions\AssignTicket;
use App\Actions\PostTicketMessage;
use App\Enums\TicketStatus;
use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Http\Requests\AttachmentRules;
use App\Models\CannedResponse;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketStatusChanged;
use App\Support\HelpdeskSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * @property Ticket $record
 */
class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getTitle(): string
    {
        return "{$this->record->reference} · {$this->record->subject}";
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->replyAction(),
            $this->noteAction(),
            Action::make('assignToMe')
                ->label(__('admin.actions.assign_to_me'))
                ->icon(Heroicon::OutlinedHandRaised)
                ->color('gray')
                ->authorize('update')
                ->visible(fn (): bool => $this->record->assigned_to !== auth()->id())
                ->action(function (AssignTicket $assign): void {
                    $assign->handle($this->record, auth()->id(), $this->record->team_id, auth()->user());
                    $this->notifySuccess('assigned');
                }),
            ActionGroup::make([
                $this->assignAction(),
                $this->transferAction(),
                $this->statusAction(),
                EditAction::make(),
                Action::make('openPortal')
                    ->label(__('admin.actions.open_portal'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (): string => route('tickets.show', $this->record), shouldOpenInNewTab: true),
                DeleteAction::make(),
            ])->button()->label(__('admin.actions.more'))->color('gray'),
        ];
    }

    private function replyAction(): Action
    {
        return Action::make('reply')
            ->label(__('admin.actions.reply'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->authorize('update')
            ->modalHeading(__('admin.actions.reply'))
            ->modalWidth(Width::ThreeExtraLarge)
            ->fillForm(fn (): array => [
                'new_status' => TicketStatus::WaitingOnCustomer,
                'append_signature' => filled(auth()->user()->signature),
            ])
            ->schema([
                Select::make('canned_response_id')
                    ->label(__('admin.fields.canned_response'))
                    ->options(fn (): array => CannedResponse::usableFor($this->record)->pluck('title', 'id')->all())
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $response = CannedResponse::find($state);
                        if ($response) {
                            $set('message', trim($get('message')."\n\n".$response->renderFor($this->record, auth()->user())));
                        }
                    }),
                Textarea::make('message')
                    ->label(__('admin.fields.message'))
                    ->required()
                    ->maxLength(10000)
                    ->rows(10),
                $this->attachmentsField(),
                Toggle::make('append_signature')
                    ->label(__('admin.fields.append_signature'))
                    ->visible(fn (): bool => filled(auth()->user()->signature)),
                Select::make('new_status')
                    ->label(__('admin.fields.new_status'))
                    ->options(TicketStatus::class)
                    ->required(),
            ])
            ->action(function (array $data, PostTicketMessage $postMessage, HelpdeskSettings $settings): void {
                $agent = auth()->user();
                $body = $data['message'];

                if (($data['append_signature'] ?? false) && filled($agent->signature)) {
                    $body .= "\n\n".$agent->signature;
                }

                $postMessage->handle(
                    ticket: $this->record,
                    author: $agent,
                    body: $body,
                    attachments: $this->storedAttachments($data),
                    newStatus: $data['new_status'] instanceof TicketStatus ? $data['new_status'] : TicketStatus::from($data['new_status']),
                );

                if ($settings->get('auto_assign_on_reply') && $this->record->assigned_to === null) {
                    $this->record->update(['assigned_to' => $agent->id]);
                }

                $this->record->refresh();
                $this->notifySuccess('replied');
            });
    }

    private function noteAction(): Action
    {
        return Action::make('note')
            ->label(__('admin.actions.note'))
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('warning')
            ->authorize('update')
            ->modalHeading(__('admin.actions.note'))
            ->modalDescription(__('admin.help.internal'))
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Textarea::make('message')
                    ->label(__('admin.fields.note'))
                    ->required()
                    ->maxLength(10000)
                    ->rows(6),
                $this->attachmentsField(),
            ])
            ->action(function (array $data, PostTicketMessage $postMessage): void {
                $postMessage->handle($this->record, auth()->user(), $data['message'], $this->storedAttachments($data), isInternal: true);
                $this->notifySuccess('note_added');
            });
    }

    private function assignAction(): Action
    {
        return Action::make('assign')
            ->label(__('admin.actions.assign'))
            ->icon(Heroicon::OutlinedUserPlus)
            ->authorize('update')
            ->modalWidth(Width::Large)
            ->fillForm(fn (): array => ['assigned_to' => $this->record->assigned_to, 'team_id' => $this->record->team_id])
            ->schema([
                Select::make('assigned_to')
                    ->label(__('admin.fields.assignee'))
                    ->options(fn (): array => User::withAccessToDepartment($this->record->department_id)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Select::make('team_id')
                    ->label(__('admin.fields.team'))
                    ->options(fn (): array => Team::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->action(function (array $data, AssignTicket $assign): void {
                $assign->handle($this->record, $data['assigned_to'] ?? null, $data['team_id'] ?? null, auth()->user());
                $this->notifySuccess('assigned');
            });
    }

    private function transferAction(): Action
    {
        return Action::make('transfer')
            ->label(__('admin.actions.transfer'))
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->authorize('update')
            ->modalWidth(Width::Large)
            ->fillForm(fn (): array => ['department_id' => $this->record->department_id])
            ->schema([
                Select::make('department_id')
                    ->label(__('admin.fields.department'))
                    ->options(fn (): array => Department::orderBy('name')->pluck('name', 'id')->all())
                    ->required(),
                Textarea::make('note')
                    ->label(__('admin.fields.transfer_note'))
                    ->rows(3)
                    ->maxLength(2000),
            ])
            ->action(function (array $data, PostTicketMessage $postMessage): void {
                if ((int) $data['department_id'] === $this->record->department_id) {
                    return;
                }

                // A transfer clears the agent if they can't see the new department.
                $this->record->department_id = (int) $data['department_id'];
                $assignee = $this->record->assignee;
                if ($assignee && ! User::withAccessToDepartment($this->record->department_id)->whereKey($assignee->id)->exists()) {
                    $this->record->assigned_to = null;
                }
                $this->record->save();

                if (filled($data['note'] ?? null)) {
                    $postMessage->handle($this->record, auth()->user(), $data['note'], isInternal: true);
                }

                $this->notifySuccess('transferred');

                if (! auth()->user()->can('view', $this->record->fresh())) {
                    $this->redirect(TicketResource::getUrl('index'));
                }
            });
    }

    private function statusAction(): Action
    {
        return Action::make('changeStatus')
            ->label(__('admin.actions.change_status'))
            ->icon(Heroicon::OutlinedArrowPath)
            ->authorize('update')
            ->modalWidth(Width::Medium)
            ->fillForm(fn (): array => ['status' => $this->record->status, 'notify' => true])
            ->schema([
                Select::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(TicketStatus::class)
                    ->required(),
                Toggle::make('notify')
                    ->label(__('admin.fields.notify_client')),
            ])
            ->action(function (array $data): void {
                $this->record->update(['status' => $data['status']]);

                if ($data['notify'] && $this->record->wasChanged('status')) {
                    $this->record->user->notify(new TicketStatusChanged($this->record));
                }

                $this->notifySuccess('status_changed');
            });
    }

    private function attachmentsField(): FileUpload
    {
        return FileUpload::make('attachments')
            ->label(__('admin.fields.attachments'))
            ->disk('local')
            ->directory('tmp/ticket-uploads')
            ->visibility('private')
            ->multiple()
            ->maxFiles(AttachmentRules::MAX_FILES)
            ->maxSize(AttachmentRules::MAX_KILOBYTES)
            ->rules(['extensions:'.implode(',', AttachmentRules::EXTENSIONS)])
            ->storeFileNamesIn('attachment_names');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{path: string, name: string}>
     */
    private function storedAttachments(array $data): array
    {
        return collect($data['attachments'] ?? [])
            ->map(fn (string $path): array => ['path' => $path, 'name' => $data['attachment_names'][$path] ?? basename($path)])
            ->values()
            ->all();
    }

    private function notifySuccess(string $key): void
    {
        Notification::make()->title(__("admin.notifications.{$key}"))->success()->send();
    }
}
