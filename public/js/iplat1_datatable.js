var IplatDataTable = (function () {

    var defaults = {
        processing: false,
        serverSide: false,
        scrollX: true,
        fixedColumns: { right: 1 },
        info: true,
        layout: {
            topStart: 'pageLength',
            topEnd: null
        },
        language: {
            info: window.dtLangDefaults?.info || 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: window.dtLangDefaults?.infoEmpty || 'No entries available',
            infoFiltered: window.dtLangDefaults?.infoFiltered || '(filtered from _MAX_ total entries)',
            lengthMenu: window.dtLangDefaults?.lengthMenu || 'Show _MENU_ entries per page',
            emptyTable: window.dtLangDefaults?.emptyTable || '<div class="text-muted py-3"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No data available</div>',
            zeroRecords: window.dtLangDefaults?.zeroRecords || '<div class="text-muted py-3"><i class="bi bi-search fs-4 d-block mb-2"></i>No matching records found</div>',
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
