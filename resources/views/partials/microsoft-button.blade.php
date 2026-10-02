@if(config('services.microsoft.enabled'))
<a class="btn microsoft-btn" href="{{ route('microsoft.login') }}"><span class="microsoft-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>Continuar con Microsoft</a>
@else
<button class="btn microsoft-btn" type="button" disabled aria-describedby="microsoft-status"><span class="microsoft-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>Continuar con Microsoft</button><p id="microsoft-status" class="text-muted small text-center mt-2 mb-0">Acceso institucional pendiente de activación.</p>
@endif
