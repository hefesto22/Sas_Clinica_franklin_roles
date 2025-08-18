<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultorioResource\Pages;
use App\Filament\Resources\ConsultorioResource\RelationManagers;
use App\Models\Consultorio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConsultorioResource extends Resource
{
    protected static ?string $model = Consultorio::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Gestión de Clínica';
    protected static ?string $navigationLabel = 'Consultorios';
    protected static ?string $pluralLabel = 'Consultorios';
    protected static ?string $label = 'Consultorio';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre del consultorio')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('modo_defecto')
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
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('modo_defecto')
                    ->label('Modo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'horario' => 'success',
                        'cupos'   => 'warning',
                        default   => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('modo_defecto')
                    ->label('Modo')
                    ->options([
                        'horario' => 'Horario',
                        'cupos'   => 'Cupos',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ConsultorioResource\RelationManagers\TurnosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultorios::route('/'),
            'create' => Pages\CreateConsultorio::route('/create'),
            'edit' => Pages\EditConsultorio::route('/{record}/edit'),
        ];
    }
}
