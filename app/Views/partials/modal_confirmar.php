<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="modalConfirmarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modalConfirmarTitulo">Confirmar acción</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" data-modal-mensaje></p>
                <div class="mt-3 d-none" data-modal-motivo>
                    <label for="modalMotivo" class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                    <textarea id="modalMotivo" class="form-control" rows="3" maxlength="300" placeholder="Explica el motivo (mínimo 10 caracteres)"></textarea>
                    <div class="invalid-feedback">Escribe un motivo de al menos 10 caracteres.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-modal-aceptar>Confirmar</button>
            </div>
        </div>
    </div>
</div>
