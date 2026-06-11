<?php

namespace App\Filament\Resources\CambioEventoResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\CambioEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCambioEvento extends EditRecord
{
    protected static string $resource = CambioEventoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
