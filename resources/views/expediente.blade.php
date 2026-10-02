@extends('layout')
@section('content')
<div class="dossier-wrap">
@if(!$record)<section class="card card-body p-4"><h1 class="h3">Mi expediente médico y psicológico</h1><p>Aún no has llenado tu expediente.</p><a href="{{ route('mi-expediente.editar') }}" class="btn btn-primary align-self-start">Llenar expediente</a></section>
@else
<section class="dossier-header">
<div class="dossier-header-top"><span class="dossier-avatar"><x-icon name="person"/></span><div>
<h1>{{ isset($sections['personales'])?trim($record->nombres.' '.$record->apellidos):'Expediente #'.$record->id }}</h1>
<div class="dossier-meta">@if(isset($sections['personales']))<span>Matrícula: <strong>{{ $student?->matricula?:'N/I' }}</strong></span><span>Licenciatura: <strong>{{ $degree?:'N/I' }}</strong></span><span>Edad: <strong>{{ $record->edad?:'N/I' }} años</strong></span>@endif
@if(isset($sections['antecedentes']))<span>Sangre: <strong>{{ $record->apnp_tipo_sangre }} {{ $record->apnp_factor_rh }}</strong></span>@endif</div>
</div></div>
<div class="dossier-header-actions">
@if($own || \App\Services\SectionAccess::can('personales'))<a class="btn btn-outline-light" href="{{ $own?route('mi-constancia'):route('expediente.constancia',$record->id) }}">Imprimir constancia</a>@endif
@if(!$record->archivado_at && ($own?!$record->bloqueado:\App\Services\SectionAccess::can('personales',true)||\App\Services\SectionAccess::can('antecedentes',true)||\App\Services\SectionAccess::can('cuestionario',true)))<a class="btn btn-warning ms-2" href="{{ $own?route('mi-expediente.editar'):route('expediente.editar',$record->id) }}">Modificar / Editar</a>@endif
<span class="dossier-status {{ !$record->bloqueado&&!$record->archivado_at?'editable':'' }}">{{ $record->archivado_at?'Expediente archivado':($record->bloqueado?'Expediente Protegido por Coordinación':'Expediente Registrado') }}</span>
@if($record->bloqueado&&$own)<span>Solicita a tu coordinador una actualización si necesitas corregir información.</span>@endif
<small class="dossier-registered">Registrado el {{ \Carbon\Carbon::parse($record->created_at)->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</small>
</div></section>
<div class="dossier-grid"><aside class="dossier-side">
@if($own||isset($sections['clasificacion']))<section class="dossier-card"><h2>Clasificación de Atención</h2><div class="dossier-card-body">
@forelse($categories as $category)<div class="category-chip" style="--category-color:{{ ['Sin dato de alarma'=>'#e5f700','Atención Psicopedagógica'=>'#ff9b00','Salud Física'=>'#ff70ce','Atención Emocional'=>'#77ff66'][$category]??'#aaa' }}"><span class="dot"></span>{{ $category }}</div>@empty<p>Sin clasificación registrada.</p>@endforelse
@if($record->atencion_prioritaria)<span class="badge bg-danger">Atención prioritaria</span>@endif
@if($record->categoria_manual)<small class="d-block text-muted mt-2">Valoración institucional</small>@endif
</div></section>@endif
@if(isset($sections['personales']))<section class="dossier-card emergency"><h2>Contacto de Emergencia</h2><div class="dossier-card-body"><strong>{{ $record->contacto_emergencia_nombre?:'N/I' }}</strong><div>Parentesco: {{ $record->contacto_emergencia_parentesco?:'N/I' }}</div><span class="emergency-phone">Tel: {{ $record->contacto_emergencia_telefono?:'N/I' }}</span></div></section>@endif
@if(isset($sections['antecedentes']))<section class="dossier-card clinical"><h2>Parámetros Clínicos Críticos</h2><div class="dossier-card-body"><p><strong>Grupo Sanguíneo:</strong> <span class="badge bg-danger">{{ $record->apnp_tipo_sangre }} {{ $record->apnp_factor_rh }}</span></p><strong>Alergias Conocidas:</strong><p class="dossier-summary-text">{{ $record->app_alergias?:'Sin información' }}</p><strong>Enfermedades Crónicas:</strong><p class="dossier-summary-text">{{ $record->app_enfermedades_cronicas?:'Sin información' }}</p>@if(isset($sections['personales']))<strong>Creencias / Restricciones Médicas:</strong><p class="text-danger mb-0">{{ $record->religion?:'Ninguna especificada' }}</p>@endif</div></section>@endif
@if($own||isset($sections['bitacora']))<section class="dossier-card revisions"><h2>Historial de Revisiones</h2><div class="dossier-card-body">@forelse($revisions as $revision)<div class="revision-entry"><strong>{{ ['REGISTRO_INICIAL'=>'Registro inicial','EDICION_ALUMNO'=>'Actualización por Alumno','EDICION_INSTITUCIONAL'=>'Actualización institucional','CLASIFICAR'=>'Clasificación','NOTA'=>'Seguimiento'][ $revision->accion ]??str_replace('_',' ',$revision->accion) }}</strong><small>{{ \Carbon\Carbon::parse($revision->fecha)->locale('es')->diffForHumans() }}</small></div>@empty<p class="mb-0 text-muted">Sin revisiones registradas.</p>@endforelse</div></section>@endif
</aside><section class="dossier-card"><h2>Contenido Detallado del Expediente</h2><div class="dossier-content-body">
@php
    $detailGroups=[];
    if(isset($sections['personales'])) $detailGroups['1. Datos Personales y Contacto']=$sections['personales'];
    if(isset($sections['cuestionario'])) $detailGroups['2. Cuestionario de Salud y Bienestar Estudiantil']=$sections['cuestionario'];
    if(isset($sections['antecedentes'])) {
        $detailGroups['3. Antecedentes Personales No Patológicos (APNP)']=array_filter($sections['antecedentes'],fn($field)=>str_starts_with($field,'apnp_'));
        $detailGroups['4. Antecedentes Personales Patológicos (APP)']=array_filter($sections['antecedentes'],fn($field)=>str_starts_with($field,'app_'));
    }
    if(isset($sections['notas'])) $detailGroups['Notas de Seguimiento']=$sections['notas'];
@endphp
@foreach($detailGroups as $title=>$fields)<details class="dossier-section" @if($loop->first) open @endif><summary>{{ $title }}</summary><dl class="dossier-field-grid">
@foreach($fields as $field)
@php($value=$record->$field)
<div class="{{ in_array($field,['domicilio','q8_red_apoyo','q12_info_adicional','notas_coordinador'])?'wide-field':'' }}"><dt>{{ config('dossier.labels.'.$field,$field) }}</dt><dd>
@if(preg_match('/^q[1-7]_.*(?<!detalle)$/',$field)||$field==='q9_acomp_psicologico')<span class="badge {{ $value?'bg-danger':'bg-success' }}">{{ $value?'Sí':'No' }}</span>
@elseif($field==='licenciatura_id'){{ $degree?:'N/I' }}
@elseif($field==='q11_necesita_apoyo'){{ implode(', ',json_decode($value?:'[]',true)?:[]) }}
@else{{ $value!==null&&$value!==''?$value:'—' }}@endif
</dd></div>
@endforeach</dl></details>@endforeach
@if(isset($sections['clasificacion']))<details class="dossier-section"><summary>Valoración de Atención</summary><div class="p-3 small"><p>Clasificación automática: {{ implode(', ',\App\Services\DossierRules::categories($record->categoria_atencion)) }}</p><p>Clasificación institucional: {{ $record->categoria_manual?implode(', ',$categories):'Sin valoración manual' }}</p><p>Motivo: {{ $record->motivo_clasificacion?:'—' }}</p></div></details>@endif
@if(isset($sections['bitacora']))<details class="dossier-section"><summary>Bitácora de Revisiones</summary><ul class="p-4 small">@foreach($history as $entry)<li>{{ $entry->fecha }} — {{ $entry->accion }}: {{ $entry->detalles }}</li>@endforeach</ul></details>@endif
</div></section></div>
<div class="dossier-actions">
<form class="card card-body mb-3" method="post" action="{{ auth()->user()->rol_id===3?route('mi-expediente.pdf'):route('expediente.pdf',$record->id) }}">@csrf
@if(auth()->user()->rol_id!==3)<label>Motivo de exportación<textarea class="form-control mb-3" name="motivo" required maxlength="2000"></textarea></label>@endif
<button class="btn btn-outline-primary">Exportar expediente en PDF</button></form>
@if(auth()->user()->rol_id!==3)
@foreach(['nota'=>'Agregar nota','clasificar'=>'Clasificar','bloqueo'=>'Bloquear o desbloquear','archivar'=>'Archivar','restaurar'=>'Restaurar'] as $action=>$label)
@if(in_array($action,['bloqueo','archivar','restaurar'])?auth()->user()->rol_id===1:\App\Services\SectionAccess::can($action==='nota'?'notas':'clasificacion',true))
<form class="card card-body mb-3" method="post" action="{{ route('expediente.accion',[$record->id,$action]) }}">@csrf<h2 class="h5">{{ $label }}</h2>
@if($action==='nota')<label>Nota<textarea class="form-control mb-3" name="nota" required maxlength="10000"></textarea></label>@endif
@if($action==='clasificar')
<p>La valoración institucional prevalece sobre la clasificación automática, incluso cuando el alumno actualice sus respuestas.</p>
@foreach(['Sin dato de alarma','Atención Psicopedagógica','Salud Física','Atención Emocional'] as $category)<label><input type="checkbox" name="categorias[]" value="{{ $category }}" @checked(in_array($category,json_decode($record->categoria_manual?:$record->categoria_atencion,true)?:[],true))> {{ $category }}</label>@endforeach
<input type="hidden" name="prioritaria" value="0"><label><input type="checkbox" name="prioritaria" value="1" @checked($record->atencion_prioritaria)> Requiere atención prioritaria</label>
@endif
@if($action==='bloqueo')<label>Estado<select class="form-select" name="bloqueado"><option value="1">Bloqueado</option><option value="0">Editable por el alumno</option></select></label>@endif
<label>Motivo<textarea name="motivo" class="form-control my-3" required maxlength="2000"></textarea></label><button class="btn btn-secondary">{{ $label }}</button></form>
@endif @endforeach
@endif
</div>
@endif
</div>
@endsection
