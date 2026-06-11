<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ClienteNotasRelationManager extends RelationManager
{
    protected static string $relationship = 'notas';
    protected static ?string $title = 'Notas rápidas';
    protected static ?string $recordTitleAttribute = 'contenido';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('contenido')
                ->label('Nota')
                ->rows(4)
                ->required(),

            Toggle::make('leida')
                ->label('¿Leída?')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contenido')
                    ->label('Nota')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->contenido)
                    ->wrap()
                    ->searchable(),

                IconColumn::make('leida')
                    ->label('Leída')
                    ->boolean(),

                TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('mark_read')
                    ->label('Marcar como leída')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => ! $record->leida)
                    ->action(fn($record) => $record->update(['leida' => true])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}
