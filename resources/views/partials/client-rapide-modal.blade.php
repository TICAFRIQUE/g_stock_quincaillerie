<div class="modal fade" id="clientRapideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="clientRapideNom" class="form-label">Nom<span class="required-marker">*</span></label>
                    <input type="text" id="clientRapideNom" class="form-control" required>
                    <div class="invalid-feedback" id="clientRapideNomErreur"></div>
                </div>
                <div class="mb-3">
                    <label for="clientRapideTelephone" class="form-label">Téléphone</label>
                    <input type="text" id="clientRapideTelephone" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="clientRapideBouton" onclick="window.soumettreClientRapide()">
                    Créer et sélectionner
                </button>
            </div>
        </div>
    </div>
</div>
