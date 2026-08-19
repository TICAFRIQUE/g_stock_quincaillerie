@props(['label', 'value', 'icon', 'color' => 'primary', 'compact' => false])
<div class="card h-100 border-0 shadow-sm">
    <div class="card-body d-flex align-items-center {{ $compact ? 'gap-2 p-2' : 'gap-3' }}">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $color }}-subtle text-{{ $color }}-emphasis flex-shrink-0"
             style="width: {{ $compact ? '2.25rem' : '2.75rem' }}; height: {{ $compact ? '2.25rem' : '2.75rem' }}; font-size: {{ $compact ? '1rem' : '1.1rem' }};">
            <i class="bi {{ $icon }}"></i>
        </div>
        <div class="overflow-hidden">
            <div class="text-secondary {{ $compact ? 'small' : '' }}" style="{{ $compact ? 'font-size: .75rem;' : '' }}">{{ $label }}</div>
            <div class="fw-semibold text-truncate {{ $compact ? '' : 'fs-5' }}" style="{{ $compact ? 'font-size: 1.05rem;' : '' }}">{{ $value }}</div>
        </div>
    </div>
</div>
