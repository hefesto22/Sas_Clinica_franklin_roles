<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Components\Group;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Checkbox;



class EvaluacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluaciones';
    protected static ?string $title = 'Evaluaciones (Hojas)';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('Datos generales')
                ->columns(4)
                ->schema([
                    DatePicker::make('fecha')
                        ->label('Fecha')
                        ->required()
                        ->default(now()),
                    TextInput::make('limpieza_periodontal')
                        ->label('Limpieza periodontal')
                        ->maxLength(255),
                    TextInput::make('fluor')
                        ->label('Flúor')
                        ->maxLength(255),
                    Textarea::make('observaciones')
                        ->label('Observaciones')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            \Filament\Schemas\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')
                ->schema([
                    \Filament\Schemas\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                        // Fila 1
                        Textarea::make('dx.1_8')->label('1.8')->rows(2),
                        Placeholder::make('gap_1')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_2')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Textarea::make('dx.2_8')->label('2.8')->rows(2),

                        // Fila 2
                        Textarea::make('dx.1_7')->label('1.7')->rows(2),
                        Placeholder::make('gap_3')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_4')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Textarea::make('dx.2_7')->label('2.7')->rows(2),

                        // Fila 3
                        Textarea::make('dx.1_6')->label('1.6')->rows(2),
                        Placeholder::make('gap_5')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_6')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Textarea::make('dx.2_6')->label('2.6')->rows(2),

                        // Fila 4
                        Textarea::make('dx.1_5')->label('1.5')->rows(2),
                        Textarea::make('dx.5_5')->label('5.5')->rows(2),
                        Textarea::make('dx.2_5')->label('2.5')->rows(2),
                        Textarea::make('dx.6_5')->label('6.5')->rows(2),

                        // Fila 5
                        Textarea::make('dx.1_4')->label('1.4')->rows(2),
                        Textarea::make('dx.5_4')->label('5.4')->rows(2),
                        Textarea::make('dx.2_4')->label('2.4')->rows(2),
                        Textarea::make('dx.6_4')->label('6.4')->rows(2),

                        // Fila 6
                        Textarea::make('dx.1_3')->label('1.3')->rows(2),
                        Textarea::make('dx.5_3')->label('5.3')->rows(2),
                        Textarea::make('dx.2_3')->label('2.3')->rows(2),
                        Textarea::make('dx.6_3')->label('6.3')->rows(2),

                        // Fila 7
                        Textarea::make('dx.1_2')->label('1.2')->rows(2),
                        Textarea::make('dx.5_2')->label('5.2')->rows(2),
                        Textarea::make('dx.2_2')->label('2.2')->rows(2),
                        Textarea::make('dx.6_2')->label('6.2')->rows(2),

                        // Fila 8
                        Textarea::make('dx.1_1')->label('1.1')->rows(2),
                        Textarea::make('dx.5_1')->label('5.1')->rows(2),
                        Textarea::make('dx.2_1')->label('2.1')->rows(2),
                        Textarea::make('dx.6_1')->label('6.1')->rows(2),

                        // Fila 9
                        Textarea::make('dx.3_1')->label('3.1')->rows(2),
                        Textarea::make('dx.7_1')->label('7.1')->rows(2),
                        Textarea::make('dx.4_1')->label('4.1')->rows(2),
                        Textarea::make('dx.8_1')->label('8.1')->rows(2),

                        // Fila 10
                        Textarea::make('dx.3_2')->label('3.2')->rows(2),
                        Textarea::make('dx.7_2')->label('7.2')->rows(2),
                        Textarea::make('dx.4_2')->label('4.2')->rows(2),
                        Textarea::make('dx.8_2')->label('8.2')->rows(2),

                        // Fila 11
                        Textarea::make('dx.3_3')->label('3.3')->rows(2),
                        Textarea::make('dx.7_3')->label('7.3')->rows(2),
                        Textarea::make('dx.4_3')->label('4.3')->rows(2),
                        Textarea::make('dx.8_3')->label('8.3')->rows(2),

                        // Fila 12
                        Textarea::make('dx.3_4')->label('3.4')->rows(2),
                        Textarea::make('dx.7_4')->label('7.4')->rows(2),
                        Textarea::make('dx.4_4')->label('4.4')->rows(2),
                        Textarea::make('dx.8_4')->label('8.4')->rows(2),

                        // Fila 13
                        Textarea::make('dx.3_5')->label('3.5')->rows(2),
                        Textarea::make('dx.7_5')->label('7.5')->rows(2),
                        Textarea::make('dx.4_5')->label('4.5')->rows(2),
                        Textarea::make('dx.8_5')->label('8.5')->rows(2),

                        // Fila 14
                        Textarea::make('dx.3_6')->label('3.6')->rows(2),
                        Placeholder::make('gap_7')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_8')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Textarea::make('dx.4_6')->label('4.6')->rows(2),

                        // Fila 15
                        Textarea::make('dx.3_7')->label('3.7')->rows(2),
                        Placeholder::make('gap_9')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_10')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Textarea::make('dx.4_7')->label('4.7')->rows(2),

                        // Fila 16
                        Textarea::make('dx.3_8')->label('3.8')->rows(2),
                        Placeholder::make('gap_11')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_12')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Textarea::make('dx.4_8')->label('4.8')->rows(2),
                    ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')->label('Fecha')->date()->sortable()->searchable(),
                TextColumn::make('limpieza_periodontal')->label('Limpieza'),
                TextColumn::make('fluor')->label('Flúor'),
                TextColumn::make('detalles_count')->label('# Piezas')->counts('detalles'),
                TextColumn::make('created_at')->since()->label('Creada'),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva evaluación')

                    // 1) dx.* → detalles_payload (se hace en la acción)
                    ->mutateDataUsing(function (array $data): array {
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
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Vista de evaluación')
                    ->schema([
                        // Datos generales
                        \Filament\Schemas\Components\Section::make('Datos generales')
                            ->columns(4)
                            ->schema([
                                DatePicker::make('fecha')->label('Fecha')->disabled(),
                                TextInput::make('limpieza_periodontal')->label('Limpieza periodontal')->disabled(),
                                TextInput::make('fluor')->label('Flúor')->disabled(),
                                Textarea::make('observaciones')->label('Observaciones')->rows(2)->disabled()->columnSpanFull(),
                            ]),

                        // Diagnóstico (idéntico a tu form, pero deshabilitado y autosize)
                        \Filament\Schemas\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                                    // Fila 1
                                    Group::make()->schema([
                                        Textarea::make('dx.1_8')->label('1.8')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_8')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v1')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v2')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_8')->label('2.8')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_8')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 2
                                    Group::make()->schema([
                                        Textarea::make('dx.1_7')->label('1.7')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_7')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v3')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v4')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_7')->label('2.7')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_7')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 3
                                    Group::make()->schema([
                                        Textarea::make('dx.1_6')->label('1.6')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_6')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v5')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v6')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_6')->label('2.6')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_6')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 4
                                    Group::make()->schema([
                                        Textarea::make('dx.1_5')->label('1.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_5')->label('5.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.5_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_5')->label('2.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_5')->label('6.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.6_5')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 5
                                    Group::make()->schema([
                                        Textarea::make('dx.1_4')->label('1.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_4')->label('5.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.5_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_4')->label('2.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_4')->label('6.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.6_4')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 6
                                    Group::make()->schema([
                                        Textarea::make('dx.1_3')->label('1.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_3')->label('5.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.5_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_3')->label('2.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_3')->label('6.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.6_3')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 7
                                    Group::make()->schema([
                                        Textarea::make('dx.1_2')->label('1.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_2')->label('5.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.5_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_2')->label('2.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_2')->label('6.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.6_2')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 8
                                    Group::make()->schema([
                                        Textarea::make('dx.1_1')->label('1.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.1_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_1')->label('5.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.5_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_1')->label('2.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.2_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_1')->label('6.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.6_1')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 9
                                    Group::make()->schema([
                                        Textarea::make('dx.3_1')->label('3.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_1')->label('7.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.7_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_1')->label('4.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_1')->label('8.1')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.8_1')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 10
                                    Group::make()->schema([
                                        Textarea::make('dx.3_2')->label('3.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_2')->label('7.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.7_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_2')->label('4.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_2')->label('8.2')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.8_2')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 11
                                    Group::make()->schema([
                                        Textarea::make('dx.3_3')->label('3.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_3')->label('7.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.7_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_3')->label('4.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_3')->label('8.3')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.8_3')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 12
                                    Group::make()->schema([
                                        Textarea::make('dx.3_4')->label('3.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_4')->label('7.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.7_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_4')->label('4.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_4')->label('8.4')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.8_4')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 13
                                    Group::make()->schema([
                                        Textarea::make('dx.3_5')->label('3.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_5')->label('7.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.7_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_5')->label('4.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_5')->label('8.5')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.8_5')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 14
                                    Group::make()->schema([
                                        Textarea::make('dx.3_6')->label('3.6')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_6')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v7')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v8')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_6')->label('4.6')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_6')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 15
                                    Group::make()->schema([
                                        Textarea::make('dx.3_7')->label('3.7')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_7')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v9')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v10')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_7')->label('4.7')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_7')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 16
                                    Group::make()->schema([
                                        Textarea::make('dx.3_8')->label('3.8')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.3_8')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v11')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v12')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_8')->label('4.8')->rows(2)->autosize()->disabled(),
                                        Checkbox::make('done.4_8')->label('Hecho')->disabled(),
                                    ]),
                                ]),
                            ]),

                    ])
                    ->fillForm(function (Model $record): array {
                        // mapear diagnostico y hecho
                        $detalles = $record->detalles()->get(['pieza', 'diagnostico', 'hecho']);

                        $mapDx = [];
                        $mapDone = [];
                        foreach ($detalles as $d) {
                            $key = str_replace('.', '_', $d->pieza); // 1.8 -> 1_8
                            $mapDx[$key]   = $d->diagnostico;
                            $mapDone[$key] = (bool) $d->hecho;       // <- estado del checkbox
                        }

                        return [
                            'fecha'                => $record->fecha,
                            'limpieza_periodontal' => $record->limpieza_periodontal,
                            'fluor'                => $record->fluor,
                            'observaciones'        => $record->observaciones,
                            'dx'                   => $mapDx,
                            'done'                 => $mapDone,      // <- IMPORTANTE
                        ];
                    }),


                EditAction::make()
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Datos generales')
                            ->columns(4)
                            ->schema([
                                DatePicker::make('fecha')->label('Fecha')->required(),
                                TextInput::make('limpieza_periodontal')->label('Limpieza periodontal')->maxLength(255),
                                TextInput::make('fluor')->label('Flúor')->maxLength(255),
                                Textarea::make('observaciones')->label('Observaciones')->rows(2)->columnSpanFull(),
                            ]),

                        \Filament\Schemas\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                                    // Fila 1
                                    Group::make()->schema([
                                        Textarea::make('dx.1_8')->label('1.8')->rows(2),
                                        Checkbox::make('done.1_8')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e1')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e2')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_8')->label('2.8')->rows(2),
                                        Checkbox::make('done.2_8')->label('Hecho'),
                                    ]),

                                    // Fila 2
                                    Group::make()->schema([
                                        Textarea::make('dx.1_7')->label('1.7')->rows(2),
                                        Checkbox::make('done.1_7')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e3')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e4')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_7')->label('2.7')->rows(2),
                                        Checkbox::make('done.2_7')->label('Hecho'),
                                    ]),

                                    // Fila 3
                                    Group::make()->schema([
                                        Textarea::make('dx.1_6')->label('1.6')->rows(2),
                                        Checkbox::make('done.1_6')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e5')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e6')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_6')->label('2.6')->rows(2),
                                        Checkbox::make('done.2_6')->label('Hecho'),
                                    ]),

                                    // Fila 4
                                    Group::make()->schema([
                                        Textarea::make('dx.1_5')->label('1.5')->rows(2),
                                        Checkbox::make('done.1_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_5')->label('5.5')->rows(2),
                                        Checkbox::make('done.5_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_5')->label('2.5')->rows(2),
                                        Checkbox::make('done.2_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_5')->label('6.5')->rows(2),
                                        Checkbox::make('done.6_5')->label('Hecho'),
                                    ]),

                                    // Fila 5
                                    Group::make()->schema([
                                        Textarea::make('dx.1_4')->label('1.4')->rows(2),
                                        Checkbox::make('done.1_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_4')->label('5.4')->rows(2),
                                        Checkbox::make('done.5_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_4')->label('2.4')->rows(2),
                                        Checkbox::make('done.2_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_4')->label('6.4')->rows(2),
                                        Checkbox::make('done.6_4')->label('Hecho'),
                                    ]),

                                    // Fila 6
                                    Group::make()->schema([
                                        Textarea::make('dx.1_3')->label('1.3')->rows(2),
                                        Checkbox::make('done.1_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_3')->label('5.3')->rows(2),
                                        Checkbox::make('done.5_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_3')->label('2.3')->rows(2),
                                        Checkbox::make('done.2_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_3')->label('6.3')->rows(2),
                                        Checkbox::make('done.6_3')->label('Hecho'),
                                    ]),

                                    // Fila 7
                                    Group::make()->schema([
                                        Textarea::make('dx.1_2')->label('1.2')->rows(2),
                                        Checkbox::make('done.1_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_2')->label('5.2')->rows(2),
                                        Checkbox::make('done.5_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_2')->label('2.2')->rows(2),
                                        Checkbox::make('done.2_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_2')->label('6.2')->rows(2),
                                        Checkbox::make('done.6_2')->label('Hecho'),
                                    ]),

                                    // Fila 8
                                    Group::make()->schema([
                                        Textarea::make('dx.1_1')->label('1.1')->rows(2),
                                        Checkbox::make('done.1_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.5_1')->label('5.1')->rows(2),
                                        Checkbox::make('done.5_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.2_1')->label('2.1')->rows(2),
                                        Checkbox::make('done.2_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.6_1')->label('6.1')->rows(2),
                                        Checkbox::make('done.6_1')->label('Hecho'),
                                    ]),

                                    // Fila 9
                                    Group::make()->schema([
                                        Textarea::make('dx.3_1')->label('3.1')->rows(2),
                                        Checkbox::make('done.3_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_1')->label('7.1')->rows(2),
                                        Checkbox::make('done.7_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_1')->label('4.1')->rows(2),
                                        Checkbox::make('done.4_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_1')->label('8.1')->rows(2),
                                        Checkbox::make('done.8_1')->label('Hecho'),
                                    ]),

                                    // Fila 10
                                    Group::make()->schema([
                                        Textarea::make('dx.3_2')->label('3.2')->rows(2),
                                        Checkbox::make('done.3_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_2')->label('7.2')->rows(2),
                                        Checkbox::make('done.7_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_2')->label('4.2')->rows(2),
                                        Checkbox::make('done.4_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_2')->label('8.2')->rows(2),
                                        Checkbox::make('done.8_2')->label('Hecho'),
                                    ]),

                                    // Fila 11
                                    Group::make()->schema([
                                        Textarea::make('dx.3_3')->label('3.3')->rows(2),
                                        Checkbox::make('done.3_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_3')->label('7.3')->rows(2),
                                        Checkbox::make('done.7_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_3')->label('4.3')->rows(2),
                                        Checkbox::make('done.4_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_3')->label('8.3')->rows(2),
                                        Checkbox::make('done.8_3')->label('Hecho'),
                                    ]),

                                    // Fila 12
                                    Group::make()->schema([
                                        Textarea::make('dx.3_4')->label('3.4')->rows(2),
                                        Checkbox::make('done.3_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_4')->label('7.4')->rows(2),
                                        Checkbox::make('done.7_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_4')->label('4.4')->rows(2),
                                        Checkbox::make('done.4_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_4')->label('8.4')->rows(2),
                                        Checkbox::make('done.8_4')->label('Hecho'),
                                    ]),

                                    // Fila 13
                                    Group::make()->schema([
                                        Textarea::make('dx.3_5')->label('3.5')->rows(2),
                                        Checkbox::make('done.3_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.7_5')->label('7.5')->rows(2),
                                        Checkbox::make('done.7_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_5')->label('4.5')->rows(2),
                                        Checkbox::make('done.4_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        Textarea::make('dx.8_5')->label('8.5')->rows(2),
                                        Checkbox::make('done.8_5')->label('Hecho'),
                                    ]),

                                    // Fila 14
                                    Group::make()->schema([
                                        Textarea::make('dx.3_6')->label('3.6')->rows(2),
                                        Checkbox::make('done.3_6')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e7')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e8')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_6')->label('4.6')->rows(2),
                                        Checkbox::make('done.4_6')->label('Hecho'),
                                    ]),

                                    // Fila 15
                                    Group::make()->schema([
                                        Textarea::make('dx.3_7')->label('3.7')->rows(2),
                                        Checkbox::make('done.3_7')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e9')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e10')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_7')->label('4.7')->rows(2),
                                        Checkbox::make('done.4_7')->label('Hecho'),
                                    ]),

                                    // Fila 16
                                    Group::make()->schema([
                                        Textarea::make('dx.3_8')->label('3.8')->rows(2),
                                        Checkbox::make('done.3_8')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e11')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e12')->label('')->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        Textarea::make('dx.4_8')->label('4.8')->rows(2),
                                        Checkbox::make('done.4_8')->label('Hecho'),
                                    ]),
                                ]),
                            ]),
                    ])

                    // ====== ya tenías esto actualizado ======
                    ->fillForm(function (Model $record): array {
                        $detalles = $record->detalles()->get(['pieza', 'diagnostico', 'hecho']);

                        $mapDx = [];
                        $mapDone = [];
                        foreach ($detalles as $d) {
                            $k = str_replace('.', '_', $d->pieza);
                            $mapDx[$k]   = $d->diagnostico;
                            $mapDone[$k] = (bool) $d->hecho;
                        }

                        return [
                            'fecha'                => $record->fecha, // <- mantener la fecha original
                            'limpieza_periodontal' => $record->limpieza_periodontal,
                            'fluor'                => $record->fluor,
                            'observaciones'        => $record->observaciones,
                            'dx'                   => $mapDx,
                            'done'                 => $mapDone,
                        ];
                    })
                    ->after(function (Model $record, array $data) {
                        $dx   = $data['dx']   ?? [];
                        $done = $data['done'] ?? [];

                        foreach ($dx as $key => $val) {
                            $pieza = str_replace('_', '.', $key);

                            if ($val === null || $val === '') {
                                $record->detalles()->where('pieza', $pieza)->delete();
                                continue;
                            }

                            $record->detalles()->updateOrCreate(
                                ['pieza' => $pieza],
                                ['diagnostico' => $val, 'hecho' => (bool) ($done[$key] ?? false)]
                            );
                        }
                    }),


                DeleteAction::make(),
            ])

            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
