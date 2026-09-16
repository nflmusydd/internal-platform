<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
<h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><span id="confirmDeleteModalTitle">{{ ucfirst(__('general.confirm_delete_title')) }}</span>
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
<input type="hidden" id="confirmDeleteDefaultMessage" value="{{ __('general.confirm_delete') }}">

<div class="modal fade" id="confirmWarningModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
<h5 class="modal-title fw-bold" style="color:#e67700;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><span id="confirmWarningModalTitle">{{ ucfirst(__('general.confirm_delete_title')) }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="confirmWarningMessage"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                <button type="button" class="btn btn-sm fw-semibold" style="background-color:#e67700;color:#fff;" id="btnConfirmWarning">
                    <i class="bi bi-arrow-right me-1"></i>{{ ucfirst(__('general.continue')) }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    if (typeof Iplat === 'undefined') return;

    var $modal = $('#confirmDeleteModal');
    var $btn = $('#btnConfirmDelete');
    var originalDeleteHtml = $btn.html();

    $btn.on('click', function () {
        var deletingText = (window.iplatTranslations && window.iplatTranslations.deleting) || 'Deleting...';
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + deletingText);
        Iplat.ajax($modal.data('delete-url'), 'DELETE', {}, function (res) {
            Iplat.toast(res.message, 'success');
            bootstrap.Modal.getInstance($modal[0]).hide();
            var callback = $modal.data('on-success');
            if (callback) callback();
        }, function () {
            $btn.prop('disabled', false).html(originalDeleteHtml);
        });
    });

    $modal.on('hidden.bs.modal', function () {
        $btn.prop('disabled', false).html(originalDeleteHtml);
    });
});
</script>
@endpush