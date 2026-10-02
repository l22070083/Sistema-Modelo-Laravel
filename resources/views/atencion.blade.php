@extends('layout')
@section('content')<h1>Atención estudiantil</h1>
@foreach(['Sin dato de alarma'=>'#ffff00','Atención Psicopedagógica'=>'#ff9b00','Salud Física'=>'#ff70ce','Atención Emocional'=>'#77ff66'] as $category=>$color)
<section class="card card-body mb-3"><h2 class="h5" style="background:{{ $color }};padding:10px">{{ $category }}</h2><ul>
@foreach($rows as $row)@if(in_array($category,json_decode($row->categoria_manual?:$row->categoria_atencion,true)?:[],true))<li><a href="{{ route('expediente.ver',$row->id) }}">{{ $row->nombres }} {{ $row->apellidos }}</a> {{ $row->atencion_prioritaria?'— Atención prioritaria':'' }}</li>@endif @endforeach
</ul></section>@endforeach
@endsection
