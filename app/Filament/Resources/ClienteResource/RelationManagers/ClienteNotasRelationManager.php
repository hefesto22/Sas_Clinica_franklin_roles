<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ClienteNotasRelationManager extends RelationManager
{
    protected static string $relationship = 'notas';
    protected static ?string $title = 'Notas rápidas';
    // Sin recordTitleAttribute: evita que el modal use la nota completa como título.

    /**
     * Badge con el número de notas PENDIENTES (no hechas) en la pestaña:
     * así, al abrir el paciente, lo que falta hacer está siempre a la vista.
     */
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $pendientes = $ownerRecord->notas()->whereNull('hecha_en')->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'warning';
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        return 'Notas pendientes';
    }

    public function form(Schema $schema): Schema
    {
        // Alta rápida y edición cómoda: el campo crece con el texto.
        return $schema->components([
            Textarea::make('contenido')
                ->label('Nota')
                ->rows(5)
                ->autosize()
                ->required()
                ->maxLength(2000)
                ->placeholder('Escribí la nota del paciente…')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Pendientes primero; las hechas al fondo. Dentro, lo más reciente arriba.
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw('hecha_en is not null')->orderByDesc('created_at'))
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('hecha_en')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Hecha · ' . $state->format('d/m/Y') : 'Pendiente')
                            ->color(fn ($state) => $state ? 'success' : 'warning')
                            ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-bell-alert')
                            ->grow(false),

                        TextColumn::make('created_at')
                            ->since()
                            ->color('gray')
                            ->alignEnd(),
                    ]),

                    TextColumn::make('contenido')
                        ->weight(FontWeight::Medium)
                        ->wrap()
                        ->lineClamp(4) // notas largas: máx 4 líneas, el resto en el tooltip
                        ->tooltip(fn ($record) => $record->contenido)
                        // Corta palabras larguísimas sin espacios para que no desborden la tarjeta.
                        ->extraAttributes(['style' => 'word-break: break-word; overflow-wrap: anywhere;'])
                        ->color(fn ($record) => $record->hecha_en ? 'gray' : null)
                        ->searchable(),

                    TextColumn::make('creador.name')
                        ->label('')
                        ->prefix('Por ')
                        ->icon('heroicon-m-user')
                        ->color('gray')
                        ->placeholder('—'),
                ])->space(2),
            ])
            ->contentGrid(['default' => 1, 'md' => 2])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva nota')
                    ->icon('heroicon-m-plus')
                    ->modalHeading('Nueva nota rápida')
                    ->modalWidth('lg'),
            ])
            ->recordActions([
                Action::make('marcar_hecha')
                    ->label('Marcar hecha')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Model $record) => blank($record->hecha_en))
                    ->action(fn (Model $record) => $record->update(['hecha_en' => now()])),

                Action::make('reabrir')
                    ->label('Reabrir')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Model $record) => filled($record->hecha_en))
                    ->action(fn (Model $record) => $record->update(['hecha_en' => null])),

                EditAction::make()
                    ->iconButton()
                    ->icon('heroicon-m-pencil-square')
                    ->modalHeading('Editar nota')
                    ->modalWidth('lg'),
                DeleteAction::make()
                    ->iconButton()
                    ->icon('heroicon-m-trash')
                    ->modalHeading('Eliminar nota')
                    ->modalDescription('Esto borra la nota definitivamente. Si la tarea ya se hizo, mejor marcala como "Hecha" para conservar el historial.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin notas todavía')
            ->emptyStateDescription('Agregá una nota rápida para tener presente lo importante del paciente.')
            ->emptyStateIcon('heroicon-o-bell-alert');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['leida'] = false; // toda nota nueva nace sin leer

        return $data;
    }
}
