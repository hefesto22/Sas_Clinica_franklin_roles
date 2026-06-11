<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ConsultorioResource\RelationManagers\TurnosRelationManager;
use App\Filament\Resources\ConsultorioResource\Pages\ListConsultorios;
use App\Filament\Resources\ConsultorioResource\Pages\CreateConsultorio;
use App\Filament\Resources\ConsultorioResource\Pages\EditConsultorio;
use App\Filament\Resources\ConsultorioResource\Pages;
use App\Filament\Resources\ConsultorioResource\RelationManagers;
use App\Models\Consultorio;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConsultorioResource extends Resource
{
    protected static ?string $model = Consultorio::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    protected static string | \UnitEnum | null $navigationGroup = 'Gestión de Clínica';
    protected static ?string $navigationLabel = 'Consultorios';
    protected static ?string $pluralLabel = 'Consultorios';
    protected static ?string $label = 'Consultorio';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre del consultorio')
                    ->required()
                    ->maxLength(255),

                Select::make('modo_defecto')
                    ->label('Modo por defecto')
                    ->options([
                        'horario' => 'Horario (intervalos)',
                        'cupos'   => 'Cupos por hora',
                    ])
                    ->required()
                    ->default('horario'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('modo_defecto')
                    ->label('Modo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'horario' => 'success',
                        'cupos'   => 'warning',
                        default   => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('modo_defecto')
                    ->label('Modo')
                    ->options([
                        'horario' => 'Horario',
                        'cupos'   => 'Cupos',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TurnosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultorios::route('/'),
            'create' => CreateConsultorio::route('/create'),
            'edit' => EditConsultorio::route('/{record}/edit'),
        ];
    }
}
