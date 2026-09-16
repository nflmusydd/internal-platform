<div class="modal fade" id="badgeListModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green">
                    <i class="bi bi-tags me-2"></i><span id="badgeListModalTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="badgeListBody" class="border rounded p-3" style="max-height:400px;overflow-y:auto;overflow-x:hidden;"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .badge-list-cell {
        min-width: 0;
        overflow-wrap: break-word;
        word-break: break-word;
    }
    .badge-list-item {
        max-width: 100%;
        overflow-wrap: break-word;
    }
</style>
@endpush