@php
    // Previsualización: pendientes primero, las hechas al fondo; reciente arriba.
    $notas = $cliente->notas()
        ->orderByRaw('hecha_en is not null')
        ->latest()
        ->get();
@endphp

<div style="display:flex; flex-direction:column; gap:.55rem; max-height:60vh; overflow-y:auto; padding:.15rem;">
    @forelse ($notas as $nota)
        <div style="border:1px solid {{ $nota->hecha_en ? 'rgb(113 113 122 / .3)' : '#f59e0b' }};
                    border-radius:.6rem; padding:.65rem .85rem;
                    {{ $nota->hecha_en ? 'opacity:.7;' : 'background: rgb(245 158 11 / .07);' }}">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:.5rem; margin-bottom:.3rem;">
                @if ($nota->hecha_en)
                    <span style="font-size:.72rem; font-weight:700; color:#16a34a;">✓ Hecha · {{ $nota->hecha_en->format('d/m/Y') }}</span>
                @else
                    <span style="font-size:.72rem; font-weight:700; color:#d97706;">● Pendiente</span>
                @endif
                <span style="font-size:.72rem; opacity:.6;">{{ $nota->created_at->diffForHumans() }}</span>
            </div>

            <div style="font-size:.88rem; white-space:pre-wrap; line-height:1.35; word-break:break-word; overflow-wrap:anywhere; {{ $nota->hecha_en ? 'text-decoration:line-through; opacity:.8;' : '' }}">{{ $nota->contenido }}</div>

            <div style="font-size:.72rem; opacity:.6; margin-top:.35rem;">
                Por {{ $nota->creador?->name ?? '—' }}
            </div>
        </div>
    @empty
        <p style="opacity:.6; text-align:center; padding:1rem;">Este paciente no tiene notas.</p>
    @endforelse
</div>

<p style="font-size:.74rem; opacity:.55; margin-top:.6rem;">
    Vista de solo lectura. Para marcar hecha, reabrir, editar o agregar, entrá al paciente → pestaña Notas rápidas.
</p>
