<?php

namespace App\Filament\Resources\EspecialidadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class ServicioEspecialidadRelationManager extends RelationManager
{
    protected static string $relationship = 'servicios';
    protected static ?string $title = 'Servicios';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre del servicio')
                ->required()
                ->maxLength(255)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn(Unique $rule) =>
                    $rule->where('especialidad_id', $this->getOwnerRecord()->getKey())
                ),

            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable(),

            Forms\Components\TextInput::make('precio')
                ->label('Precio')
                ->numeric()
                ->rule('decimal:0,2')
                ->prefix('$')
                ->nullable(),

            // Mostrar en edición si quieres permitir cambiarlo:
            Forms\Components\Select::make('estado')
                ->label('Estado')
                ->options(['activo' => 'Activo', 'inactivo' => 'Inactivo'])
                ->visibleOn('edit'), // oculto en Create
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('descripcion')->label('Descripción')->limit(50),
            Tables\Columns\TextColumn::make('precio')->label('Precio')->money('USD', true)->sortable(),
            Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                ->color(fn($state) => match ($state) {
                    'activo' => 'success',
                    'inactivo' => 'danger',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('creador.name')->label('Creado por')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i')->sortable(),
        ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
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
