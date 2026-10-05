@extends('layout')
@section('title', 'Administradores')
@section('content')
<h1>Administradores</h1>
<section class="card card-body mb-4" id="nuevo-administrador">
    <h2 class="h5">Crear administrador</h2>
    <p>La cuenta quedará activa y tendrá acceso a todas las secciones y a la administración de usuarios.</p>
    <form method="post" action="{{ route('administradores.create') }}" class="row g-3">@csrf
        @include('partials.account-fields')
        <div class="col-12"><button class="btn btn-primary">Crear administrador</button></div>
    </form>
</section>
<x-table-scroll label="Listado de administradores">
    <table class="table"><thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Estado</th></tr></thead><tbody>
        @forelse($administradores as $administrator)
        <tr><td>{{ $administrator->nombre }} {{ $administrator->apellidos }}</td><td>{{ $administrator->username }}</td><td>{{ $administrator->email }}</td><td>{{ $administrator->status === \App\Models\User::ACTIVE ? 'Activo' : 'Inactivo' }}</td></tr>
        @empty<tr><td colspan="4">No hay administradores registrados.</td></tr>@endforelse
    </tbody></table>
</x-table-scroll>
{{ $administradores->links() }}
@endsection
