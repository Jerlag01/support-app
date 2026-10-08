<?php

namespace App\Filament\Agent\Resources\Tickets\Pages;

use App\Actions\CreateTicket as CreateTicketAction;
use App\Filament\Agent\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Agent\Resources\Tickets\TicketResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    public function form(Schema $schema): Schema
    {
        return TicketForm::configureForCreate($schema);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateTicketAction::class)->handle(
            client: User::findOrFail($data['user_id']),
            data: $data,
            author: auth()->user(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
