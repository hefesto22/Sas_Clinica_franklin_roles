<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClienteResource\RelationManagers\ClienteActividadesRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\ClienteImagenesRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\ClienteNotasRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\EvaluacionesRelationManager;
use App\Filament\Resources\ClienteResource\Pages\ListClientes;
use App\Filament\Resources\ClienteResource\Pages\CreateCliente;
use App\Filament\Resources\ClienteResource\Pages\EditCliente;
use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pacientes';
    protected static ?string $pluralModelLabel = 'Pacientes';
    protected static ?string $modelLabel = 'Paciente';
    protected static string | \UnitEnum | null $navigationGroup = 'Gestión de Pacientes';

    /** Búsqueda global (barra superior): encuentra pacientes desde cualquier pantalla. */
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'dni', 'telefono'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'DNI'      => $record->dni,
            'Teléfono' => $record->telefono ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos personales')->schema([
                Grid::make(12)->schema([
                    TextInput::make('nombre')
                        ->label('Nombre completo')
                        ->required()
                        ->maxLength(255)
                        ->autofocus()
                        ->columnSpan(6),

                    TextInput::make('dni')
                        ->label('DNI')
                        ->helperText('Opcional: si no lo trae, se deja vacío y se completa luego.')
                        ->maxLength(50)
                        // Vacío se guarda como NULL (no como texto en blanco),
                        // para que la unicidad solo aplique a DNIs reales.
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                        // Único solo cuando tiene valor; incluye pacientes archivados.
                        ->rules([
                            fn (?\Illuminate\Database\Eloquent\Model $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                if (blank($value)) {
                                    return;
                                }

                                $existe = \App\Models\Cliente::withTrashed()
                                    ->where('dni', $value)
                                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                    ->exists();

                                if ($existe) {
                                    $fail('Ya existe un paciente con este DNI (revisa también los archivados).');
                                }
                            },
                        ])
                        ->columnSpan(3),

                    Select::make('tipo_paciente')
                        ->label('Tipo de paciente')
                        ->required()
                        ->options(\App\Models\Cliente::TIPOS)
                        ->default('general')
                        ->native(false)
                        ->columnSpan(3),

                    TextInput::make('telefono')
                        ->label('Teléfono')
                        ->maxLength(30)
                        ->tel()
                        ->columnSpan(3),

                    TextInput::make('direccion')
                        ->label('Dirección')
                        ->maxLength(255)
                        ->columnSpan(6),

                    TextInput::make('ocupacion')
                        ->label('Ocupación')
                        ->maxLength(100)
                        ->columnSpan(3),

                    DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->columnSpan(3),
                ]),
            ])->collapsible(),

            Section::make('Contacto de emergencia')
                ->collapsed(fn (string $operation): bool => $operation === 'create')
                ->schema([
                Grid::make(12)->schema([
                    TextInput::make('contacto_emergencia_nombre')
                        ->label('Nombre')
                        ->maxLength(255)
                        ->columnSpan(6),
                    TextInput::make('contacto_emergencia_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(30)
                        ->columnSpan(6),
                ]),
            ])->collapsible(),

            Section::make('Datos clínicos rápidos')->schema([
                Grid::make(2)->schema([
                    Textarea::make('motivo_consulta')
                        ->label('Motivo de consulta')
                        ->rows(2)
                        ->placeholder('Ej: dolor, limpieza, control de brackets...'),
                    Textarea::make('alergias')
                        ->label('Alergias')
                        ->rows(2)
                        ->placeholder('Ej: penicilina, anestesia... (vacío = ninguna conocida)'),
                ]),
            ])->collapsible(),

            // ClienteResource.php (form)
            Section::make('Estado')
                ->schema([
                    Select::make('estado')
                        ->label('Estado')
                        ->required()
                        ->options([
                            'activo' => 'Activo',
                            'inactivo' => 'Inactivo',
                        ])
                        ->native(false)
                        ->default('activo'),
                ])
                ->columns(1)
                ->hiddenOn('create')
                ->collapsible(),  // 👈 oculta la sección completa al crear
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Conteo de notas PENDIENTES (no hechas) por paciente (badge de la lista).
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'notas as notas_pendientes_count' => fn (Builder $q) => $q->whereNull('hecha_en'),
            ]))
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('notas_pendientes_count')
                    ->label('Notas')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-bell-alert')
                    // Solo muestra el badge si hay notas sin leer; si no, queda vacío.
                    ->formatStateUsing(fn (int $state): ?string => $state > 0 ? (string) $state : null)
                    ->placeholder('—')
                    ->tooltip('Notas pendientes — clic para previsualizar')
                    ->action(
                        Action::make('previsualizarNotas')
                            ->modalHeading(fn (Cliente $record) => 'Notas de ' . $record->nombre)
                            ->modalContent(fn (Cliente $record) => view('filament.cliente-notas-preview', ['cliente' => $record]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar'),
                    ),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin DNI'),

                TextColumn::make('tipo_paciente')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => \App\Models\Cliente::TIPOS[$state] ?? $state)
                    ->color(fn (?string $state) => $state === 'ortodoncia' ? 'info' : 'gray')
                    ->sortable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state) => $state === 'activo' ? 'success' : 'gray'),

                TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('notas_pendientes')
                    ->label('Con notas pendientes')
                    ->query(fn (Builder $query) => $query->whereHas('notas', fn (Builder $q) => $q->whereNull('hecha_en'))),
                SelectFilter::make('tipo_paciente')
                    ->label('Tipo de paciente')
                    ->options(\App\Models\Cliente::TIPOS),
                SelectFilter::make('estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ]),
                \Filament\Tables\Filters\TrashedFilter::make()
                    ->label('Archivados'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->label('Archivar')
                    ->modalHeading('Archivar paciente')
                    ->modalDescription('El expediente se archiva y deja de aparecer en las listas; su historial se conserva y puede restaurarse.')
                    ->successNotificationTitle('Paciente archivado'),
                \Filament\Actions\RestoreAction::make()
                    ->label('Restaurar'),
                \Filament\Actions\ForceDeleteAction::make()
                    ->label('Eliminar definitivo'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Archivar seleccionados'),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Incluye archivados cuando el filtro lo pide (patrón estándar SoftDeletes).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([\Illuminate\Database\Eloquent\SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            // Notas primero: lo que hay que tener presente del paciente.
            ClienteNotasRelationManager::class,
            ClienteActividadesRelationManager::class,
            ClienteImagenesRelationManager::class,
            EvaluacionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClientes::route('/'),
            'create' => CreateCliente::route('/create'),
            'edit'   => EditCliente::route('/{record}/edit'),
        ];
    }
}
