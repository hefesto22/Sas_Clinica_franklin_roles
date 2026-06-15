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
            \Filament\Schemas\Components\Section::make('Datos generales')->columnSpanFull()
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

            \Filament\Schemas\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')->columnSpanFull()
                ->schema([
                    \Filament\Schemas\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                        // Fila 1
                        TextInput::make('dx.1_8')->label('1.8')->inlineLabel(),
                        Placeholder::make('gap_1')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_2')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        TextInput::make('dx.2_8')->label('2.8')->inlineLabel(),

                        // Fila 2
                        TextInput::make('dx.1_7')->label('1.7')->inlineLabel(),
                        Placeholder::make('gap_3')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_4')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        TextInput::make('dx.2_7')->label('2.7')->inlineLabel(),

                        // Fila 3
                        TextInput::make('dx.1_6')->label('1.6')->inlineLabel(),
                        Placeholder::make('gap_5')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_6')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        TextInput::make('dx.2_6')->label('2.6')->inlineLabel(),

                        // Fila 4
                        TextInput::make('dx.1_5')->label('1.5')->inlineLabel(),
                        TextInput::make('dx.5_5')->label('5.5')->inlineLabel(),
                        TextInput::make('dx.2_5')->label('2.5')->inlineLabel(),
                        TextInput::make('dx.6_5')->label('6.5')->inlineLabel(),

                        // Fila 5
                        TextInput::make('dx.1_4')->label('1.4')->inlineLabel(),
                        TextInput::make('dx.5_4')->label('5.4')->inlineLabel(),
                        TextInput::make('dx.2_4')->label('2.4')->inlineLabel(),
                        TextInput::make('dx.6_4')->label('6.4')->inlineLabel(),

                        // Fila 6
                        TextInput::make('dx.1_3')->label('1.3')->inlineLabel(),
                        TextInput::make('dx.5_3')->label('5.3')->inlineLabel(),
                        TextInput::make('dx.2_3')->label('2.3')->inlineLabel(),
                        TextInput::make('dx.6_3')->label('6.3')->inlineLabel(),

                        // Fila 7
                        TextInput::make('dx.1_2')->label('1.2')->inlineLabel(),
                        TextInput::make('dx.5_2')->label('5.2')->inlineLabel(),
                        TextInput::make('dx.2_2')->label('2.2')->inlineLabel(),
                        TextInput::make('dx.6_2')->label('6.2')->inlineLabel(),

                        // Fila 8
                        TextInput::make('dx.1_1')->label('1.1')->inlineLabel(),
                        TextInput::make('dx.5_1')->label('5.1')->inlineLabel(),
                        TextInput::make('dx.2_1')->label('2.1')->inlineLabel(),
                        TextInput::make('dx.6_1')->label('6.1')->inlineLabel(),

                        // Fila 9
                        TextInput::make('dx.3_1')->label('3.1')->inlineLabel(),
                        TextInput::make('dx.7_1')->label('7.1')->inlineLabel(),
                        TextInput::make('dx.4_1')->label('4.1')->inlineLabel(),
                        TextInput::make('dx.8_1')->label('8.1')->inlineLabel(),

                        // Fila 10
                        TextInput::make('dx.3_2')->label('3.2')->inlineLabel(),
                        TextInput::make('dx.7_2')->label('7.2')->inlineLabel(),
                        TextInput::make('dx.4_2')->label('4.2')->inlineLabel(),
                        TextInput::make('dx.8_2')->label('8.2')->inlineLabel(),

                        // Fila 11
                        TextInput::make('dx.3_3')->label('3.3')->inlineLabel(),
                        TextInput::make('dx.7_3')->label('7.3')->inlineLabel(),
                        TextInput::make('dx.4_3')->label('4.3')->inlineLabel(),
                        TextInput::make('dx.8_3')->label('8.3')->inlineLabel(),

                        // Fila 12
                        TextInput::make('dx.3_4')->label('3.4')->inlineLabel(),
                        TextInput::make('dx.7_4')->label('7.4')->inlineLabel(),
                        TextInput::make('dx.4_4')->label('4.4')->inlineLabel(),
                        TextInput::make('dx.8_4')->label('8.4')->inlineLabel(),

                        // Fila 13
                        TextInput::make('dx.3_5')->label('3.5')->inlineLabel(),
                        TextInput::make('dx.7_5')->label('7.5')->inlineLabel(),
                        TextInput::make('dx.4_5')->label('4.5')->inlineLabel(),
                        TextInput::make('dx.8_5')->label('8.5')->inlineLabel(),

                        // Fila 14
                        TextInput::make('dx.3_6')->label('3.6')->inlineLabel(),
                        Placeholder::make('gap_7')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_8')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        TextInput::make('dx.4_6')->label('4.6')->inlineLabel(),

                        // Fila 15
                        TextInput::make('dx.3_7')->label('3.7')->inlineLabel(),
                        Placeholder::make('gap_9')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_10')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        TextInput::make('dx.4_7')->label('4.7')->inlineLabel(),

                        // Fila 16
                        TextInput::make('dx.3_8')->label('3.8')->inlineLabel(),
                        Placeholder::make('gap_11')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        Placeholder::make('gap_12')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                        TextInput::make('dx.4_8')->label('4.8')->inlineLabel(),
                    ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // La evaluación dedicada al odontograma no es una "hoja": se oculta.
            ->modifyQueryUsing(fn ($query) => $query->where('es_odontograma', false))
            ->columns([
                TextColumn::make('fecha')->label('Fecha')->date()->sortable()->searchable(),
                TextColumn::make('limpieza_periodontal')->label('Limpieza'),
                TextColumn::make('fluor')->label('Flúor'),
                TextColumn::make('detalles_count')->label('# Piezas')->counts('detalles'),
                TextColumn::make('created_at')->since()->label('Creada'),
            ])
            ->filters([])
            ->headerActions([
                // Odontograma único del paciente (a nivel paciente, no por hoja).
                \Filament\Actions\Action::make('odontograma')
                    ->label('Odontograma')
                    ->icon('heroicon-o-face-smile')
                    ->color('info')
                    ->modalHeading('Odontograma del paciente')
                    ->modalWidth('5xl')
                    ->modalContent(fn () => view('filament.odontograma-modal', ['cliente' => $this->getOwnerRecord()]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                CreateAction::make()
                    ->label('Nueva evaluación')
                    ->modalWidth('7xl') // hoja completa: 4 cuadrantes lado a lado

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
                    ->modalWidth('7xl')
                    ->schema([
                        // Datos generales
                        \Filament\Schemas\Components\Section::make('Datos generales')->columnSpanFull()
                            ->columns(4)
                            ->schema([
                                DatePicker::make('fecha')->label('Fecha')->disabled(),
                                TextInput::make('limpieza_periodontal')->label('Limpieza periodontal')->disabled(),
                                TextInput::make('fluor')->label('Flúor')->disabled(),
                                Textarea::make('observaciones')->label('Observaciones')->rows(2)->disabled()->columnSpanFull(),
                            ]),

                        // Diagnóstico (idéntico a tu form, pero deshabilitado y autosize)
                        \Filament\Schemas\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')->columnSpanFull()
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                                    // Fila 1
                                    Group::make()->schema([
                                        TextInput::make('dx.1_8')->label('1.8')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_8')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v1')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v2')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_8')->label('2.8')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_8')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 2
                                    Group::make()->schema([
                                        TextInput::make('dx.1_7')->label('1.7')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_7')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v3')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v4')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_7')->label('2.7')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_7')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 3
                                    Group::make()->schema([
                                        TextInput::make('dx.1_6')->label('1.6')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_6')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v5')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v6')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_6')->label('2.6')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_6')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 4
                                    Group::make()->schema([
                                        TextInput::make('dx.1_5')->label('1.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_5')->label('5.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.5_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_5')->label('2.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_5')->label('6.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.6_5')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 5
                                    Group::make()->schema([
                                        TextInput::make('dx.1_4')->label('1.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_4')->label('5.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.5_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_4')->label('2.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_4')->label('6.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.6_4')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 6
                                    Group::make()->schema([
                                        TextInput::make('dx.1_3')->label('1.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_3')->label('5.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.5_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_3')->label('2.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_3')->label('6.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.6_3')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 7
                                    Group::make()->schema([
                                        TextInput::make('dx.1_2')->label('1.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_2')->label('5.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.5_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_2')->label('2.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_2')->label('6.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.6_2')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 8
                                    Group::make()->schema([
                                        TextInput::make('dx.1_1')->label('1.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.1_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_1')->label('5.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.5_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_1')->label('2.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.2_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_1')->label('6.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.6_1')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 9
                                    Group::make()->schema([
                                        TextInput::make('dx.3_1')->label('3.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_1')->label('7.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.7_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_1')->label('4.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.4_1')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_1')->label('8.1')->inlineLabel()->disabled(),
                                        Checkbox::make('done.8_1')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 10
                                    Group::make()->schema([
                                        TextInput::make('dx.3_2')->label('3.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_2')->label('7.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.7_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_2')->label('4.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.4_2')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_2')->label('8.2')->inlineLabel()->disabled(),
                                        Checkbox::make('done.8_2')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 11
                                    Group::make()->schema([
                                        TextInput::make('dx.3_3')->label('3.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_3')->label('7.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.7_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_3')->label('4.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.4_3')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_3')->label('8.3')->inlineLabel()->disabled(),
                                        Checkbox::make('done.8_3')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 12
                                    Group::make()->schema([
                                        TextInput::make('dx.3_4')->label('3.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_4')->label('7.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.7_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_4')->label('4.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.4_4')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_4')->label('8.4')->inlineLabel()->disabled(),
                                        Checkbox::make('done.8_4')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 13
                                    Group::make()->schema([
                                        TextInput::make('dx.3_5')->label('3.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_5')->label('7.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.7_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_5')->label('4.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.4_5')->label('Hecho')->disabled(),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_5')->label('8.5')->inlineLabel()->disabled(),
                                        Checkbox::make('done.8_5')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 14
                                    Group::make()->schema([
                                        TextInput::make('dx.3_6')->label('3.6')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_6')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v7')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v8')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_6')->label('4.6')->inlineLabel()->disabled(),
                                        Checkbox::make('done.4_6')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 15
                                    Group::make()->schema([
                                        TextInput::make('dx.3_7')->label('3.7')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_7')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v9')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v10')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_7')->label('4.7')->inlineLabel()->disabled(),
                                        Checkbox::make('done.4_7')->label('Hecho')->disabled(),
                                    ]),

                                    // Fila 16
                                    Group::make()->schema([
                                        TextInput::make('dx.3_8')->label('3.8')->inlineLabel()->disabled(),
                                        Checkbox::make('done.3_8')->label('Hecho')->disabled(),
                                    ]),
                                    Placeholder::make('gap_v11')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_v12')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_8')->label('4.8')->inlineLabel()->disabled(),
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
                    ->modalWidth('7xl')
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Datos generales')->columnSpanFull()
                            ->columns(4)
                            ->schema([
                                DatePicker::make('fecha')->label('Fecha')->required(),
                                TextInput::make('limpieza_periodontal')->label('Limpieza periodontal')->maxLength(255),
                                TextInput::make('fluor')->label('Flúor')->maxLength(255),
                                Textarea::make('observaciones')->label('Observaciones')->rows(2)->columnSpanFull(),
                            ]),

                        \Filament\Schemas\Components\Section::make('III. Diagnóstico clínico y radiográfico (formato de hoja)')->columnSpanFull()
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(['default' => 2, 'md' => 4])->schema([
                                    // Fila 1
                                    Group::make()->schema([
                                        TextInput::make('dx.1_8')->label('1.8')->inlineLabel(),
                                        Checkbox::make('done.1_8')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e1')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e2')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_8')->label('2.8')->inlineLabel(),
                                        Checkbox::make('done.2_8')->label('Hecho'),
                                    ]),

                                    // Fila 2
                                    Group::make()->schema([
                                        TextInput::make('dx.1_7')->label('1.7')->inlineLabel(),
                                        Checkbox::make('done.1_7')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e3')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e4')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_7')->label('2.7')->inlineLabel(),
                                        Checkbox::make('done.2_7')->label('Hecho'),
                                    ]),

                                    // Fila 3
                                    Group::make()->schema([
                                        TextInput::make('dx.1_6')->label('1.6')->inlineLabel(),
                                        Checkbox::make('done.1_6')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e5')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e6')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_6')->label('2.6')->inlineLabel(),
                                        Checkbox::make('done.2_6')->label('Hecho'),
                                    ]),

                                    // Fila 4
                                    Group::make()->schema([
                                        TextInput::make('dx.1_5')->label('1.5')->inlineLabel(),
                                        Checkbox::make('done.1_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_5')->label('5.5')->inlineLabel(),
                                        Checkbox::make('done.5_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_5')->label('2.5')->inlineLabel(),
                                        Checkbox::make('done.2_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_5')->label('6.5')->inlineLabel(),
                                        Checkbox::make('done.6_5')->label('Hecho'),
                                    ]),

                                    // Fila 5
                                    Group::make()->schema([
                                        TextInput::make('dx.1_4')->label('1.4')->inlineLabel(),
                                        Checkbox::make('done.1_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_4')->label('5.4')->inlineLabel(),
                                        Checkbox::make('done.5_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_4')->label('2.4')->inlineLabel(),
                                        Checkbox::make('done.2_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_4')->label('6.4')->inlineLabel(),
                                        Checkbox::make('done.6_4')->label('Hecho'),
                                    ]),

                                    // Fila 6
                                    Group::make()->schema([
                                        TextInput::make('dx.1_3')->label('1.3')->inlineLabel(),
                                        Checkbox::make('done.1_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_3')->label('5.3')->inlineLabel(),
                                        Checkbox::make('done.5_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_3')->label('2.3')->inlineLabel(),
                                        Checkbox::make('done.2_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_3')->label('6.3')->inlineLabel(),
                                        Checkbox::make('done.6_3')->label('Hecho'),
                                    ]),

                                    // Fila 7
                                    Group::make()->schema([
                                        TextInput::make('dx.1_2')->label('1.2')->inlineLabel(),
                                        Checkbox::make('done.1_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_2')->label('5.2')->inlineLabel(),
                                        Checkbox::make('done.5_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_2')->label('2.2')->inlineLabel(),
                                        Checkbox::make('done.2_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_2')->label('6.2')->inlineLabel(),
                                        Checkbox::make('done.6_2')->label('Hecho'),
                                    ]),

                                    // Fila 8
                                    Group::make()->schema([
                                        TextInput::make('dx.1_1')->label('1.1')->inlineLabel(),
                                        Checkbox::make('done.1_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.5_1')->label('5.1')->inlineLabel(),
                                        Checkbox::make('done.5_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.2_1')->label('2.1')->inlineLabel(),
                                        Checkbox::make('done.2_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.6_1')->label('6.1')->inlineLabel(),
                                        Checkbox::make('done.6_1')->label('Hecho'),
                                    ]),

                                    // Fila 9
                                    Group::make()->schema([
                                        TextInput::make('dx.3_1')->label('3.1')->inlineLabel(),
                                        Checkbox::make('done.3_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_1')->label('7.1')->inlineLabel(),
                                        Checkbox::make('done.7_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_1')->label('4.1')->inlineLabel(),
                                        Checkbox::make('done.4_1')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_1')->label('8.1')->inlineLabel(),
                                        Checkbox::make('done.8_1')->label('Hecho'),
                                    ]),

                                    // Fila 10
                                    Group::make()->schema([
                                        TextInput::make('dx.3_2')->label('3.2')->inlineLabel(),
                                        Checkbox::make('done.3_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_2')->label('7.2')->inlineLabel(),
                                        Checkbox::make('done.7_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_2')->label('4.2')->inlineLabel(),
                                        Checkbox::make('done.4_2')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_2')->label('8.2')->inlineLabel(),
                                        Checkbox::make('done.8_2')->label('Hecho'),
                                    ]),

                                    // Fila 11
                                    Group::make()->schema([
                                        TextInput::make('dx.3_3')->label('3.3')->inlineLabel(),
                                        Checkbox::make('done.3_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_3')->label('7.3')->inlineLabel(),
                                        Checkbox::make('done.7_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_3')->label('4.3')->inlineLabel(),
                                        Checkbox::make('done.4_3')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_3')->label('8.3')->inlineLabel(),
                                        Checkbox::make('done.8_3')->label('Hecho'),
                                    ]),

                                    // Fila 12
                                    Group::make()->schema([
                                        TextInput::make('dx.3_4')->label('3.4')->inlineLabel(),
                                        Checkbox::make('done.3_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_4')->label('7.4')->inlineLabel(),
                                        Checkbox::make('done.7_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_4')->label('4.4')->inlineLabel(),
                                        Checkbox::make('done.4_4')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_4')->label('8.4')->inlineLabel(),
                                        Checkbox::make('done.8_4')->label('Hecho'),
                                    ]),

                                    // Fila 13
                                    Group::make()->schema([
                                        TextInput::make('dx.3_5')->label('3.5')->inlineLabel(),
                                        Checkbox::make('done.3_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.7_5')->label('7.5')->inlineLabel(),
                                        Checkbox::make('done.7_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_5')->label('4.5')->inlineLabel(),
                                        Checkbox::make('done.4_5')->label('Hecho'),
                                    ]),
                                    Group::make()->schema([
                                        TextInput::make('dx.8_5')->label('8.5')->inlineLabel(),
                                        Checkbox::make('done.8_5')->label('Hecho'),
                                    ]),

                                    // Fila 14
                                    Group::make()->schema([
                                        TextInput::make('dx.3_6')->label('3.6')->inlineLabel(),
                                        Checkbox::make('done.3_6')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e7')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e8')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_6')->label('4.6')->inlineLabel(),
                                        Checkbox::make('done.4_6')->label('Hecho'),
                                    ]),

                                    // Fila 15
                                    Group::make()->schema([
                                        TextInput::make('dx.3_7')->label('3.7')->inlineLabel(),
                                        Checkbox::make('done.3_7')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e9')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e10')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_7')->label('4.7')->inlineLabel(),
                                        Checkbox::make('done.4_7')->label('Hecho'),
                                    ]),

                                    // Fila 16
                                    Group::make()->schema([
                                        TextInput::make('dx.3_8')->label('3.8')->inlineLabel(),
                                        Checkbox::make('done.3_8')->label('Hecho'),
                                    ]),
                                    Placeholder::make('gap_e11')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Placeholder::make('gap_e12')->hiddenLabel()->content(' ')->extraAttributes(['class' => 'hidden md:block']),
                                    Group::make()->schema([
                                        TextInput::make('dx.4_8')->label('4.8')->inlineLabel(),
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
