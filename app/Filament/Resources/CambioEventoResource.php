<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\CambioEventoResource\Pages\ListCambioEventos;
use App\Filament\Resources\CambioEventoResource\Pages\CreateCambioEvento;
use App\Filament\Resources\CambioEventoResource\Pages\EditCambioEvento;
use App\Filament\Resources\CambioEventoResource\Pages;
use App\Models\CambioEvento;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CambioEventoResource extends Resource
{
    protected static ?string $model = CambioEvento::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string | \UnitEnum | null $navigationGroup = 'Citas';
    protected static ?string $modelLabel = 'Cambio de Evento';
    protected static ?string $pluralModelLabel = 'Solicitudes de Cambio';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('estado')
                ->label('Estado de solicitud')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aceptado' => 'Aceptado',
                    'rechazado' => 'Rechazado',
                    'cancelado' => 'Cancelado',
                ])
                ->required(),

            Textarea::make('motivo_cancelacion')
                ->label('Motivo de cancelación')
                ->visible(fn($get) => $get('estado') === 'cancelado'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('eventoOrigen.cliente.nombre')
                ->label('Paciente Original')
                ->searchable(),

            TextColumn::make('eventoDestino.cliente.nombre')
                ->label('Paciente Alternativo')
                ->searchable(),

            TextColumn::make('estado')
                ->label('Estado')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'pendiente' => 'warning',
                    'aceptado' => 'success',
                    'rechazado', 'cancelado' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('creador.name')
                ->label('Solicitado por')
                ->searchable(),

            TextColumn::make('created_at')
                ->label('Fecha')
                ->dateTime('d/m/Y h:i A'),
        ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('contactar')
                    ->label('Contactar')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->url(fn(CambioEvento $record) =>
                    filled($record->eventoDestino?->cliente?->telefono)
                        ? 'https://wa.me/504' . preg_replace('/\D/', '', $record->eventoDestino->cliente->telefono)
                        : '#', true)
                    ->openUrlInNewTab()
                    ->visible(fn(CambioEvento $record) => filled($record->eventoDestino?->cliente?->telefono)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => ListCambioEventos::route('/'),
            'create' => CreateCambioEvento::route('/create'),
            'edit' => EditCambioEvento::route('/{record}/edit'),
        ];
    }
}
