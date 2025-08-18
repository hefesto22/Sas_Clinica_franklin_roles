<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')
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
                Tables\Columns\ImageColumn::make('path')
                    ->label('Imagen')
                    ->disk('public')
                    ->height('80px')
                    ->width('80px')
                    ->extraImgAttributes(['class' => 'rounded-xl object-cover']),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subida')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    // Oculta el botón si ya hay 7 imágenes
                    ->hidden(fn() => $this->getOwnerRecord()->imagenes()->count() >= 7),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
