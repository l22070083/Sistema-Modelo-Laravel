@props(['label' => 'Tabla de datos'])
<div {{ $attributes->class(['table-responsive']) }} role="region" aria-label="{{ $label }}" tabindex="0">{{ $slot }}</div>
