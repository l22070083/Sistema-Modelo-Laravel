@extends('layout')
@section('title', 'Iniciar sesión · Escuela Modelo')
@section('content')
<div class="site-login-wrapper {{ ($administrative??false)?'administrative-login':'student-login' }}">
<section class="login-card">
<div class="login-logo"><img src="{{ asset('img/logo-escuela-modelo.png') }}" alt="Escuela Modelo Valladolid"></div>
<h1 class="login-title">{{ ($administrative??false)?'Escuela Modelo':'Universidad Modelo' }}<br>Valladolid</h1><div class="login-divider"></div>
<p class="login-subtitle">{{ ($administrative??false)?'Sistema de Gestión Psicopedagógica':'Portal Estudiantil · Acceso Seguro' }}</p>
@if($administrative??false)@include('partials.flash')@endif
<form method="post" action="{{ route('login.submit') }}">@csrf
<label class="login-label" for="username">Usuario</label><div class="login-input-icon"><x-icon name="person"/><input class="form-control login-input" id="username" name="username" value="{{ old('username') }}" required autocomplete="username" maxlength="255"></div>
<label class="login-label" for="password">Contraseña</label><div class="login-input-icon"><x-icon name="lock"/><input class="form-control login-input" type="password" id="password" name="password" required autocomplete="current-password"><button class="password-toggle" type="button" aria-label="Mostrar contraseña" aria-pressed="false"><x-icon name="eye"/></button></div>
<div class="login-extra-row"><a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a></div>
<button class="btn login-btn"><x-icon name="enter"/>Iniciar sesión</button>
</form>
@include('partials.microsoft-button')
@unless($administrative??false)<p class="login-register">¿Aún no tienes cuenta? <a href="{{ route('registro') }}">Regístrate</a></p>@endunless
<div class="login-footer-note"><x-icon name="school"/>Sistema AcompañaTE-UMV</div>
</section>
@if($administrative??false)<p class="login-copyright">© {{ date('Y') }} Escuela Modelo Valladolid</p>@endif
</div>
@endsection
@push('scripts')
<script>document.querySelector('.password-toggle').addEventListener('click',function(){const input=document.getElementById('password'),visible=input.type==='password';input.type=visible?'text':'password';this.setAttribute('aria-label',visible?'Ocultar contraseña':'Mostrar contraseña');this.setAttribute('aria-pressed',String(visible));});</script>
@endpush
