<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    public function getTitle(): string
    {
        return 'Registrar paciente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['estado'] = 'activo';
        return $data;
    }

    /** Botones claros para el flujo de mostrador. */
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Registrar');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->label('Registrar y agregar otro');
    }

    /** Tras registrar, de vuelta a la lista (no a editar el expediente). */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Paciente registrado';
    }
}
