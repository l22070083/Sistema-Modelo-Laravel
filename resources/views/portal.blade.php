@extends('layout')
@section('title', 'Portal Estudiantil · Escuela Modelo')
@section('content')
<section class="student-hero">
<span class="hero-badge">Certificado y Confidencial</span>
<h1>Detección de Riesgos y Salud Estudiantil</h1>
<p class="hero-description">Estimado(a) estudiante: con el objetivo de salvaguardar tu bienestar y brindarte apoyo oportuno en caso de emergencia dentro de las instalaciones universitarias o actividades académicas, te solicitamos completar de manera honesta el presente cuestionario. La información proporcionada será tratada con estricta confidencialidad y utilizada únicamente para fines de prevención, atención y respuesta ante situaciones de emergencia.</p>
<div class="hero-confidential"><strong>Importante:</strong> Tus respuestas son <strong>totalmente confidenciales.</strong></div>
@guest
<p class="hero-invitation">Para comenzar la encuesta, por favor accede a tu cuenta:</p>
<div class="hero-actions"><a class="btn btn-primary" href="{{ route('login') }}">Iniciar Sesión</a><a class="btn btn-outline-primary" href="{{ route('registro') }}">Registrarse</a></div>
@else
<h2 class="student-welcome">¡Hola, {{ auth()->user()->nombre }}!</h2><p class="hero-invitation">Consulta y completa tu expediente de salud.</p>
<div class="hero-actions"><a class="btn btn-primary" href="{{ route('mi-expediente') }}">Mi expediente</a><a class="btn btn-outline-primary" href="{{ route('encuesta.responder') }}">Responder encuesta de salud</a></div>
@endguest
</section>
@endsection
