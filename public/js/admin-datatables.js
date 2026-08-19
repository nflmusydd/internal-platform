var AdminDataTable = (function () {

    var defaults = {
        dom: 'irtip',
        processing: false,
        serverSide: false,
        scrollX: true,
        fixedColumns: { right: 1 },
        info: false,
        language: {
            // loadingRecords: '',
            infoEmpty: 'No entries available',
            infoFiltered: '(filtered from _MAX_ total entries)',
            emptyTable: '<div class="text-muted py-3"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No data available</div>',
            paginate: {
                first: '<i class="bi bi-chevron-double-left"></i>',
                last: '<i class="bi bi-chevron-double-right"></i>',
                next: '<i class="bi bi-chevron-right"></i>',
                previous: '<i class="bi bi-chevron-left"></i>'
            }
        },
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']]
    };

    function init(selector, config) {
        var merged = $.extend(true, {}, defaults, config);
        return $(selector).DataTable(merged);
    }

    return { init: init };
})();
