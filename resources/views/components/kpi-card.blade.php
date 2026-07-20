@props(['label', 'value', 'icon', 'color' => 'primary'])
<div class="card h-100 border-0 shadow-sm">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $color }}-subtle text-{{ $color }}-emphasis flex-shrink-0"
             style="width: 2.75rem; height: 2.75rem; font-size: 1.1rem;">
            <i class="bi {{ $icon }}"></i>
        </div>
        <div class="overflow-hidden">
            <div class="text-secondary small">{{ $label }}</div>
            <div class="fs-5 fw-semibold text-truncate">{{ $value }}</div>
        </div>
    </div>
</div>
