@props(['checked' => true, 'label' => 'Actif'])

{{--
    Une case décochée n'est jamais envoyée par un formulaire HTML : sans ce
    hidden input, désactiver quelque chose ne se propage jamais au serveur.
--}}
<div class="form-check form-switch mb-4">
    <input type="hidden" name="actif" value="0">
    <input type="checkbox" name="actif" id="actif" class="form-check-input" value="1"
           {{ old('actif', $checked) ? 'checked' : '' }}>
    <label for="actif" class="form-check-label">{{ $label }}</label>
</div>
