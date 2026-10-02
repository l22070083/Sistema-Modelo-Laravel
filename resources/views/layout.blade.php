@php
    $staff = auth()->check() ? in_array(auth()->user()->rol_id,[1,2],true) : ($administrative ?? false);
    $bareLogin = !auth()->check() && ($administrative ?? false);
@endphp
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Escuela Modelo Valladolid')</title>
<link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/modelo.css') }}">
@stack('styles')
</head><body class="{{ $staff?'staff-shell':'student-shell' }} {{ $bareLogin?'admin-login-page':'' }} {{ request()->routeIs('panel')?'dashboard-page':'' }}">
@include('partials.icons')
@unless($bareLogin)
<header class="modelo-header"><nav class="modelo-nav {{ $staff?'staff-nav':'student-nav' }}" aria-label="Navegación principal">
<a class="modelo-brand" href="{{ route('home') }}"><img src="{{ asset('img/Logo.png') }}" alt="Escudo Escuela Modelo"><span>@if($staff)<strong>ESCUELA MODELO</strong><small>VALLADOLID</small>@else<strong>Universidad Modelo</strong><small>Campus Valladolid • Coordinación</small>@endif</span></a>
<div class="modelo-links">
<a href="{{ route('home') }}" class="{{ request()->routeIs('home','inicio','panel')?'active':'' }}">@if($staff)<x-icon name="home"/>@endif Inicio</a>
@auth
@if($staff)
    @if(auth()->user()->rol_id===1)
        <a href="{{ route('coordinadores') }}"><x-icon name="users"/>Coordinadores</a>
        <a href="{{ route('encuestas') }}"><x-icon name="file"/>Encuestas y preguntas</a>
        @foreach(['grupo'=>'Grupos','licenciatura'=>'Licenciaturas','genero'=>'Géneros'] as $catalog=>$label)<a href="{{ route('catalogo',$catalog) }}"><x-icon name="school"/>{{ $label }}</a>@endforeach
    @endif
    <a href="{{ route('notificaciones') }}">Notificaciones ({{ \App\Models\User::pendientes()->count() }})</a>
    <a href="{{ route('panel') }}" class="nav-gold"><x-icon name="grid"/>Mi Panel</a>
    @if(\App\Services\SectionAccess::can('personales'))
        <a href="{{ route('alumnos') }}"><x-icon name="school"/>Alumnos</a><a href="{{ route('expedientes') }}"><x-icon name="folder"/>Expedientes Clínicos</a><a href="{{ route('reportes') }}"><x-icon name="file"/>Reportes</a>
    @endif
    @if(\App\Services\SectionAccess::can('personales') && \App\Services\SectionAccess::can('clasificacion'))
        <a class="nav-gold" href="{{ route('alertas') }}"><x-icon name="alert"/>Alertas de Salud</a><a class="nav-cyan" href="{{ route('atencion') }}"><x-icon name="pulse"/>Atención Estudiantil</a><a href="{{ route('resultados') }}">Resultados</a>
    @endif
    <a href="{{ route('perfil') }}">Mi cuenta</a>
@else
    <a href="{{ route('mi-expediente') }}" class="{{ request()->routeIs('mi-expediente*','mi-constancia')?'active':'' }}"><x-icon name="file"/>Mi Expediente</a>
    <a href="{{ route('encuesta.responder') }}">Encuesta de salud</a><a href="{{ route('resultados') }}">Mis resultados</a><a href="{{ route('perfil') }}">Mi perfil</a>
@endif
@else
<a href="{{ route('registro') }}">Registrarse</a>
@endauth
</div>
@auth
<form class="nav-session" action="{{ route('logout') }}" method="post">@csrf<button class="btn btn-outline-light btn-sm">{{ $staff?'Salir':'Cerrar sesión' }} ({{ $staff?auth()->user()->username:auth()->user()->nombre }})</button></form>
@else
<a class="btn btn-outline-light btn-sm nav-session" href="{{ route('login') }}">Entrar</a>
@endauth
</nav></header>
@endunless
<main class="modelo-main {{ $bareLogin?'login-main':'' }} {{ request()->routeIs('home','inicio')&&!$staff?'portal-main':'' }}">
@unless($bareLogin)@include('partials.flash')@endunless
@yield('content')
</main>
@unless($bareLogin)<footer class="modelo-footer">© {{ date('Y') }} {{ $staff?'Escuela Modelo Valladolid · Sistema de Gestión de Coordinación':'Universidad Modelo · Campus Valladolid' }} @guest<a href="{{ route('login.administrativo') }}">Acceso administrativo</a>@endguest</footer>@endunless
@stack('scripts')
</body></html>
