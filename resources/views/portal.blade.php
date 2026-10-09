@extends('layout')
@section('title', 'Portal Estudiantil · Escuela Modelo')
@section('content')
<section class="student-hero">
<span class="hero-badge">Certificado y Confidencial</span>
<h1>Expediente de Salud Estudiantil</h1>
<p class="hero-description">Estimado(a) estudiante: completa y mantén actualizado tu expediente de salud para contar con información útil en caso de emergencia dentro de las instalaciones universitarias o durante actividades académicas. Tu información será tratada con estricta confidencialidad.</p>
<div class="hero-confidential"><strong>Importante:</strong> Tu información es <strong>totalmente confidencial.</strong></div>
@guest
<p class="hero-invitation">Para consultar o completar tu expediente, accede a tu cuenta:</p>
<div class="hero-actions"><a class="btn btn-primary" href="{{ route('login') }}">Iniciar Sesión</a><a class="btn btn-outline-primary" href="{{ route('registro') }}">Registrarse</a></div>
@else
<h2 class="student-welcome">¡Hola, {{ auth()->user()->nombre }}!</h2><p class="hero-invitation">Consulta y completa tu expediente de salud.</p>
<div class="hero-actions"><a class="btn btn-primary" href="{{ route('mi-expediente') }}">Mi expediente</a></div>
@endguest
</section>
@endsection
