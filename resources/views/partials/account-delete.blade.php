<form method="post" class="d-inline-block ms-2" action="{{ route($deleteRoute, $account->id) }}" onsubmit="return confirm('¿Eliminar esta cuenta del sistema? Se bloqueará su acceso y no podrá reactivarse desde el listado. El historial institucional se conserva.');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
</form>