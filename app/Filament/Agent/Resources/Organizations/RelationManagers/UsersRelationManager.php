<?php

namespace App\Filament\Agent\Resources\Organizations\RelationManagers;

use App\Filament\Agent\Resources\Clients\ClientResource;
use App\Models\User;
use Filament\Actions\AssociateAction;
use Filament\Actions\DissociateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.client.plural');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('tickets'))
            ->recordUrl(fn (User $record): string => ClientResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name')),
                TextColumn::make('email')
                    ->label(__('admin.fields.email')),
                TextColumn::make('tickets_count')
                    ->label(__('admin.fields.tickets')),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('role', 'customer')),
            ])
            ->recordActions([
                DissociateAction::make(),
            ]);
    }
}
