<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    /**
     * Auditoría de LECTURA: abrir el expediente de un paciente deja
     * registro de quién lo consultó y cuándo (dato médico sensible).
     */
    public function mount(int | string $record): void
    {
        parent::mount($record);

        activity('expediente')
            ->performedOn($this->getRecord())
            ->causedBy(Auth::user())
            ->event('consulta')
            ->log('Consultó el expediente del paciente');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
