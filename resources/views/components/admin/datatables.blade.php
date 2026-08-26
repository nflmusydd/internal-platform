@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.4/css/fixedColumns.bootstrap5.min.css">
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
<script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.min.js"></script>
<script src="{{ asset('js/admin-datatables.js') }}?v={{ filemtime(public_path('js/admin-datatables.js')) }}"></script>
@endpush
