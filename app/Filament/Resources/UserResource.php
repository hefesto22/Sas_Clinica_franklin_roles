<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\BelongsToManySelect;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => Hash::needsRehash($state) ? bcrypt($state) : $state)
                    ->required(fn(string $context) => $context === 'create')
                    ->maxLength(255),

                Select::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required()
                    ->options(function () {
                        $query = Role::query();
                        $user = Auth::user();

                        if ($user?->roles->contains('name', 'Jefe')) {
                            $query->whereNotIn('name', ['super_admin']);
                        }

                        if ($user?->roles->contains('name', 'Encargado')) {
                            $query->whereNotIn('name', ['super_admin', 'Jefe', 'Encargado']);
                        }

                        return $query->pluck('name', 'id');
                    }),

                TextInput::make('remember_token')
                    ->maxLength(100)
                    ->visible(false),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable()->searchable(),
            TextColumn::make('name')->label('Nombre')->searchable(),
            TextColumn::make('email')->label('Correo')->searchable(),
            TextColumn::make('created_at')->label('Creado')->dateTime()->sortable(),
            TextColumn::make('updated_at')->label('Actualizado')->dateTime()->sortable(),
        ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Si es super_admin, ve todo
        if ($user->roles->contains('name', 'super_admin')) {
            return $query;
        }

        // Si es Jefe
        if ($user->roles->contains('name', 'Jefe')) {
            // IDs de usuarios creados por el jefe (normalmente encargados)
            $encargadosIds = User::where('created_by', $user->id)->pluck('id');

            return $query->where(function ($q) use ($user, $encargadosIds) {
                $q->where('id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereIn('created_by', $encargadosIds);
            });
        }

        // Si es Encargado
        if ($user->roles->contains('name', 'Encargado')) {
            return $query->where(function ($q) use ($user) {
                $q->where('id', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        // Otros roles, solo se ven a sí mismos
        return $query->where('id', $user->id);
    }


    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
