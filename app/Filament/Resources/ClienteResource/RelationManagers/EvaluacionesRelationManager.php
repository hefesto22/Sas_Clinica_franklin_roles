<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\TextEntry;




class EvaluacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluaciones';
    protected static ?string $title = 'Evaluaciones (Hojas)';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos generales')
                ->columns(4)
                ->schema([
                    Forms\Components\DatePicker::make('fecha')
                        ->label('Fecha')
                        ->required()
                        ->default(now()),
                    Forms\Components\TextInput::make('limpieza_periodontal')
                        ->label('Limpieza periodontal')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('fluor')
                        ->label('Flúor')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')
                ->schema([
                    Forms\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                        // Fila 1
                        Forms\Components\Textarea::make('dx.1_8')->label('1.8')->rows(2),
                        Forms\Components\Placeholder::make('gap_1')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Placeholder::make('gap_2')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Textarea::make('dx.2_8')->label('2.8')->rows(2),

                        // Fila 2
                        Forms\Components\Textarea::make('dx.1_7')->label('1.7')->rows(2),
                        Forms\Components\Placeholder::make('gap_3')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Placeholder::make('gap_4')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Textarea::make('dx.2_7')->label('2.7')->rows(2),

                        // Fila 3
                        Forms\Components\Textarea::make('dx.1_6')->label('1.6')->rows(2),
                        Forms\Components\Placeholder::make('gap_5')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Placeholder::make('gap_6')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Textarea::make('dx.2_6')->label('2.6')->rows(2),

                        // Fila 4
                        Forms\Components\Textarea::make('dx.1_5')->label('1.5')->rows(2),
                        Forms\Components\Textarea::make('dx.5_5')->label('5.5')->rows(2),
                        Forms\Components\Textarea::make('dx.2_5')->label('2.5')->rows(2),
                        Forms\Components\Textarea::make('dx.6_5')->label('6.5')->rows(2),

                        // Fila 5
                        Forms\Components\Textarea::make('dx.1_4')->label('1.4')->rows(2),
                        Forms\Components\Textarea::make('dx.5_4')->label('5.4')->rows(2),
                        Forms\Components\Textarea::make('dx.2_4')->label('2.4')->rows(2),
                        Forms\Components\Textarea::make('dx.6_4')->label('6.4')->rows(2),

                        // Fila 6
                        Forms\Components\Textarea::make('dx.1_3')->label('1.3')->rows(2),
                        Forms\Components\Textarea::make('dx.5_3')->label('5.3')->rows(2),
                        Forms\Components\Textarea::make('dx.2_3')->label('2.3')->rows(2),
                        Forms\Components\Textarea::make('dx.6_3')->label('6.3')->rows(2),

                        // Fila 7
                        Forms\Components\Textarea::make('dx.1_2')->label('1.2')->rows(2),
                        Forms\Components\Textarea::make('dx.5_2')->label('5.2')->rows(2),
                        Forms\Components\Textarea::make('dx.2_2')->label('2.2')->rows(2),
                        Forms\Components\Textarea::make('dx.6_2')->label('6.2')->rows(2),

                        // Fila 8
                        Forms\Components\Textarea::make('dx.1_1')->label('1.1')->rows(2),
                        Forms\Components\Textarea::make('dx.5_1')->label('5.1')->rows(2),
                        Forms\Components\Textarea::make('dx.2_1')->label('2.1')->rows(2),
                        Forms\Components\Textarea::make('dx.6_1')->label('6.1')->rows(2),

                        // Fila 9
                        Forms\Components\Textarea::make('dx.3_1')->label('3.1')->rows(2),
                        Forms\Components\Textarea::make('dx.7_1')->label('7.1')->rows(2),
                        Forms\Components\Textarea::make('dx.4_1')->label('4.1')->rows(2),
                        Forms\Components\Textarea::make('dx.8_1')->label('8.1')->rows(2),

                        // Fila 10
                        Forms\Components\Textarea::make('dx.3_2')->label('3.2')->rows(2),
                        Forms\Components\Textarea::make('dx.7_2')->label('7.2')->rows(2),
                        Forms\Components\Textarea::make('dx.4_2')->label('4.2')->rows(2),
                        Forms\Components\Textarea::make('dx.8_2')->label('8.2')->rows(2),

                        // Fila 11
                        Forms\Components\Textarea::make('dx.3_3')->label('3.3')->rows(2),
                        Forms\Components\Textarea::make('dx.7_3')->label('7.3')->rows(2),
                        Forms\Components\Textarea::make('dx.4_3')->label('4.3')->rows(2),
                        Forms\Components\Textarea::make('dx.8_3')->label('8.3')->rows(2),

                        // Fila 12
                        Forms\Components\Textarea::make('dx.3_4')->label('3.4')->rows(2),
                        Forms\Components\Textarea::make('dx.7_4')->label('7.4')->rows(2),
                        Forms\Components\Textarea::make('dx.4_4')->label('4.4')->rows(2),
                        Forms\Components\Textarea::make('dx.8_4')->label('8.4')->rows(2),

                        // Fila 13
                        Forms\Components\Textarea::make('dx.3_5')->label('3.5')->rows(2),
                        Forms\Components\Textarea::make('dx.7_5')->label('7.5')->rows(2),
                        Forms\Components\Textarea::make('dx.4_5')->label('4.5')->rows(2),
                        Forms\Components\Textarea::make('dx.8_5')->label('8.5')->rows(2),

                        // Fila 14
                        Forms\Components\Textarea::make('dx.3_6')->label('3.6')->rows(2),
                        Forms\Components\Placeholder::make('gap_7')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Placeholder::make('gap_8')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Textarea::make('dx.4_6')->label('4.6')->rows(2),

                        // Fila 15
                        Forms\Components\Textarea::make('dx.3_7')->label('3.7')->rows(2),
                        Forms\Components\Placeholder::make('gap_9')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Placeholder::make('gap_10')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Textarea::make('dx.4_7')->label('4.7')->rows(2),

                        // Fila 16
                        Forms\Components\Textarea::make('dx.3_8')->label('3.8')->rows(2),
                        Forms\Components\Placeholder::make('gap_11')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Placeholder::make('gap_12')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Forms\Components\Textarea::make('dx.4_8')->label('4.8')->rows(2),
                    ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fecha')->label('Fecha')->date()->sortable()->searchable(),
                Tables\Columns\TextColumn::make('limpieza_periodontal')->label('Limpieza'),
                Tables\Columns\TextColumn::make('fluor')->label('Flúor'),
                Tables\Columns\TextColumn::make('detalles_count')->label('# Piezas')->counts('detalles'),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Creada'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva evaluación')

                    // 1) dx.* → detalles_payload (se hace en la acción)
                    ->mutateFormDataUsing(function (array $data): array {
                        $payload = [];
                        foreach (($data['dx'] ?? []) as $key => $val) {
                            if ($val === null || $val === '') {
                                continue; // no crear vacíos
                            }
                            $pieza = str_replace('_', '.', $key); // 1_8 => 1.8
                            $payload[] = ['pieza' => $pieza, 'diagnostico' => $val];
                        }
                        $data['detalles_payload'] = $payload;
                        unset($data['dx']); // no existe columna 'dx'
                        return $data;
                    })

                    // 2) Guardar/actualizar relación detalles
                    ->after(function (Model $record, array $data) {
                        $payload = $data['detalles_payload'] ?? [];

                        foreach ($payload as $item) {
                            if (empty($item['pieza'])) continue;

                            $record->detalles()->updateOrCreate(
                                ['pieza' => $item['pieza']],
                                ['diagnostico' => $item['diagnostico']]
                            );
                        }

                        // Eliminar piezas que quedaron vacías
                        $piezas = collect($payload)->pluck('pieza')->all();
                        if (! empty($piezas)) {
                            $record->detalles()->whereNotIn('pieza', $piezas)->delete();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Vista de evaluación')
                    ->form([
                        // Datos generales
                        Forms\Components\Section::make('Datos generales')
                            ->columns(4)
                            ->schema([
                                Forms\Components\DatePicker::make('fecha')->label('Fecha')->disabled(),
                                Forms\Components\TextInput::make('limpieza_periodontal')->label('Limpieza periodontal')->disabled(),
                                Forms\Components\TextInput::make('fluor')->label('Flúor')->disabled(),
                                Forms\Components\Textarea::make('observaciones')->label('Observaciones')->rows(2)->disabled()->columnSpanFull(),
                            ]),

                        // Diagnóstico (idéntico a tu form, pero deshabilitado y autosize)
                        Forms\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')
                            ->schema([
                                Forms\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                                    // Fila 1
                                    Forms\Components\Textarea::make('dx.1_8')->label('1.8')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Placeholder::make('gap_v1')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Placeholder::make('gap_v2')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Textarea::make('dx.2_8')->label('2.8')->rows(2)->autosize()->disabled(),

                                    // Fila 2
                                    Forms\Components\Textarea::make('dx.1_7')->label('1.7')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Placeholder::make('gap_v3')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Placeholder::make('gap_v4')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Textarea::make('dx.2_7')->label('2.7')->rows(2)->autosize()->disabled(),

                                    // Fila 3
                                    Forms\Components\Textarea::make('dx.1_6')->label('1.6')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Placeholder::make('gap_v5')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Placeholder::make('gap_v6')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Textarea::make('dx.2_6')->label('2.6')->rows(2)->autosize()->disabled(),

                                    // Fila 4
                                    Forms\Components\Textarea::make('dx.1_5')->label('1.5')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.5_5')->label('5.5')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.2_5')->label('2.5')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.6_5')->label('6.5')->rows(2)->autosize()->disabled(),

                                    // Fila 5
                                    Forms\Components\Textarea::make('dx.1_4')->label('1.4')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.5_4')->label('5.4')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.2_4')->label('2.4')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.6_4')->label('6.4')->rows(2)->autosize()->disabled(),

                                    // Fila 6
                                    Forms\Components\Textarea::make('dx.1_3')->label('1.3')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.5_3')->label('5.3')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.2_3')->label('2.3')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.6_3')->label('6.3')->rows(2)->autosize()->disabled(),

                                    // Fila 7
                                    Forms\Components\Textarea::make('dx.1_2')->label('1.2')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.5_2')->label('5.2')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.2_2')->label('2.2')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.6_2')->label('6.2')->rows(2)->autosize()->disabled(),

                                    // Fila 8
                                    Forms\Components\Textarea::make('dx.1_1')->label('1.1')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.5_1')->label('5.1')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.2_1')->label('2.1')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.6_1')->label('6.1')->rows(2)->autosize()->disabled(),

                                    // Fila 9
                                    Forms\Components\Textarea::make('dx.3_1')->label('3.1')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.7_1')->label('7.1')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.4_1')->label('4.1')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.8_1')->label('8.1')->rows(2)->autosize()->disabled(),

                                    // Fila 10
                                    Forms\Components\Textarea::make('dx.3_2')->label('3.2')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.7_2')->label('7.2')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.4_2')->label('4.2')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.8_2')->label('8.2')->rows(2)->autosize()->disabled(),

                                    // Fila 11
                                    Forms\Components\Textarea::make('dx.3_3')->label('3.3')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.7_3')->label('7.3')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.4_3')->label('4.3')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.8_3')->label('8.3')->rows(2)->autosize()->disabled(),

                                    // Fila 12
                                    Forms\Components\Textarea::make('dx.3_4')->label('3.4')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.7_4')->label('7.4')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.4_4')->label('4.4')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.8_4')->label('8.4')->rows(2)->autosize()->disabled(),

                                    // Fila 13
                                    Forms\Components\Textarea::make('dx.3_5')->label('3.5')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.7_5')->label('7.5')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.4_5')->label('4.5')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Textarea::make('dx.8_5')->label('8.5')->rows(2)->autosize()->disabled(),

                                    // Fila 14
                                    Forms\Components\Textarea::make('dx.3_6')->label('3.6')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Placeholder::make('gap_v7')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Placeholder::make('gap_v8')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Textarea::make('dx.4_6')->label('4.6')->rows(2)->autosize()->disabled(),

                                    // Fila 15
                                    Forms\Components\Textarea::make('dx.3_7')->label('3.7')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Placeholder::make('gap_v9')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Placeholder::make('gap_v10')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Textarea::make('dx.4_7')->label('4.7')->rows(2)->autosize()->disabled(),

                                    // Fila 16
                                    Forms\Components\Textarea::make('dx.3_8')->label('3.8')->rows(2)->autosize()->disabled(),
                                    Forms\Components\Placeholder::make('gap_v11')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Placeholder::make('gap_v12')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Forms\Components\Textarea::make('dx.4_8')->label('4.8')->rows(2)->autosize()->disabled(),
                                ]),
                            ]),
                    ])
                    ->fillForm(function (Model $record): array {
                        // Mapea detalles -> dx.*
                        $map = $record->detalles()
                            ->pluck('diagnostico', 'pieza')
                            ->mapWithKeys(fn($v, $k) => [str_replace('.', '_', $k) => $v])
                            ->toArray();

                        return [
                            'fecha'                => $record->fecha,
                            'limpieza_periodontal' => $record->limpieza_periodontal,
                            'fluor'                => $record->fluor,
                            'observaciones'        => $record->observaciones,
                            'dx'                   => $map,
                        ];
                    }),


                Tables\Actions\EditAction::make()
                    // Prefill dx.* con lo que haya en la relación
                    ->fillForm(function (Model $record): array {
                        // Mapa para los diagnósticos (dx.*)
                        $map = $record->detalles()
                            ->pluck('diagnostico', 'pieza')
                            ->mapWithKeys(fn($v, $k) => [str_replace('.', '_', $k) => $v])
                            ->toArray();

                        // Prefill del formulario: fecha = hoy, y los demás campos desde el registro
                        return [
                            'fecha'                => now(), // <-- hoy
                            'limpieza_periodontal' => $record->limpieza_periodontal,
                            'fluor'                => $record->fluor,
                            'observaciones'        => $record->observaciones,
                            'dx'                   => $map,
                        ];
                    })

                    // Guardar directo leyendo $data['dx'] (sin payload extra)
                    ->after(function (Model $record, array $data) {
                        $dx = $data['dx'] ?? [];

                        foreach ($dx as $key => $val) {
                            $pieza = str_replace('_', '.', $key);

                            // Si está vacío, elimina el detalle de esa pieza
                            if ($val === null || $val === '') {
                                $record->detalles()->where('pieza', $pieza)->delete();
                                continue;
                            }

                            // Crea/actualiza el diagnóstico de esa pieza
                            $record->detalles()->updateOrCreate(
                                ['pieza' => $pieza],
                                ['diagnostico' => $val]
                            );
                        }
                    }),

                Tables\Actions\DeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

}
