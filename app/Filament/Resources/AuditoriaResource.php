<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditoriaResource\Pages\ListAuditorias;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/**
 * Auditoría clínica: SOLO lectura.
 *
 * El acceso lo gobierna ActivityPolicy (todo denegado) + el Gate::before
 * de Shield: en la práctica, únicamente super_admin ve este Resource.
 */
class AuditoriaResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Filament Shield';

    protected static ?string $modelLabel = 'Registro de Auditoría';

    protected static ?string $pluralModelLabel = 'Auditoría';

    protected static ?string $slug = 'auditoria';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->default('Sistema')
                    ->searchable(),

                TextColumn::make('event')
                    ->label('Acción')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created'  => 'Creó',
                        'updated'  => 'Editó',
                        'deleted'  => 'Eliminó',
                        'consulta' => 'Consultó',
                        default    => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'created'  => 'success',
                        'updated'  => 'warning',
                        'deleted'  => 'danger',
                        'consulta' => 'info',
                        default    => 'gray',
                    }),

                TextColumn::make('subject_type')
                    ->label('Registro')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),

                TextColumn::make('subject_id')
                    ->label('ID'),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(60)
                    ->wrap(),

                TextColumn::make('attribute_changes')
                    ->label('Cambios')
                    ->formatStateUsing(function ($record): string {
                        // v5 guarda el diff en attribute_changes: {attributes: {...}, old: {...}}
                        $cambios = $record->attribute_changes['attributes'] ?? null;

                        if (! is_array($cambios) || $cambios === []) {
                            return '—';
                        }

                        return collect($cambios)
                            ->map(fn ($valor, $campo) => "{$campo}: " . (is_scalar($valor) ? $valor : json_encode($valor)))
                            ->take(4)
                            ->implode(' · ');
                    })
                    ->limit(80)
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Acción')
                    ->options([
                        'created'  => 'Creó',
                        'updated'  => 'Editó',
                        'deleted'  => 'Eliminó',
                        'consulta' => 'Consultó',
                    ]),
                SelectFilter::make('log_name')
                    ->label('Tipo')
                    ->options([
                        'clinico'    => 'Cambios clínicos',
                        'expediente' => 'Lecturas de expediente',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditorias::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
