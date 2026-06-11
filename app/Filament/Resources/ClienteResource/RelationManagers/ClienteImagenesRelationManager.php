<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ClienteImagenesRelationManager extends RelationManager
{
    protected static string $relationship = 'imagenes';
    protected static ?string $title = 'Imágenes';
    protected static ?string $recordTitleAttribute = 'path';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Imagen')
                ->image()
                ->directory('clientes')   // storage/app/public/clientes
                ->disk('public')
                ->visibility('public')
                ->preserveFilenames()
                ->imageEditor()
                ->required(),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Imagen')
                    ->disk('public')
                    ->height('80px')
                    ->width('80px')
                    ->extraImgAttributes(['class' => 'rounded-xl object-cover']),
                TextColumn::make('created_at')
                    ->label('Subida')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    // Oculta el botón si ya hay 7 imágenes
                    ->hidden(fn() => $this->getOwnerRecord()->imagenes()->count() >= 7),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Previsualizar')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Vista previa')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('4xl')
                    ->modalContent(function ($record) {
                        $url = Storage::url($record->path); // 👈 aquí van los ()
                        return new HtmlString(
                            "<img src=\"{$url}\" alt=\"Vista\" class=\"w-full max-h-[80vh] object-contain rounded-xl\" />"
                        );
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
