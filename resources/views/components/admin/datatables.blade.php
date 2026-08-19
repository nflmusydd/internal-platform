@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.4/css/fixedColumns.bootstrap5.min.css">
<style>
    /* Pagination (Bootstrap 5 integration) */
    .dt-paging ul.pagination {
        --bs-link-color: #495057;
        --bs-link-hover-color: #043523;
        margin: 0;
        gap: 4px;
        justify-content: flex-end;
    }
    .dt-paging .page-link {
        --bs-link-color: #495057;
        --bs-link-hover-color: #043523;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 34px;
        height: 34px;
        padding: 0 0.6rem !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
        background: #fff !important;
        color: #495057 !important;
        font-size: 0.85rem;
        font-weight: 500;
        line-height: 1;
        cursor: pointer;
        transition: all 0.15s ease;
        box-shadow: none !important;
        text-decoration: none !important;
    }
    .dt-paging .page-link:hover {
        background: #e8f5e9 !important;
        border-color: #043523 !important;
        color: #043523 !important;
    }
    .dt-paging .page-item.active .page-link {
        background: #043523 !important;
        border-color: #043523 !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(4, 53, 35, 0.25) !important;
    }
    .dt-paging .page-item.disabled .page-link {
        background: transparent !important;
        border-color: #dee2e6 !important;
        color: #adb5bd !important;
        cursor: default !important;
        opacity: 0.45;
        pointer-events: none;
    }
    .dt-paging .page-link:focus {
        outline: none !important;
        box-shadow: 0 0 0 2px rgba(4, 53, 35, 0.15) !important;
    }
    .dt-paging .page-link:active {
        transform: scale(0.96);
    }

    /* Info */
    .dataTables_wrapper .dataTables_info {
        font-size: 0.85rem;
        color: #6c757d;
        padding-top: 0.75rem;
    }

    /* Fixed columns */
    .dtfc-fixed-right,
    .dtfc-fixed-right-right {
        background-color: #fff !important;
        box-shadow: -2px 0 4px rgba(0,0,0,0.08);
    }

    /* No-wrap */
    table.dataTable th,
    table.dataTable td {
        white-space: nowrap;
    }

    /* Hide processing overlay */
    .dataTables_wrapper .dataTables_processing {
        display: none !important;
    }

    /* Sorting icons */
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
        opacity: 0.4;
    }
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after {
        opacity: 1;
        color: #fff;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.min.js"></script>
<script src="{{ asset('js/admin-datatables.js') }}"></script>
@endpush
