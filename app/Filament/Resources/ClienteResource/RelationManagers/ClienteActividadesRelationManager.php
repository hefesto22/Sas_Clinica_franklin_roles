<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteActividadesRelationManager extends RelationManager
{
    protected static string $relationship = 'actividades';
    protected static ?string $title = 'Actividades';
    

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('fecha')
                ->label('Fecha')
                ->required()
                ->native(false)
                ->closeOnDateSelection(),

            Forms\Components\Textarea::make('actividad')
                ->label('Actividad')
                ->rows(3)                 // alto pequeño
                ->autosize(false)         // no crece automáticamente
                ->maxLength(1000)
                ->placeholder('Descripción de la actividad…')
                ->extraInputAttributes([
                    'style' => 'max-height:120px; overflow-y:auto; resize:vertical;' // scroll vertical y permite redimensionar si quiere
                ]),

            Forms\Components\TextInput::make('pago')
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
                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('actividad')
                    ->label('Actividad')
                    ->wrap()                 // permite salto de línea
                    ->limit(80)              // muestra un resumen
                    ->tooltip(fn($record) => $record->actividad), // ver completo al pasar el mouse
                // ->lineClamp(3) // alternativa visual si prefieres 3 líneas con “…” (Filament v3)

                Tables\Columns\TextColumn::make('pago')
                    ->label('Pago')
                    ->money('USD', true) // muestra con formato de dinero
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
