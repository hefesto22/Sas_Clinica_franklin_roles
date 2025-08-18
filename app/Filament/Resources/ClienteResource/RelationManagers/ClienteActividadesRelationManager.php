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
    protected static ?string $recordTitleAttribute = 'actividad';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('fecha')
                ->label('Fecha')
                ->required()
                ->native(false)
                ->closeOnDateSelection(),

            Forms\Components\TextInput::make('actividad')
                ->label('Actividad')
                ->required()
                ->maxLength(255),

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
                    ->searchable(),

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
