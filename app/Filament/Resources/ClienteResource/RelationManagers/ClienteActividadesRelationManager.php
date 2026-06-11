<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteActividadesRelationManager extends RelationManager
{
    protected static string $relationship = 'actividades';
    protected static ?string $title = 'Actividades';
    

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('fecha')
                ->label('Fecha')
                ->required()
                ->native(false)
                ->closeOnDateSelection(),

            Textarea::make('actividad')
                ->label('Actividad')
                ->rows(3)                 // alto pequeño
                ->autosize(false)         // no crece automáticamente
                ->maxLength(1000)
                ->placeholder('Descripción de la actividad…')
                ->extraInputAttributes([
                    'style' => 'max-height:120px; overflow-y:auto; resize:vertical;' // scroll vertical y permite redimensionar si quiere
                ]),

            TextInput::make('pago')
                ->label('Pago')
                ->numeric()
                ->prefix('$')
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                TextColumn::make('actividad')
                    ->label('Actividad')
                    ->wrap()                 // permite salto de línea
                    ->limit(80)              // muestra un resumen
                    ->tooltip(fn($record) => $record->actividad), // ver completo al pasar el mouse
                // ->lineClamp(3) // alternativa visual si prefieres 3 líneas con “…” (Filament v3)

                TextColumn::make('pago')
                    ->label('Pago')
                    ->money('USD', true) // muestra con formato de dinero
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
