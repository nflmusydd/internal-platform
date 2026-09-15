/* ==============================
   Flatpickr Initialization
   ============================== */
$(function() {
    if (typeof flatpickr === 'undefined') return;

    var monthNames = window.iplatTranslations.months || ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var weekdaysShort = window.iplatTranslations.weekdaysShort || ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    flatpickr.localize(window.appLocale || 'id');
    var fpInstances = flatpickr('.filter-date', {
        dateFormat: 'd/m/Y',
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
        if (key.endsWith('_from')) {
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
