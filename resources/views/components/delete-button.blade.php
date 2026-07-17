@props(['action', 'label' => 'cet élément'])

@php $formId = 'delete-form-'.\Illuminate\Support\Str::random(8); @endphp

<form id="{{ $formId }}" method="POST" action="{{ $action }}" class="d-none">
    @csrf
    @method('DELETE')
</form>

<button type="button" class="btn btn-sm btn-icon btn-outline-danger" title="Supprimer"
        data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
        data-form-id="{{ $formId }}" data-label="{{ $label }}">
    <i class="bi bi-trash"></i>
    <span class="visually-hidden">Supprimer</span>
</button>
