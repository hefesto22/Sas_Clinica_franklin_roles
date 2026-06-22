<div style="font-size:14px;">
    <p style="font-size:.8rem; opacity:.7; margin-bottom:.6rem;">
        Clic en un diente para cargar su texto y agregar una o varias condiciones. El punto de color indica que tiene condición.
    </p>

    {{-- Dos columnas: la hoja a la izquierda, el panel del diente a la derecha (fijo). --}}
    <div style="display:flex; gap:1rem; align-items:flex-start; flex-wrap:wrap;">

        {{-- ── Columna izquierda: ficha dental ─────────────────────────
             Cada fila se parte en dos cuadrantes (derecho | izquierdo) con la
             línea media al centro. Una división separa la arcada superior de la
             inferior. Todos los botones comparten alto para alinear columnas. --}}
        <div style="flex:1 1 360px; min-width:300px;">
            <div style="display:flex; flex-direction:column; gap:.3rem; position:relative;">
                {{-- línea media (continua) --}}
                <div style="position:absolute; top:0; bottom:0; left:50%; width:1px;
                            background:rgb(113 113 122 / .25); transform:translateX(-.5px); pointer-events:none;"></div>

                @foreach (\App\Livewire\HojaDiagnostico::LAYOUT as $idx => $fila)
                    @php $mitades = [[$fila[0], $fila[1]], [$fila[2], $fila[3]]]; @endphp
                    <div style="display:flex; gap:1rem; align-items:stretch;">
                        @foreach ($mitades as $mitad)
                            @php $solo = $mitad[0] === null || $mitad[1] === null; @endphp
                            <div style="flex:1; display:grid; grid-template-columns:1fr 1fr; gap:.4rem;">
                                @foreach ($mitad as $j => $pieza)
                                    @continue($pieza === null)
                                    @php
                                        $color = $this->colorDe($pieza);
                                        $texto = $this->textoDe($pieza);
                                        $col = $solo ? '1 / 3' : ($j + 1).' / '.($j + 2);
                                    @endphp
                                    <button type="button" wire:click="seleccionar('{{ $pieza }}')"
                                        title="{{ $pieza }}{{ $texto ? ' — '.$texto : '' }}"
                                        style="grid-column:{{ $col }}; display:flex; align-items:center; gap:.35rem; text-align:left; cursor:pointer;
                                               min-height:1.95rem; box-sizing:border-box;
                                               border:1px solid {{ $piezaSeleccionada === $pieza ? '#16a34a' : 'rgb(113 113 122 / .35)' }};
                                               border-radius:.45rem; padding:.25rem .5rem; background:transparent; color:inherit; min-width:0;
                                               {{ $piezaSeleccionada === $pieza ? 'box-shadow:0 0 0 2px rgb(22 163 74 / .25);' : '' }}">
                                        <span style="font-weight:700; font-size:.72rem; opacity:.75; flex-shrink:0; width:1.6rem;">{{ $pieza }}</span>
                                        <span style="flex-shrink:0; width:9px; height:9px; border-radius:9999px;
                                                     background:{{ $color ?? 'transparent' }};
                                                     border:1px solid {{ $color ?? 'rgb(113 113 122 / .4)' }};"></span>
                                        <span style="font-size:.72rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; opacity:.85;">{{ $texto }}</span>
                                        @if ($this->hechoDe($pieza))
                                            <span style="margin-left:auto; color:#16a34a; font-size:.7rem; flex-shrink:0;">✓</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    {{-- separación entre arcada superior e inferior --}}
                    @if ($idx === 7)
                        <div style="height:1px; background:rgb(113 113 122 / .35); margin:.45rem 0;"></div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- ── Columna derecha: panel del diente (sticky, scroll propio) ─ --}}
        <div style="flex:1 1 320px; min-width:280px; position:sticky; top:.5rem;">
            @if ($piezaSeleccionada)
                @php
                    $condiciones = $this->condicionesDe($piezaSeleccionada);
                    $tope = \App\Livewire\HojaDiagnostico::TOPE_CONDICIONES;
                    $visibles = $verTodas ? $condiciones : $condiciones->take($tope);
                @endphp
                <div style="border:1px solid rgb(113 113 122 / .35); border-radius:.7rem; padding:.9rem;
                            max-height:72vh; overflow-y:auto;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.6rem;">
                        <strong style="font-size:.95rem;">Diente {{ $piezaSeleccionada }}</strong>
                        <button type="button" wire:click="cerrarPanel"
                            style="border:none; background:transparent; cursor:pointer; color:inherit; opacity:.6; font-size:1.1rem;">✕</button>
                    </div>

                    @if ($this->puedeEditar)
                        {{-- Texto + Hecho --}}
                        <label style="font-size:.78rem; opacity:.8; display:block; margin-bottom:.25rem;">Nota / texto</label>
                        <textarea wire:model="texto" rows="2"
                            style="width:100%; border-radius:.5rem; padding:.45rem .7rem; font-size:.85rem; background:transparent; border:1px solid rgb(113 113 122 / .45); color:inherit;"
                            placeholder="Lo que anotás de siempre (ej: C1, Exo pos, puente...)"></textarea>
                        <div style="display:flex; align-items:center; gap:.8rem; margin-top:.5rem;">
                            <label style="display:inline-flex; align-items:center; gap:.4rem; font-size:.85rem; cursor:pointer;">
                                <input type="checkbox" wire:model="hecho"> Hecho
                            </label>
                            <button type="button" wire:click="guardarNota"
                                style="background:#16a34a; color:#fff; font-weight:600; font-size:.8rem; padding:.35rem .9rem; border-radius:.5rem; cursor:pointer; border:none;">
                                Guardar nota
                            </button>
                        </div>

                        {{-- Condiciones agregadas a este diente --}}
                        <div style="border-top:1px solid rgb(113 113 122 / .3); margin-top:.9rem; padding-top:.8rem;">
                            <label style="font-size:.78rem; opacity:.8; display:block; margin-bottom:.4rem;">Condiciones del diente</label>

                            @forelse ($visibles as $c)
                                <div style="border:1px solid {{ $c->tratada ? 'rgb(22 163 74 / .55)' : 'rgb(113 113 122 / .3)' }}; border-radius:.5rem; padding:.45rem .6rem; margin-bottom:.45rem;
                                            background:{{ $c->tratada ? 'rgb(22 163 74 / .08)' : 'transparent' }};">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap;">
                                        <span style="display:inline-flex; align-items:center; gap:.4rem; font-size:.84rem; font-weight:600;
                                                     {{ $c->tratada ? 'text-decoration:line-through; opacity:.7;' : '' }}">
                                            <i style="width:10px;height:10px;border-radius:3px;display:inline-block;
                                                      background:{{ $this->colorCondicion($c->condicion) ? $this->colorCondicion($c->condicion).'cc' : 'transparent' }};
                                                      border:1.5px solid {{ $this->colorCondicion($c->condicion) ?? 'rgb(113 113 122 / .5)' }};"></i>
                                            {{ $this->etiquetaCondicion($c->condicion) }}
                                        </span>
                                        <div style="display:flex; align-items:center; gap:.3rem;">
                                            @foreach ($this->tamanos as $tk => $tl)
                                                <button type="button" wire:click="cambiarTamano({{ $c->id }}, {{ $c->tamano === $tk ? 'null' : "'".$tk."'" }})"
                                                    title="{{ $tl }}"
                                                    style="font-size:.68rem; padding:.1rem .4rem; border-radius:.35rem; cursor:pointer; color:inherit;
                                                           background:{{ $c->tamano === $tk ? 'rgb(37 99 235 / .15)' : 'transparent' }};
                                                           border:1px solid {{ $c->tamano === $tk ? '#2563eb' : 'rgb(113 113 122 / .4)' }};">
                                                    {{ \Illuminate\Support\Str::substr($tl, 0, 1) }}
                                                </button>
                                            @endforeach
                                            @if ($c->tratada)
                                                <span title="Marcala como no hecha para poder archivarla"
                                                    style="border:1px solid rgb(113 113 122 / .3); border-radius:.35rem; padding:.1rem .4rem; font-size:.7rem; opacity:.4; cursor:not-allowed;">🗑</span>
                                            @else
                                                <button type="button" wire:click="eliminarCondicion({{ $c->id }})"
                                                    wire:confirm="¿Quitar esta condición del diente {{ $piezaSeleccionada }}?"
                                                    style="border:1px solid #dc2626; border-radius:.35rem; padding:.1rem .4rem; font-size:.7rem; cursor:pointer; background:transparent; color:#dc2626;">🗑</button>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:.4rem; margin-top:.4rem;">
                                        <input type="text" wire:model="notasCondicion.{{ $c->id }}"
                                            wire:keydown.enter="guardarNotaCondicion({{ $c->id }})"
                                            placeholder="Nota de esta condición (opcional)"
                                            style="flex:1; min-width:0; font-size:.78rem; padding:.3rem .5rem; border-radius:.4rem; background:transparent; border:1px solid rgb(113 113 122 / .4); color:inherit;">
                                        <button type="button" wire:click="guardarNotaCondicion({{ $c->id }})"
                                            style="font-size:.72rem; padding:.25rem .6rem; border-radius:.4rem; cursor:pointer; color:inherit;
                                                   background:rgb(22 163 74 / .15); border:1px solid #16a34a;">Guardar</button>
                                    </div>
                                    {{-- Estado: etiqueta informativa si está hecha; botón si está pendiente --}}
                                    @if ($c->tratada)
                                        @php $hechaLabel = '✓ Hecha'.($c->tratada_en ? ' · '.$c->tratada_en->format('d/m/Y') : ''); @endphp
                                        <div style="margin-top:.45rem; display:flex; align-items:center; justify-content:space-between; gap:.5rem;
                                                    background:rgb(22 163 74 / .12); border:1px solid rgb(22 163 74 / .4); border-radius:.4rem; padding:.3rem .6rem;">
                                            <span style="font-size:.76rem; color:#16a34a; font-weight:600;">{{ $hechaLabel }}</span>
                                            <button type="button" wire:click="alternarTratada({{ $c->id }})"
                                                title="Volver a pendiente"
                                                style="font-size:.7rem; padding:.12rem .5rem; border-radius:.35rem; cursor:pointer; color:inherit;
                                                       background:transparent; border:1px solid rgb(113 113 122 / .4); opacity:.8;">
                                                Deshacer
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" wire:click="alternarTratada({{ $c->id }})"
                                            style="margin-top:.45rem; width:100%; font-size:.74rem; padding:.3rem .5rem; border-radius:.4rem; cursor:pointer;
                                                   display:flex; align-items:center; justify-content:center; gap:.4rem;
                                                   background:transparent; color:inherit; border:1px dashed rgb(113 113 122 / .5);">
                                            Marcar como hecha
                                        </button>
                                    @endif
                                </div>
                            @empty
                                <p style="font-size:.78rem; opacity:.6; margin-bottom:.5rem;">Sin condiciones. Agregá una abajo (podés sumar varias).</p>
                            @endforelse

                            @if ($condiciones->count() > $tope)
                                <button type="button" wire:click="toggleVerTodas"
                                    style="width:100%; font-size:.76rem; padding:.3rem; border-radius:.4rem; cursor:pointer; color:inherit;
                                           background:transparent; border:1px solid rgb(113 113 122 / .4); margin-bottom:.5rem;">
                                    {{ $verTodas
                                        ? 'Ver menos'
                                        : 'Ver ' . ($condiciones->count() - $tope) . ' más (incluye las hechas)' }}
                                </button>
                            @endif

                            <label style="font-size:.74rem; opacity:.7; display:block; margin:.5rem 0 .3rem;">Agregar condición (clic en el color)</label>
                            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:.35rem;">
                                @foreach ($this->catalogo as $clave => $info)
                                    <button type="button" wire:click="agregarCondicion('{{ $clave }}')" title="Agregar {{ $info['label'] }}"
                                        style="display:flex; align-items:center; gap:.4rem; font-size:.76rem; padding:.32rem .5rem;
                                               min-height:1.95rem; box-sizing:border-box; text-align:left;
                                               border-radius:.5rem; cursor:pointer; color:inherit; min-width:0;
                                               background:{{ $info['color'] ? $info['color'].'1a' : 'transparent' }};
                                               border:1.5px solid {{ $info['color'] ?? 'rgb(113 113 122 / .45)' }};">
                                        <i style="flex-shrink:0; width:9px;height:9px;border-radius:3px;display:inline-block;
                                                  background:{{ $info['color'] ? $info['color'].'cc' : 'transparent' }};
                                                  border:1.5px solid {{ $info['color'] ?? 'rgb(113 113 122 / .5)' }};"></i>
                                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $info['label'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <p style="font-size:.72rem; opacity:.55; margin-top:.4rem;">El tamaño (P/M/G) se elige en cada condición ya agregada.</p>
                        </div>
                    @else
                        {{-- Solo lectura --}}
                        @if ($texto)<p style="font-size:.85rem; margin-bottom:.4rem;">{{ $texto }}</p>@endif
                        @forelse ($condiciones as $c)
                            <p style="font-size:.84rem;">• {{ $this->etiquetaCondicion($c->condicion) }}{{ $c->tamano ? ' ('.($this->tamanos[$c->tamano] ?? $c->tamano).')' : '' }}@if ($c->nota)<span style="opacity:.75;"> — {{ $c->nota }}</span>@endif @if ($c->tratada)<span style="color:#16a34a; font-weight:600;">✓ hecha</span>@endif</p>
                        @empty
                            <p style="font-size:.82rem; opacity:.6;">Sin condiciones.</p>
                        @endforelse
                    @endif
                </div>
            @else
                {{-- Sin diente seleccionado: placeholder para mantener la columna --}}
                <div style="border:1px dashed rgb(113 113 122 / .4); border-radius:.7rem; padding:1.5rem .9rem; text-align:center;">
                    <div style="font-size:1.6rem; opacity:.35;">🦷</div>
                    <p style="font-size:.82rem; opacity:.6; margin-top:.4rem;">Seleccioná un diente para ver y cargar su detalle aquí.</p>
                </div>
            @endif
        </div>

    </div>
</div>
