<?php

namespace App\Filament\Resources\EspecialidadResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class ServicioEspecialidadRelationManager extends RelationManager
{
    protected static string $relationship = 'servicios';
    protected static ?string $title = 'Servicios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')
                ->label('Nombre del servicio')
                ->required()
                ->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn(Unique $rule) =>
                    $rule->where('especialidad_id', $this->getOwnerRecord()->getKey())
                ),

            Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable(),

            TextInput::make('precio')
                ->label('Precio')
                ->numeric()
                ->rule('decimal:0,2')
                ->prefix('$')
                ->nullable(),

            // Mostrar en edición si quieres permitir cambiarlo:
            Select::make('estado')
                ->label('Estado')
                ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                ->visibleOn('edit'), // oculto en Create
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
            TextColumn::make('descripcion')->label('Descripción')->limit(50),
            TextColumn::make('precio')->label('Precio')->money('USD', true)->sortable(),
            TextColumn::make('estado')->label('Estado')->badge()
                ->color(fn($state) => match ($state) {
                    'activo' => 'success',
                    'inactivo' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('creador.name')->label('Creado por')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i')->sortable(),
        ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    // ==== HOOKS CLAVE ====
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['especialidad_id'] = $this->getOwnerRecord()->getKey();
        $data['estado'] = $data['estado'] ?? 'activo';  // por defecto activo
        $data['created_by'] = Auth::id();               // 👈 evita el error 1364
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
