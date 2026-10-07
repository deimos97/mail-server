@props(['name'])
@error($name)
    <p id="{{ $name }}-error" class="mt-2 text-sm font-medium text-rojo" role="alert">{{ $message }}</p>
@enderror
