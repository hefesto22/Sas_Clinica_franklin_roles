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

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pacientes';
    protected static ?string $pluralModelLabel = 'Pacientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static string | \UnitEnum | null $navigationGroup = 'Gestión de Pacientes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos personales')->schema([
                Grid::make(12)->schema([
                    TextInput::make('nombre')
                        ->label('Nombre completo')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(6),

                    TextInput::make('dni')
                        ->label('DNI')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
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

            Section::make('Contacto de emergencia')->schema([
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
                Textarea::make('motivo_consulta')
                    ->label('Motivo de consulta')
                    ->rows(3),
                Textarea::make('alergias')
                    ->label('Alergias')
                    ->rows(3),
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
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
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
                SelectFilter::make('estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ClienteActividadesRelationManager::class,
            ClienteImagenesRelationManager::class,
            ClienteNotasRelationManager::class,
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
