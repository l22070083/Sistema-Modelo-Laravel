@foreach(['nombre' => 'Nombre', 'apellidos' => 'Apellidos', 'username' => 'Usuario', 'email' => 'Correo'] as $field => $label)
<div class="col-md-6">
    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
    <input class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}" type="{{ $field === 'email' ? 'email' : 'text' }}" maxlength="255" @required($field !== 'apellidos' || ($requiredSurname ?? false)) @if($field === 'username') autocomplete="username" @endif>
</div>
@endforeach
<div class="col-md-6">
    <label class="form-label" for="password">Contraseña</label>
    <input class="form-control" id="password" name="password" type="password" required autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="password-help">
    <small id="password-help">Al menos 8 caracteres, una mayúscula y un carácter especial.</small>
</div>
<div class="col-md-6">
    <label class="form-label" for="password_confirmation">Confirma la contraseña</label>
    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" minlength="8" maxlength="72">
</div>
