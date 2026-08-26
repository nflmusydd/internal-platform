var AdminDataTable = (function () {

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


/* ==============================
   AdminSearchFilter
   ============================== */
var AdminSearchFilter = (function () {

    var state = {};

    function toggle(barId) {
        barId = barId.replace(/^#/, '');
        var $bar = $('#' + barId);
        var $btn = $('[data-target="' + barId + '"]');
        $bar.slideToggle(150, function () {
            $btn.toggleClass('active', $bar.is(':visible'));
        });
    }

    function populateSelect(barId, filterKey, values) {
        var $dropdown = $('#' + barId + ' .custom-filter-dropdown').has('input[data-filter="' + filterKey + '"]');
        if (!$dropdown.length) return;

        var $menu = $dropdown.find('.dropdown-menu');
        var $firstItem = $menu.find('li:first').clone();
        $firstItem.find('.dropdown-item').addClass('active');
        
        $menu.empty().append($firstItem);
        $.each(values, function (i, v) {
            if (v !== '' && v !== null && v !== undefined) {
                $menu.append('<li><a class="dropdown-item" href="javascript:void(0)" data-value="' + v + '">' + v + '</a></li>');
            }
        });
    }

    function init(tableId, config) {
        var barId = 'filterBar-' + config.id;
        var $bar = $('#' + barId);
        var table = config.table;
        var columnMap = config.columnMap || {};
        var customFilters = config.customFilters || {};
        var onStateChange = config.onStateChange || null;

        if (!state[config.id]) {
            state[config.id] = {};
        }

        $.each(columnMap, function (key) {
            if (!state[config.id].hasOwnProperty(key)) {
                state[config.id][key] = '';
            }
        });
        $.each(customFilters, function (key) {
            if (!state[config.id].hasOwnProperty(key)) {
                state[config.id][key] = '';
            }
        });

        // Handle text inputs
        $bar.on('input change', 'input[type="text"]', function () {
            applyFilters(config.id, table, columnMap, customFilters, onStateChange);
        });

        // Handle custom dropdowns
        $bar.on('click', '.dropdown-item', function (e) {
            e.preventDefault();
            var $item = $(this);
            var $dropdown = $item.closest('.custom-filter-dropdown');
            var $input = $dropdown.find('input[type="hidden"]');
            var val = $item.data('value');

            $input.val(val);
            $dropdown.find('.filter-dropdown-text').text($item.text());
            $dropdown.find('.dropdown-item').removeClass('active');
            $item.addClass('active');

            applyFilters(config.id, table, columnMap, customFilters, onStateChange);
        });

        $bar.find('.filter-reset').on('click', function () {
            reset(config.id, table, columnMap, customFilters, onStateChange);
        });
    }

    function applyFilters(id, table, columnMap, customFilters, onStateChange) {
        var barId = 'filterBar-' + id;
        var $bar = $('#' + barId);

        $.each(columnMap, function (key, colIdx) {
            var val = $bar.find('[data-filter="' + key + '"]').val() || '';
            state[id][key] = val;
            table.column(colIdx).search(val);
        });

        $.each(customFilters, function (key, fn) {
            var val = $bar.find('[data-filter="' + key + '"]').val() || '';
            state[id][key] = val;
        });

        table.draw();
        if (onStateChange) onStateChange(state[id]);
    }

    function reset(id, table, columnMap, customFilters, onStateChange) {
        var barId = 'filterBar-' + id;
        var $bar = $('#' + barId);

        $bar.find('input[type="text"]').val('');
        $bar.find('input[type="hidden"]').val('');

        // Clear Flatpickr instances
        $bar.find('.filter-date').each(function() {
            if (this._flatpickr) {
                this._flatpickr.clear();
                this._flatpickr.set('minDate', null);
                this._flatpickr.set('maxDate', null);
            }
        });
        
        // Reset dropdown buttons to their first option text
        $bar.find('.custom-filter-dropdown').each(function() {
            var $dropdown = $(this);
            var $firstItem = $dropdown.find('.dropdown-item:first');
            $dropdown.find('.filter-dropdown-text').text($firstItem.text());
            $dropdown.find('.dropdown-item').removeClass('active');
            $firstItem.addClass('active');
        });

        $.each(columnMap, function (key) {
            state[id][key] = '';
            table.column(columnMap[key]).search('');
        });
        $.each(customFilters, function (key) {
            state[id][key] = '';
        });

        table.draw();
        if (onStateChange) onStateChange(state[id]);
    }

    function getState(id) {
        return state[id] || {};
    }

    function registerCustomSearch(key, fn) {
        var exists = $.fn.dataTable.ext.search.some(function (s) {
            return s._filterKey === key;
        });
        if (exists) return;

        var wrapper = function (settings, data, dataIndex) {
            var tableId = settings.sTableId;
            var barId = 'filterBar-' + settings._searchFilterId;
            if (!barId || !$('#' + barId).length) return true;

            var val = state[settings._searchFilterId]?.[key] || '';
            if (val === '') return true;
            return fn(val, settings, data, dataIndex);
        };
        wrapper._filterKey = key;
        $.fn.dataTable.ext.search.push(wrapper);
    }

    return {
        toggle: toggle,
        init: init,
        getState: getState,
        populateSelect: populateSelect,
        registerCustomSearch: registerCustomSearch
    };
})();

/* ==============================
   Flatpickr Initialization
   ============================== */
$(function() {
    if (typeof flatpickr === 'undefined') return;

    var monthNames = window.adminTranslations.months || ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var weekdaysShort = window.adminTranslations.weekdaysShort || ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    flatpickr.localize(window.appLocale || 'id');
    var fpInstances = flatpickr('.filter-date', {
        dateFormat: 'd/m/Y',
        // maxDate: 'today',
        clickOpens: true,
        disableMobile: true,
        allowInput: false,
        locale: window.appLocale || 'id',
        onReady: function(sel, dateStr, fp) {
            buildMonthDropdown(fp);
        },
        onClose: function(sel, dateStr, fp) {
            if (fp._fpMonthDropdown) {
                fp._fpMonthDropdown.menu.hide();
                fp._fpMonthDropdown.wrapper.removeClass('open');
            }
        },
        onMonthChange: function(sel, dateStr, fp) {
            syncMonthDropdown(fp);
        },
        onYearChange: function(sel, dateStr, fp) {
            syncMonthDropdown(fp);
        },
        onChange: function(sel, dateStr, fp) {
            syncMonthDropdown(fp);
            $(fp.input).trigger('change');
            if (fp._updateDatePairLimits) fp._updateDatePairLimits();
        }
    });

    if (fpInstances) {
        var arr = Array.isArray(fpInstances) ? fpInstances : [fpInstances];
        $.each(arr, function(_, fp) { setupDatePair(fp); });
    }

    function buildMonthDropdown(fp) {
        var $select = $(fp.calendarContainer).find('.flatpickr-monthDropdown-months');
        if (!$select.length) return;

        var idx = fp.currentMonth;

        var $wrapper = $('<div class="fp-month-dropdown"></div>');
        var $btn = $('<button type="button" class="fp-month-dropdown-btn">' +
                     monthNames[idx] + ' <i class="bi bi-chevron-down fp-month-chevron"></i></button>');
        var $menu = $('<div class="fp-month-dropdown-menu"></div>');

        $.each(monthNames, function(i, name) {
            var cls = 'fp-month-dropdown-item' + (i === idx ? ' active' : '');
            $menu.append('<a class="' + cls + '" href="javascript:void(0)" data-month="' + i + '">' + name + '</a>');
        });

        $menu.appendTo('body');

        function positionMenu() {
            var rect = $btn[0].getBoundingClientRect();
            $menu.css({
                position: 'fixed',
                top: rect.bottom + 4,
                left: rect.left
            });
        }

        function openMenu() {
            positionMenu();
            $menu.show();
            $wrapper.addClass('open');
        }

        function closeMenu() {
            $menu.hide();
            $wrapper.removeClass('open');
        }

        $btn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if ($wrapper.hasClass('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        $menu.on('click mousedown', function(e) {
            e.stopPropagation();
        });

        $menu.on('click', '.fp-month-dropdown-item', function(e) {
            e.preventDefault();
            var targetMonth = $(this).data('month');
            fp.changeMonth(targetMonth, false);
            closeMenu();
        });

        $(fp.calendarContainer).on('mousedown', function() {
            closeMenu();
        });

        $wrapper.append($btn);
        $wrapper.insertBefore($select);
        $select.hide();

        fp._fpMonthDropdown = { wrapper: $wrapper, btn: $btn, menu: $menu };
    }

    function syncMonthDropdown(fp) {
        if (!fp._fpMonthDropdown) return;
        var m = fp._fpMonthDropdown;
        var idx = fp.currentMonth;
        m.btn.html(monthNames[idx] + ' <i class="bi bi-chevron-down fp-month-chevron"></i>');
        m.menu.find('.fp-month-dropdown-item').removeClass('active')
            .eq(idx).addClass('active');
        m.menu.hide();
        m.wrapper.removeClass('open');
    }

    function parseFilterDate(val) {
        if (!val) return null;
        var parts = val.split('/');
        if (parts.length !== 3) return null;
        return new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
    }

    function setupDatePair(fp) {
        var key = fp.input.dataset.filter;
        if (!key) return;

        var pairKey = null;
        if (key.endsWith('_from')) {        // pakai .indexOf('_from') jika key _from tidak harus di akhir
            pairKey = key.replace(/_from$/, '_to'); 
        } else if (key.endsWith('_to')) {
            pairKey = key.replace(/_to$/, '_from');
        }
        if (!pairKey) return;

        var $pairedInput = $('.filter-date[data-filter="' + pairKey + '"]');
        if (!$pairedInput.length) return;
        var pairedFp = $pairedInput[0]._flatpickr;
        if (!pairedFp) return;

        function updatePairLimits() {
            var val = fp.input.value;
            var date = parseFilterDate(val);

            if (key.indexOf('_from') !== -1) {
                pairedFp.set('minDate', date || null);
                if (date && pairedFp.selectedDates[0] && pairedFp.selectedDates[0] < date) {
                    pairedFp.clear();
                }
            } else {
                pairedFp.set('maxDate', date || null);
                if (date && pairedFp.selectedDates[0] && pairedFp.selectedDates[0] > date) {
                    pairedFp.clear();
                }
            }
        }

        fp._updateDatePairLimits = updatePairLimits;
    }
});
