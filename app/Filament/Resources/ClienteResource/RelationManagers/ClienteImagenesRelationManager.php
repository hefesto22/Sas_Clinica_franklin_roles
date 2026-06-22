<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
        $cliente = $this->getOwnerRecord();

        return $schema->components([
            FileUpload::make('path')
                ->label('Imagen')
                ->image()
                ->disk('public')
                ->visibility('public')
                ->imageEditor()
                // Cada imagen se guarda con un nombre único en la carpeta del
                // paciente y se convierte a WebP (ahorra espacio). Si la
                // conversión no es posible, guarda la original con nombre único:
                // así NUNCA se pisan imágenes entre expedientes.
                ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string =>
                    $this->guardarComoWebp($file, $cliente->getKey()))
                ->required(),
        ])->columns(1);
    }

    /**
     * Guarda la imagen subida como WebP en clientes/{clienteId}/ con nombre
     * único. Corrige la orientación EXIF (fotos de teléfono salen rotadas).
     * Si GD/WebP no está disponible o algo falla, guarda la original con
     * nombre único — nunca colisiona con otro expediente.
     */
    protected function guardarComoWebp(TemporaryUploadedFile $file, int $clienteId): string
    {
        $directorio = "clientes/{$clienteId}";
        $nombre     = 'img_' . now()->format('Ymd_His') . '_' . uniqid();

        try {
            if (function_exists('imagewebp')) {
                $imagen = @imagecreatefromstring(file_get_contents($file->getRealPath()));

                if ($imagen !== false) {
                    $imagen = $this->corregirOrientacion($imagen, $file->getRealPath());

                    ob_start();
                    imagewebp($imagen, null, 80);
                    $contenido = ob_get_clean();
                    imagedestroy($imagen);

                    $ruta = "{$directorio}/{$nombre}.webp";
                    Storage::disk('public')->put($ruta, $contenido);

                    return $ruta;
                }
            }
        } catch (\Throwable $e) {
            // Cae al fallback: guardar la original con nombre único.
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';

        return $file->storeAs($directorio, "{$nombre}.{$extension}", 'public');
    }

    /** Rota la imagen según la orientación EXIF (solo afecta JPEG de cámara). */
    protected function corregirOrientacion(\GdImage $imagen, string $ruta): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $imagen;
        }

        $exif = @exif_read_data($ruta);
        $orientacion = $exif['Orientation'] ?? null;

        return match ($orientacion) {
            3 => imagerotate($imagen, 180, 0),
            6 => imagerotate($imagen, -90, 0),
            8 => imagerotate($imagen, 90, 0),
            default => $imagen,
        };
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
