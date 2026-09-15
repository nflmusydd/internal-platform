/* ==============================
   IplatFilter
   ============================== */
var IplatFilter = (function () {

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
