<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EvaluacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluaciones';
    protected static ?string $title = 'Hoja de evaluación';

    /** Datos generales de la hoja (el diagnóstico por diente va en su propia pantalla). */
    protected function camposDatosGenerales(bool $disabled = false): array
    {
        return [
            Section::make('Datos generales')->columnSpanFull()->columns(4)->schema([
                DatePicker::make('fecha')->label('Fecha')->required(! $disabled)->disabled($disabled)->default(now()),
                TextInput::make('limpieza_periodontal')->label('Limpieza periodontal')->maxLength(255)->disabled($disabled),
                TextInput::make('fluor')->label('Flúor')->maxLength(255)->disabled($disabled),
                Textarea::make('observaciones')->label('Observaciones')->rows(2)->columnSpanFull()->disabled($disabled),
            ]),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->camposDatosGenerales());
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
                // Ficha clínica del paciente: odontograma + hoja en un solo modal con pestañas.
                Action::make('fichaClinica')
                    ->label('Ficha clínica (Odontograma + Hoja)')
                    ->icon('heroicon-o-face-smile')
                    ->color('info')
                    ->modalHeading('Ficha clínica del paciente')
                    ->modalWidth('7xl')
                    ->modalContent(fn () => view('filament.ficha-clinica-modal', ['cliente' => $this->getOwnerRecord()]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->recordActions([
                // Diagnóstico por diente: grilla interactiva (clic en el diente → panel).
                Action::make('diagnostico')
                    ->label('Diagnóstico')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('success')
                    ->modalHeading(fn (Model $record): string => 'Diagnóstico — hoja del ' . \Illuminate\Support\Carbon::parse($record->fecha)->format('d/m/Y'))
                    ->modalWidth('4xl')
                    ->modalContent(fn (Model $record) => view('filament.hoja-diagnostico-modal', ['hoja' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                ViewAction::make()
                    ->modalHeading('Vista de evaluación')
                    ->modalWidth('2xl')
                    ->schema($this->camposDatosGenerales(disabled: true)),

                EditAction::make()
                    ->modalWidth('2xl')
                    ->schema($this->camposDatosGenerales()),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
