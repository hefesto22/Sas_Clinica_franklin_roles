@php
    use App\Models\EvaluacionDetalle;

    /**
     * Trazos SVG anatómicos por tipo de diente (viewBox 0 0 48 112,
     * raíz arriba / corona abajo). Curvas con estrechamiento cervical,
     * cúspides en la cara oclusal y fisuras como detalle.
     */
    $trazos = [
        'molar' => [
            'raices' => [
                'M13,46 C9,34 8,18 11,9 C12,4 18,4 18,9 C18,22 19,34 22,46 Z',
                'M35,46 C39,34 40,18 37,9 C36,4 30,4 30,9 C30,22 29,34 26,46 Z',
            ],
            'corona' => 'M9,46 C5,54 4,66 6,76 C8,88 13,97 17,99 C20,101 22,97 24,97 C26,97 28,101 31,99 C35,97 40,88 42,76 C44,66 43,54 39,46 C33,42 15,42 9,46 Z',
            'detalle' => 'M15,70 Q19,64 24,70 Q29,76 33,70',
        ],
        'premolar' => [
            'raices' => [
                'M21,46 C18,34 18,16 21,8 C22,3 26,3 27,8 C30,16 30,34 27,46 Z',
            ],
            'corona' => 'M11,48 C7,56 6,68 8,78 C10,89 15,97 19,99 C21,100 23,97 24,97 C25,97 27,100 29,99 C33,97 38,89 40,78 C42,68 41,56 37,48 C31,44 17,44 11,48 Z',
            'detalle' => 'M17,72 Q24,65 31,72',
        ],
        'canino' => [
            'raices' => [
                'M21,42 C18,28 19,12 22,5 C23,1 26,1 27,5 C30,12 30,28 27,42 Z',
            ],
            'corona' => 'M11,46 C7,56 7,68 10,78 C13,88 19,97 24,103 C29,97 35,88 38,78 C41,68 41,56 37,46 C31,42 17,42 11,46 Z',
            'detalle' => 'M24,54 C23,66 23,78 24,88',
        ],
        'incisivo' => [
            'raices' => [
                'M20,44 C18,32 18,14 21,7 C22,2 26,2 27,7 C30,14 30,32 28,44 Z',
            ],
            'corona' => 'M13,46 C10,54 9,64 10,74 C11,86 14,96 16,100 L32,100 C34,96 37,86 38,74 C39,64 38,54 35,46 C29,42 19,42 13,46 Z',
            'detalle' => 'M19,56 C19,70 19,82 19,92 M29,56 C29,70 29,82 29,92',
        ],
    ];
@endphp

