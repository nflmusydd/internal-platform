@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/iplat1_flatpickr.css') }}?v={{ filemtime(public_path('css/iplat1_flatpickr.css')) }}">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.4/css/fixedColumns.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('css/iplat1_datatable.css') }}?v={{ filemtime(public_path('css/iplat1_datatable.css')) }}">
<link rel="stylesheet" href="{{ asset('css/iplat1_filter.css') }}?v={{ filemtime(public_path('css/iplat1_filter.css')) }}">
@endpush

@push('scripts')
<script>
    window.dtLangDefaults = {
        info: @json(__('general.dt_info_showing')),
        infoEmpty: @json(__('general.dt_info_empty')),
        infoFiltered: @json(__('general.dt_info_filtered')),
        lengthMenu: @json(__('general.dt_length_menu')),
        zeroRecords: @json(__('general.dt_zero_records')),
        emptyTable: @json(__('general.dt_empty_table'))
    };
</script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.min.js"></script>
<script src="{{ asset('js/iplat1_datatable.js') }}?v={{ filemtime(public_path('js/iplat1_datatable.js')) }}"></script>
<script src="{{ asset('js/iplat1_filter.js') }}?v={{ filemtime(public_path('js/iplat1_filter.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script src="{{ asset('js/iplat1_flatpickr.js') }}?v={{ filemtime(public_path('js/iplat1_flatpickr.js')) }}"></script>
@endpush