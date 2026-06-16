<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Cliente;
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

            Select::make('tipo')
                ->label('Tipo')
                ->options(Cliente::TIPOS)
                // Presugiere el tipo del paciente; igual se puede cambiar o dejar vacío.
                ->default(fn () => $this->getOwnerRecord()?->tipo_paciente)
                ->native(false)
                ->placeholder('Sin especificar'),

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
                ->helperText('Opcional: se puede dejar vacío.')
                ->numeric()
                ->prefix('$')
                ->nullable()
                // Vacío se guarda como NULL (no como 0).
                ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null),
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

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Cliente::TIPOS[$state] ?? '—')
                    ->color(fn (?string $state) => $state === 'ortodoncia' ? 'info' : 'gray'),

                TextColumn::make('actividad')
                    ->label('Actividad')
                    ->wrap()                 // permite salto de línea
                    ->limit(80)              // muestra un resumen
                    ->tooltip(fn($record) => $record->actividad), // ver completo al pasar el mouse
                // ->lineClamp(3) // alternativa visual si prefieres 3 líneas con “…” (Filament v3)

                TextColumn::make('pago')
                    ->label('Pago')
                    ->money('USD', true) // muestra con formato de dinero
                    ->placeholder('Sin pago') // actividades sin cobro
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(Cliente::TIPOS),
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
