@push('styles')
<link rel="stylesheet" href="{{ asset('css/flatpickr/flatpickr.min.css') }}?v={{ filemtime(public_path('css/flatpickr/flatpickr.min.css')) }}">
<link rel="stylesheet" href="{{ asset('css/iplat1_flatpickr.css') }}?v={{ filemtime(public_path('css/iplat1_flatpickr.css')) }}">
<link rel="stylesheet" href="{{ asset('css/datatables/dataTables.bootstrap5.min.css') }}?v={{ filemtime(public_path('css/datatables/dataTables.bootstrap5.min.css')) }}">
<link rel="stylesheet" href="{{ asset('css/datatables/fixedColumns-5.0.4.bootstrap5.min.css') }}?v={{ filemtime(public_path('css/datatables/fixedColumns-5.0.4.bootstrap5.min.css')) }}">
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
<script src="{{ asset('js/datatables/dataTables-2.1.8.min.js') }}?v={{ filemtime(public_path('js/datatables/dataTables-2.1.8.min.js')) }}"></script>
<script src="{{ asset('js/datatables/dataTables-2.1.8.bootstrap5.min.js') }}?v={{ filemtime(public_path('js/datatables/dataTables-2.1.8.bootstrap5.min.js')) }}"></script>
<script src="{{ asset('js/datatables/dataTables.fixedColumns-5.0.4.min.js') }}?v={{ filemtime(public_path('js/datatables/dataTables.fixedColumns-5.0.4.min.js')) }}"></script>
<script src="{{ asset('js/iplat1_datatable.js') }}?v={{ filemtime(public_path('js/iplat1_datatable.js')) }}"></script>
<script src="{{ asset('js/iplat1_filter.js') }}?v={{ filemtime(public_path('js/iplat1_filter.js')) }}"></script>
<script src="{{ asset('js/flatpickr/flatpickr-4.6.13.js') }}?v={{ filemtime(public_path('js/flatpickr/flatpickr-4.6.13.js')) }}"></script>
<script src="{{ asset('js/flatpickr/id.js') }}?v={{ filemtime(public_path('js/flatpickr/id.js')) }}"></script>
<script src="{{ asset('js/iplat1_flatpickr.js') }}?v={{ filemtime(public_path('js/iplat1_flatpickr.js')) }}"></script>
@endpush