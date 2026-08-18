<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ ucfirst(__('general.confirm_delete_title')) }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{{ __('general.confirm_delete') }}</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                <button type="button" class="btn btn-sm btn-danger" id="btnConfirmDelete">
                    <i class="bi bi-trash me-1"></i>{{ ucfirst(__('general.delete')) }}
                </button>
            </div>
        </div>
    </div>
</div>
