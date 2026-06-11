<?php

namespace App\Filament\Resources\EspecialidadResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\EspecialidadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEspecialidad extends EditRecord
{
    protected static string $resource = EspecialidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 👇 Auditoría: cada vez que se edita
        $data['updated_by'] = Auth::id();

        return $data;
    }
}