<div class="odo">
    {{-- Degradados compartidos (esmalte y dentina) para dar volumen --}}
    <svg width="0" height="0" style="position:absolute">
        <defs>
            <linearGradient id="odoEsmalte" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#ffffff"/>
                <stop offset="55%" stop-color="#f1f5f9"/>
                <stop offset="100%" stop-color="#cbd5e1"/>
            </linearGradient>
            <linearGradient id="odoRaizG" x1="0" y1="1" x2="0" y2="0">
                <stop offset="0%" stop-color="#eedfc2"/>
                <stop offset="100%" stop-color="#d9c194"/>
            </linearGradient>
        </defs>
    </svg>

    <style>
        .odo { padding: .25rem 0; }
        .odo-fila { display: flex; justify-content: center; align-items: flex-end; gap: 3px; margin: 2px 0; }
        .odo-fila.inferior { align-items: flex-start; }
        .odo-sep { width: 2px; align-self: stretch; background: rgb(113 113 122 / .3); margin: 0 9px; border-radius: 2px; }
        .odo-diente {
            position: relative;
            width: 42px; background: transparent; border: none; padding: 2px; cursor: pointer;
            display: flex; flex-direction: column; align-items: center; gap: 1px;
            border-radius: .5rem; transition: transform .08s ease;
            color: inherit;
        }
        .odo-diente.deciduo { width: 35px; }
        .odo-diente:hover { transform: scale(1.1); z-index: 2; }
        .odo-diente.sel { outline: 2.5px solid #39C928; outline-offset: 1px; }
        .odo-diente svg { width: 100%; height: auto; display: block; }
        .odo-diente .num { font-size: 10.5px; font-weight: 700; opacity: .8; line-height: 1; }
        .odo-diente.inferior svg { transform: rotate(180deg); }
        .odo-raiz { fill: url(#odoRaizG); stroke: #ab9166; stroke-width: 1.3; }
        .odo-corona { fill: url(#odoEsmalte); stroke: #64748b; stroke-width: 1.5; }
        .odo-fisura { fill: none; stroke: #64748b; stroke-width: 1.3; stroke-linecap: round; opacity: .45; }
        .odo-check {
            position: absolute; top: 0; right: 0; width: 14px; height: 14px; border-radius: 9999px;
            background: #16a34a; color: #fff; font-size: 9px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .odo-titulo { text-align: center; font-size: 10.5px; text-transform: uppercase; letter-spacing: .1em; opacity: .5; margin: .4rem 0 .2rem; }
        .odo-leyenda { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem 1rem; font-size: 11.5px; margin: .8rem auto .2rem; opacity: .9; max-width: 760px; }
        .odo-leyenda span { display: inline-flex; align-items: center; gap: .3rem; }
        .odo-chip { width: 11px; height: 11px; border-radius: 3px; border: 1.5px solid rgb(113 113 122 / .5); display: inline-block; }
        .odo-layout { display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap; }
        .odo-chart { flex: 1 1 640px; min-width: 0; }
        .odo-panel-col { flex: 1 1 320px; min-width: 280px; position: sticky; top: .5rem; }
        .odo-panel { margin: 0; border: 1px solid rgb(113 113 122 / .35); border-radius: .75rem; padding: 1rem; max-height: 76vh; overflow-y: auto; }
        .odo-panel h4 { font-weight: 700; margin-bottom: .6rem; }
        .odo-panel .grid2 { display: grid; grid-template-columns: 220px 1fr; gap: .75rem; align-items: start; }
        .odo-panel select, .odo-panel textarea {
            width: 100%; border-radius: .5rem; padding: .45rem .7rem; font-size: .875rem;
            background: transparent; border: 1px solid rgb(113 113 122 / .45); color: inherit;
        }
        .odo-panel select option { color: #111; }
        .odo-panel .fila-acciones { display: flex; align-items: center; justify-content: space-between; margin-top: .75rem; gap: 1rem; }
        .odo-btn { background: #39C928; color: #fff; font-weight: 600; font-size: .875rem; padding: .45rem 1.1rem; border-radius: .5rem; cursor: pointer; border: none; }
        .odo-btn:hover { filter: brightness(1.08); }
        .odo-check-lbl { display: inline-flex; align-items: center; gap: .45rem; font-size: .875rem; cursor: pointer; }
    </style>

    @php
        $renderDiente = function (string $pieza, bool $inferior) use ($trazos) {
            $tipo = $this->tipoDe($pieza);
            $color = $this->colorDe($pieza);
            $condiciones = $this->condicionesDe($pieza);
            $esDeciduo = (int) explode('.', $pieza)[0] >= 5;
            $ausente = in_array('ausente', $condiciones, true);
            $extraccion = in_array('extraccion_indicada', $condiciones, true);
            $implante = in_array('implante', $condiciones, true);
            $hecho = $this->estadoDe($pieza) === 'hecho';
            $extras = $this->coloresExtraDe($pieza);

            $tratadas = $this->tratadasDe($pieza);
            $etiquetas = collect($condiciones)
                ->map(fn ($c) => (EvaluacionDetalle::CONDICIONES[$c]['label'] ?? $c)
                    . (in_array($c, $tratadas, true) ? ' ✓' : ''))
                ->implode(', ');
            $titulo = trim(($etiquetas ? $etiquetas . '. ' : '') . ($this->diagnosticoDe($pieza) ?? ''));

            return compact('tipo', 'color', 'condiciones', 'esDeciduo', 'ausente', 'extraccion', 'implante', 'hecho', 'extras', 'titulo');
        };
    @endphp

    <div class="odo-layout">
    <div class="odo-chart">
    <div class="odo-titulo">Arcada superior</div>

    @foreach (['superior_permanente' => false, 'superior_deciduo' => false, 'inferior_deciduo' => true, 'inferior_permanente' => true] as $arcada => $esInferior)
        <div class="odo-fila {{ $esInferior ? 'inferior' : '' }}">
            @foreach (\App\Livewire\Odontograma::ARCADAS[$arcada] as $ladoIndex => $lado)
                @if ($ladoIndex === 1) <div class="odo-sep"></div> @endif
                @foreach ($lado as $pieza)
                    @php $d = $renderDiente($pieza, $esInferior); $t = $trazos[$d['tipo']]; @endphp
                    <button
                        type="button"
                        wire:click="seleccionar('{{ $pieza }}')"
                        class="odo-diente {{ $esInferior ? 'inferior' : '' }} {{ $d['esDeciduo'] ? 'deciduo' : '' }} {{ $piezaSeleccionada === $pieza ? 'sel' : '' }}"
                        title="{{ trim($d['titulo']) ?: 'Sin registro' }}"
                    >
                        @if (! $esInferior) <span class="num">{{ $pieza }}</span> @endif

                        <svg viewBox="0 0 48 112" xmlns="http://www.w3.org/2000/svg">
                            @if ($d['ausente'])
                                {{-- Pieza ausente: contorno punteado --}}
                                @foreach ($t['raices'] as $raiz)
                                    <path d="{{ $raiz }}" fill="none" stroke="rgb(113 113 122 / .55)" stroke-width="1.3" stroke-dasharray="3 3"/>
                                @endforeach
                                <path d="{{ $t['corona'] }}" fill="none" stroke="rgb(113 113 122 / .55)" stroke-width="1.5" stroke-dasharray="4 3"/>
                            @else
                                @if ($d['implante'])
                                    {{-- Implante: tornillo en lugar de raíz --}}
                                    <path d="M20,10 H28 L26,46 H22 Z" fill="#6b7280" stroke="#4b5563" stroke-width="1.2"/>
                                    <line x1="18" y1="19" x2="30" y2="16" stroke="#4b5563" stroke-width="1.8"/>
                                    <line x1="18" y1="27" x2="30" y2="24" stroke="#4b5563" stroke-width="1.8"/>
                                    <line x1="18" y1="35" x2="30" y2="32" stroke="#4b5563" stroke-width="1.8"/>
                                @else
                                    @foreach ($t['raices'] as $raiz)
                                        <path d="{{ $raiz }}" class="odo-raiz"/>
                                    @endforeach
                                @endif

                                <path d="{{ $t['corona'] }}" class="odo-corona"
                                    @if ($d['color']) style="fill: {{ $d['color'] }}; fill-opacity: .8; stroke: {{ $d['color'] }};" @endif
                                />

                                @if (! empty($t['detalle']))
                                    <path d="{{ $t['detalle'] }}" class="odo-fisura"
                                        @if ($d['color']) style="stroke: #fff; opacity: .5;" @endif
                                    />
                                @endif

                                @if ($d['extraccion'])
                                    {{-- X de extracción indicada --}}
                                    <line x1="10" y1="50" x2="38" y2="100" stroke="#dc2626" stroke-width="4.5" stroke-linecap="round"/>
                                    <line x1="38" y1="50" x2="10" y2="100" stroke="#dc2626" stroke-width="4.5" stroke-linecap="round"/>
                                @endif
                            @endif
                        </svg>

                        @if ($esInferior) <span class="num">{{ $pieza }}</span> @endif

                        {{-- Puntos de condiciones adicionales --}}
                        @if ($d['extras'] !== [])
                            <span style="display:flex; gap:2px;">
                                @foreach ($d['extras'] as $extra)
                                    <i style="width:6px;height:6px;border-radius:9999px;background:{{ $extra }};display:inline-block;"></i>
                                @endforeach
                            </span>
                        @endif

                        @if ($d['hecho']) <span class="odo-check">✓</span> @endif
                    </button>
                @endforeach
            @endforeach
        </div>
    @endforeach

    <div class="odo-titulo">Arcada inferior</div>

    {{-- Leyenda de condiciones --}}
    <div class="odo-leyenda">
        @foreach (EvaluacionDetalle::CONDICIONES as $clave => $info)
            <span>
                @if ($clave === 'ausente')
                    <i class="odo-chip" style="border-style: dashed;"></i>
                @else
                    <i class="odo-chip" style="background: {{ $info['color'] }}cc; border-color: {{ $info['color'] }};"></i>
                @endif
                {{ $info['label'] }}
            </span>
        @endforeach
        <span><i class="odo-chip" style="background:#16a34a;border-color:#16a34a;color:#fff;font-size:8px;text-align:center;line-height:11px;">✓</i> Tratado</span>
    </div>
    </div>{{-- /.odo-chart --}}

    {{-- Panel de la pieza seleccionada (columna derecha) --}}
    <div class="odo-panel-col">
    @if ($piezaSeleccionada)
        @php $filas = $this->condicionesRowsDe($piezaSeleccionada); @endphp
        <div class="odo-panel">
            <h4>Pieza {{ $piezaSeleccionada }} — {{ ucfirst($this->tipoDe($piezaSeleccionada)) }}</h4>

            {{-- Espejo de la hoja: lo que el doctor escribió en la hoja para este diente --}}
            @if ($this->tieneHoja($piezaSeleccionada))
                <div style="display:flex; align-items:flex-start; gap:.5rem; margin-bottom:.9rem;
                            border:1px solid rgb(148 163 184 / .5); border-radius:.55rem; padding:.55rem .75rem;
                            background:rgb(148 163 184 / .08);">
                    <span style="font-size:.95rem;">📋</span>
                    <div style="min-width:0; word-break:break-word;">
                        <div style="font-size:.72rem; font-weight:700; opacity:.7;">
                            De la hoja de evaluación
                            @if ($this->hojaHechoDe($piezaSeleccionada))
                                <span style="color:#16a34a;">· ✓ Hecho</span>
                            @endif
                        </div>
                        @if ($this->hojaTextoDe($piezaSeleccionada))
                            <div style="font-size:.86rem; margin-top:.15rem;">{{ $this->hojaTextoDe($piezaSeleccionada) }}</div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- El odontograma es de SEGUIMIENTO: las condiciones se registran
                 desde la hoja de evaluación (Diagnóstico). Aquí solo se les da
                 seguimiento: editar nota, marcar tratada, tamaño, archivar. --}}
            @if ($this->puedeEditar)
                <div style="font-size:.76rem; opacity:.6; margin-bottom:.8rem;">
                    Las condiciones se registran desde la <strong>hoja de evaluación</strong> (botón Diagnóstico).
                    Acá las seguís: editás la nota, marcás si ya se realizó y archivás.
                </div>
            @endif

            {{-- Historial de condiciones de la pieza (log) --}}
            <div style="display:flex; flex-direction:column; gap:.4rem;
                        {{ $this->puedeEditar ? 'border-top:1px solid rgb(113 113 122 / .3); padding-top:.8rem;' : '' }}">
                @forelse ($filas as $fila)
                    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem;
                                border:1px solid rgb(113 113 122 / .3); border-radius:.55rem; padding:.5rem .7rem;">
                        <div style="min-width:0; flex:1;">
                            <div style="display:flex; align-items:center; gap:.4rem; font-weight:600; font-size:.86rem;">
                                @if ($fila->color)
                                    <i class="odo-chip" style="background: {{ $fila->color }}cc; border-color: {{ $fila->color }};"></i>
                                @else
                                    <i class="odo-chip" style="border-style: dashed;"></i>
                                @endif
                                {{ $fila->etiqueta }}
                                @if ($fila->tratada)
                                    <span style="font-size:.7rem; color:#16a34a; font-weight:700;">✓ tratada</span>
                                @else
                                    <span style="font-size:.7rem; color:#d97706; font-weight:700;">● pendiente</span>
                                @endif
                            </div>

                            @if ($editandoId === $fila->id)
                                <div style="margin-top:.4rem;">
                                    <textarea rows="2" wire:model="editNota"
                                        placeholder="Nota de esta condición. Ej: caries oclusal profunda, cara mesial..."></textarea>
                                    @error('editNota')
                                        <span style="font-size:.72rem; color:#dc2626;">{{ $message }}</span>
                                    @enderror
                                    <div style="display:flex; gap:.4rem; margin-top:.35rem;">
                                        <button type="button" class="odo-btn" style="padding:.25rem .8rem; font-size:.78rem;"
                                            wire:click="guardarNota">Guardar nota</button>
                                        <button type="button" wire:click="cancelarNota"
                                            style="border:1px solid rgb(113 113 122 / .4); border-radius:.45rem; padding:.25rem .8rem; font-size:.78rem; cursor:pointer; background:transparent; color:inherit;">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            @elseif ($tratandoId === $fila->id)
                                <div style="margin-top:.4rem;">
                                    <textarea rows="2" wire:model="notaTratamiento"
                                        placeholder="Nota del tratamiento realizado (opcional). Ej: obturación con resina, cara oclusal..."></textarea>
                                    @error('notaTratamiento')
                                        <span style="font-size:.72rem; color:#dc2626;">{{ $message }}</span>
                                    @enderror
                                    <div style="display:flex; gap:.4rem; margin-top:.35rem;">
                                        <button type="button" class="odo-btn" style="padding:.25rem .8rem; font-size:.78rem;"
                                            wire:click="confirmarTratamiento">Confirmar tratada</button>
                                        <button type="button" wire:click="cancelarTratamiento"
                                            style="border:1px solid rgb(113 113 122 / .4); border-radius:.45rem; padding:.25rem .8rem; font-size:.78rem; cursor:pointer; background:transparent; color:inherit;">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            @else
                                @if ($fila->nota)
                                    <div style="font-size:.8rem; opacity:.85; margin-top:.2rem;">{{ $fila->nota }}</div>
                                @endif
                                @if ($fila->nota_tratamiento)
                                    <div style="font-size:.8rem; margin-top:.2rem; color:#16a34a;">
                                        Tratamiento: {{ $fila->nota_tratamiento }}
                                    </div>
                                @endif
                                <div style="font-size:.72rem; opacity:.6; margin-top:.2rem;">
                                    Detectada: {{ optional($fila->detectada_en)->format('d/m/Y') ?? '—' }}
                                    @if ($fila->tratada_en) · Tratada: {{ $fila->tratada_en->format('d/m/Y') }} @endif
                                </div>

                                {{-- Tamaño de la condición (Pequeña / Mediana / Grande) --}}
                                @if ($this->puedeEditar)
                                    <div style="display:flex; align-items:center; gap:.3rem; margin-top:.35rem; flex-wrap:wrap;">
                                        <span style="font-size:.7rem; opacity:.6;">Tamaño:</span>
                                        @foreach ($this->catalogoTamanos as $clave => $label)
                                            <button type="button"
                                                wire:click="cambiarTamano({{ $fila->id }}, {{ $fila->tamano === $clave ? 'null' : "'".$clave."'" }})"
                                                style="border:1px solid {{ $fila->tamano === $clave ? '#2563eb' : 'rgb(113 113 122 / .4)' }};
                                                       border-radius:.4rem; padding:.1rem .5rem; font-size:.7rem; cursor:pointer;
                                                       background:{{ $fila->tamano === $clave ? 'rgb(37 99 235 / .15)' : 'transparent' }}; color:inherit;">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif ($fila->tamano)
                                    <div style="font-size:.72rem; opacity:.7; margin-top:.2rem;">Tamaño: {{ $fila->tamano_label }}</div>
                                @endif
                            @endif
                        </div>

                        @if ($this->puedeEditar && $editandoId !== $fila->id && $tratandoId !== $fila->id)
                            <div style="display:flex; gap:.35rem; flex-shrink:0;">
                                @if ($fila->tratada)
                                    <button type="button" title="Marcar pendiente"
                                        wire:click="alternarTratada({{ $fila->id }})"
                                        style="border:1px solid rgb(113 113 122 / .4); border-radius:.4rem; padding:.2rem .5rem; font-size:.78rem; cursor:pointer; background:transparent; color:inherit;">
                                        ↩
                                    </button>
                                @else
                                    <button type="button" title="Marcar tratada"
                                        wire:click="iniciarTratamiento({{ $fila->id }})"
                                        style="border:1px solid #16a34a; border-radius:.4rem; padding:.2rem .5rem; font-size:.78rem; cursor:pointer; background:transparent; color:#16a34a;">
                                        ✓
                                    </button>
                                @endif
                                <button type="button" title="{{ $fila->nota ? 'Editar nota' : 'Agregar nota' }}"
                                    wire:click="editarNota({{ $fila->id }})"
                                    style="border:1px solid rgb(113 113 122 / .4); border-radius:.4rem; padding:.2rem .5rem; font-size:.78rem; cursor:pointer; background:transparent; color:inherit;">
                                    ✎
                                </button>
                                @if ($fila->tratada)
                                    <span title="Marcala como no hecha (↩) para poder archivarla"
                                        style="border:1px solid rgb(113 113 122 / .3); border-radius:.4rem; padding:.2rem .5rem; font-size:.78rem; opacity:.4; cursor:not-allowed;">
                                        🗑
                                    </span>
                                @else
                                    <button type="button" title="Archivar condición"
                                        wire:click="eliminarCondicion({{ $fila->id }})"
                                        wire:confirm="¿Archivar esta condición de la pieza {{ $piezaSeleccionada }}?"
                                        style="border:1px solid #dc2626; border-radius:.4rem; padding:.2rem .5rem; font-size:.78rem; cursor:pointer; background:transparent; color:#dc2626;">
                                        🗑
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p style="font-size:.82rem; opacity:.6;">Sin condiciones registradas en esta pieza.</p>
                @endforelse
            </div>
        </div>
    @else
        <div style="border:1px dashed rgb(113 113 122 / .4); border-radius:.75rem; padding:1.5rem 1rem; text-align:center;">
            <div style="font-size:1.6rem; opacity:.35;">🦷</div>
            <p style="font-size:.85rem; opacity:.6; margin-top:.4rem;">
                Haz clic en un diente para ver y registrar sus condiciones.
            </p>
        </div>
    @endif
    </div>{{-- /.odo-panel-col --}}
    </div>{{-- /.odo-layout --}}
</div>
