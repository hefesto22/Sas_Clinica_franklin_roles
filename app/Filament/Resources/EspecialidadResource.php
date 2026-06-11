<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\EspecialidadResource\RelationManagers\ServicioEspecialidadRelationManager;
use App\Filament\Resources\EspecialidadResource\Pages\ListEspecialidads;
use App\Filament\Resources\EspecialidadResource\Pages\CreateEspecialidad;
use App\Filament\Resources\EspecialidadResource\Pages\EditEspecialidad;
use App\Filament\Resources\EspecialidadResource\Pages;
use App\Models\Especialidad;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EspecialidadResource extends Resource
{
    protected static ?string $model = Especialidad::class;

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Especialidades';
    protected static string | \UnitEnum | null $navigationGroup = 'Gestión Clínica';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable(),

            Hidden::make('estado')
                ->default('activo')
                ->disabledOn('edit'),

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

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'activo' => 'success',
                        'inactivo' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('editor.name')
                    ->label('Editado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since() // muestra "hace X"
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
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
            ServicioEspecialidadRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index'  => ListEspecialidads::route('/'),
            'create' => CreateEspecialidad::route('/create'),
            'edit'   => EditEspecialidad::route('/{record}/edit'),
        ];
    }
}
