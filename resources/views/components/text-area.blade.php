@props([
    'name',
    'label' => null,
    'placeholder' => null,
    'rows' => 4,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'id' => null,
    'class' => ''
])

@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
    $inputClass = 'block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 ' . $class;
    if ($hasError) {
        $inputClass .= ' border-red-500 focus:ring-red-500 focus:border-red-500';
    }
@endphp

<div class="mb-4">
    @if($label)
        <label for="{{ $id }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif
    
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ $attributes->merge(['class' => $inputClass]) }}
    >{{ old($name, $value) }}</textarea>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>