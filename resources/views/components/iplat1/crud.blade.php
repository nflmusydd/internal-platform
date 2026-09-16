@push('scripts')
<script>
    window.appLocale = @json(app()->getLocale());
    window.iplatTranslations = {
        errorOccurred: @json(__('general.error_occurred')),
        saving: @json(__('general.saving')),
        deleting: @json(__('general.deleting')),
        loading: @json(__('general.loading')),
        confirmDeleteWith: @json(__('general.confirm_delete_with')),
        months: @json(__('general.months')),
        weekdaysShort: @json(__('general.weekdays_short')),
    };
</script>
<script src="{{ asset('js/iplat1_crud.js') }}?v={{ filemtime(public_path('js/iplat1_crud.js')) }}"></script>
@endpush