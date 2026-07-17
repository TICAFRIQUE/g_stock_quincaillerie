@props([
    'action',
    'method' => 'POST',
    'message' => 'Confirmer cette action ?',
    'buttonLabel' => 'Confirmer',
    'buttonClass' => 'btn-primary',
    'icon' => 'bi-check-circle',
])

@php $formId = 'confirm-form-'.\Illuminate\Support\Str::random(8); @endphp

<form id="{{ $formId }}" method="POST" action="{{ $action }}" class="d-none">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
</form>

<button type="button" class="btn {{ $buttonClass }}"
        data-bs-toggle="modal" data-bs-target="#confirmActionModal"
        data-form-id="{{ $formId }}" data-message="{{ $message }}"
        data-button-label="{{ $buttonLabel }}" data-button-class="{{ $buttonClass }}">
    <i class="bi {{ $icon }} me-1"></i>{{ $buttonLabel }}
</button>
