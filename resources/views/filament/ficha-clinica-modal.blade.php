<div x-data="{ tab: 'odontograma' }">
    {{-- Pestañas: alternan entre el odontograma y la hoja del paciente --}}
    <div style="display:flex; gap:.4rem; margin-bottom:1rem; border-bottom:1px solid rgb(113 113 122 / .25); padding-bottom:.5rem;">
        <button type="button" @click="tab = 'odontograma'"
            :style="tab === 'odontograma'
                ? 'background:#2563eb; color:#fff; border-color:#2563eb;'
                : 'background:transparent; color:inherit; border-color:rgb(113 113 122 / .4);'"
            style="font-size:.85rem; font-weight:600; padding:.4rem 1rem; border-radius:.5rem; border:1px solid; cursor:pointer;">
            🦷 Odontograma
        </button>
        <button type="button" @click="tab = 'hoja'"
            :style="tab === 'hoja'
                ? 'background:#2563eb; color:#fff; border-color:#2563eb;'
                : 'background:transparent; color:inherit; border-color:rgb(113 113 122 / .4);'"
            style="font-size:.85rem; font-weight:600; padding:.4rem 1rem; border-radius:.5rem; border:1px solid; cursor:pointer;">
            📋 Hoja de evaluación
        </button>
    </div>

    {{-- Las dos vistas se montan a la vez; Alpine alterna cuál se muestra --}}
    <div x-show="tab === 'odontograma'">
        @livewire(\App\Livewire\Odontograma::class, ['cliente' => $cliente], key('ficha-odo-'.$cliente->id))
    </div>

    <div x-show="tab === 'hoja'" x-cloak>
        @livewire(\App\Livewire\HojaDiagnostico::class, ['hoja' => $cliente->hoja()], key('ficha-hoja-'.$cliente->id))
    </div>
</div>
