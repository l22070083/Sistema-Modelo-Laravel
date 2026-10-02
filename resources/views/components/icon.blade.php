@props(['name'])
<svg {{ $attributes->merge(['class' => 'modelo-icon']) }} aria-hidden="true" focusable="false"><use href="#icon-{{ $name }}"></use></svg>
