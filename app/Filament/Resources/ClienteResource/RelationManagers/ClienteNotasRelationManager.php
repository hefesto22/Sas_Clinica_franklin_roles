<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;

class ClienteNotasRelationManager extends RelationManager
{
    protected static string $relationship = 'notas';
    protected static ?string $title = 'Notas rápidas';
    protected static ?string $recordTitleAttribute = 'contenido';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('contenido')
                ->label('Nota')
                ->rows(4)
                ->required(),

            Forms\Components\Toggle::make('leida')
                ->label('¿Leída?')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contenido')
                    ->label('Nota')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->contenido)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\IconColumn::make('leida')
                    ->label('Leída')
                    ->boolean(),

                Tables\Columns\TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Action::make('mark_read')
                    ->label('Marcar como leída')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => ! $record->leida)
                    ->action(fn($record) => $record->update(['leida' => true])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}
