<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pacientes';
    protected static ?string $pluralModelLabel = 'Pacientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $navigationGroup = 'Gestión de Pacientes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos personales')->schema([
                Forms\Components\Grid::make(12)->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre completo')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('dni')
                        ->label('DNI')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->maxLength(30)
                        ->tel()
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('direccion')
                        ->label('Dirección')
                        ->maxLength(255)
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('ocupacion')
                        ->label('Ocupación')
                        ->maxLength(100)
                        ->columnSpan(3),

                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->columnSpan(3),
                ]),
            ])->collapsible(),

            Forms\Components\Section::make('Contacto de emergencia')->schema([
                Forms\Components\Grid::make(12)->schema([
                    Forms\Components\TextInput::make('contacto_emergencia_nombre')
                        ->label('Nombre')
                        ->maxLength(255)
                        ->columnSpan(6),
                    Forms\Components\TextInput::make('contacto_emergencia_telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(30)
                        ->columnSpan(6),
                ]),
            ])->collapsible(),

            Forms\Components\Section::make('Datos clínicos rápidos')->schema([
                Forms\Components\Textarea::make('motivo_consulta')
                    ->label('Motivo de consulta')
                    ->rows(3),
                Forms\Components\Textarea::make('alergias')
                    ->label('Alergias')
                    ->rows(3),
            ])->collapsible(),

            // ClienteResource.php (form)
            Forms\Components\Section::make('Estado')
                ->schema([
                    Forms\Components\Select::make('estado')
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
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->toggleable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state) => $state === 'activo' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ClienteResource\RelationManagers\ClienteActividadesRelationManager::class,
            \App\Filament\Resources\ClienteResource\RelationManagers\ClienteImagenesRelationManager::class,
            \App\Filament\Resources\ClienteResource\RelationManagers\ClienteNotasRelationManager::class,
            \App\Filament\Resources\ClienteResource\RelationManagers\EvaluacionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit'   => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
