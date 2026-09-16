var Iplat = (function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || $('meta[name="csrf-token"]').attr('content');

    function ajaxRequest(url, method, data, onSuccess, onError) {
        $.ajax({
            url: url,
            type: method,
            data: data,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                if (onSuccess) onSuccess(response);
            },
            error: function (xhr) {
                var msg = (window.iplatTranslations && window.iplatTranslations.errorOccurred) || 'An error occurred';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || msg;
                    if (xhr.responseJSON.errors) {
                        var first = Object.values(xhr.responseJSON.errors)[0];
                        if (Array.isArray(first)) msg = first[0];
                    }
                }
                showToast(msg, 'danger', { position: 'center', autohide: false });
                if (onError) onError(xhr);
            }
        });
    }

    function showToast(message, type, opts) {
        type = type || 'success';
        opts = opts || {};
        var position = opts.position || 'right';
        var autohide = opts.autohide !== undefined ? opts.autohide : true;
        var delay = opts.delay !== undefined ? opts.delay : 4000;

        var toastId = 'toast-' + Date.now();
        var iconMap = {
            success: 'bi-check-circle-fill',
            danger: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill'
        };
        var html = '<div id="' + toastId + '" class="toast align-items-center text-bg-' + type + ' border-0" role="alert">' +
            '<div class="d-flex">' +
            '<div class="toast-body"><i class="bi ' + (iconMap[type] || iconMap.info) + ' me-2"></i>' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div></div>';

        var positionMap = {
            'top-right':  'top:20px;right:20px;',
            'top-left':   'top:20px;left:20px;',
            'top-center': 'top:20px;left:50%;transform:translateX(-50%);',
            'center':     'top:80px;left:50%;transform:translateX(-50%);',
            'right':      'top:80px;right:20px;',
            'left':       'top:80px;left:20px;'
        };

        var containerId = 'toast-container-' + position;
        var $container = $('#' + containerId);
        if (!$container.length) {
            var posStyle = positionMap[position] || positionMap['top-right'];
            $container = $('<div id="' + containerId + '" style="position:fixed;' + posStyle + 'z-index:9999;min-width:300px;"></div>').appendTo('body');
        }

        $container.empty();

        var $toast = $(html).appendTo($container);
        var bsToast = new bootstrap.Toast($toast[0], { autohide: autohide, delay: delay });
        bsToast.show();
        $toast.on('hidden.bs.toast', function () { $toast.remove(); });
    }

    function resetModal(modalEl) {
        var $form = $(modalEl).find('form');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        $form.find('.text-danger').remove();
    }

    function showValidationErrors(modalEl, errors) {
        var $form = $(modalEl).find('form');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        $.each(errors, function (field, msgs) {
            var $input = $form.find('[name="' + field + '"]');
            $input.addClass('is-invalid');
            var $feedback = $('#' + field + 'Error');
            if ($feedback.length) {
                $feedback.text(msgs[0]);
            } else {
                $input.after('<div class="invalid-feedback">' + msgs[0] + '</div>');
            }
        });
    }

    function showBadgeList(url, title, emptyText, itemKey, cols, box) {
        var $body = $('#badgeListBody');
        if (!$body.length) return;
        if (!box) {
            $body.removeClass('border rounded p-3');
        } else {
            $body.addClass('border rounded p-3');
        }
        var loadingText = (window.iplatTranslations && window.iplatTranslations.loading) || 'Loading...';
        if (title) $('#badgeListModalTitle').text(title);
        $body.html('<div class="py-4"></div><div class="text-center"><div class="spinner-border spinner-border-sm me-2"></div>' + escHtml(loadingText) + '</div>');
        new bootstrap.Modal('#badgeListModal').show();
        $.get(url).done(function(res) {
            var items = (res && res.data && (res.data[itemKey || 'items'] || []));
            if (!items.length) {
                $body.html('<div class="text-center text-muted py-4">' + escHtml(emptyText || '') + '</div>');
                return;
            }
            if (cols) {
                var colCls = 'col-md-' + Math.floor(12 / cols);
                var html = items.map(function(item) {
                    var isObj = typeof item === 'object' && item && item.name;
                    var inner = isObj
                        ? '<div class="fw-semibold">' + escHtml(item.name) + '</div>' +
                          (item.email ? '<div class="text-muted small">' + escHtml(item.email) + '</div>' : '')
                        : escHtml(item);
                    return '<div class="col-12 ' + colCls + '"><div class="border rounded bg-light p-2 h-100 badge-list-cell">' + inner + '</div></div>';
                }).join('');
                $body.html('<div class="row g-2">' + html + '</div>');
            } else {
                var html = items.map(function(item) {
                    if (typeof item === 'object' && item && item.name) {
                        return '<span class="badge badge-list-item bg-light border text-dark p-3 me-1 mb-1 text-start">' +
                            escHtml(item.name) +
                            (item.email ? '<br><small class="text-muted">' + escHtml(item.email) + '</small>' : '') +
                            '</span>';
                    }
                    return '<span class="badge badge-list-item bg-light border text-dark p-2 me-1 mb-1">' + escHtml(item) + '</span>';
                }).join('');
                $body.html('<div class="d-flex flex-wrap">' + html + '</div>');
            }
        }).fail(function() {
            var msg = (window.iplatTranslations && window.iplatTranslations.errorOccurred) || 'An error occurred';
            $body.html('<div class="text-center text-danger py-4">' + escHtml(msg) + '</div>');
        });
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

    var defaultDeleteTitle = null;
    var defaultWarningTitle = null;

    function confirmDelete(url, onSuccess, name, message, title) {
        var $modal = $('#confirmDeleteModal');
        var body;
        if (typeof message === 'string' && message.trim()) {
            body = name ? message.replace(':name', escHtml(name)) : message;
        } else if (name) {
            var tpl = (window.iplatTranslations && window.iplatTranslations.confirmDeleteWith) || 'Delete <strong>:name</strong>?';
            body = tpl.replace(':name', escHtml(name));
        } else {
            body = $('#confirmDeleteDefaultMessage').val();
        }
        var $title = $modal.find('#confirmDeleteModalTitle');
        if (defaultDeleteTitle === null) defaultDeleteTitle = $title.text();
        $title.text(typeof title === 'string' && title.trim() ? title : defaultDeleteTitle);
        $modal.find('.modal-body p').html(body);
        $modal.data('delete-url', url)
              .data('on-success', onSuccess)
              .data('delete-name', name || '');
        new bootstrap.Modal($modal[0]).show();
    }

    function confirmWarning(message, url, onSuccess, name, deleteMessage, title) {
        $('#confirmWarningMessage').html(message);
        var $title = $('#confirmWarningModalTitle');
        if (defaultWarningTitle === null) defaultWarningTitle = $title.text();
        $title.text(typeof title === 'string' && title.trim() ? title : defaultWarningTitle);
        var $modal = $('#confirmWarningModal');
        new bootstrap.Modal($modal[0]).show();
        $('#btnConfirmWarning').off('click').on('click', function () {
            bootstrap.Modal.getInstance($modal[0]).hide();
            confirmDelete(url, onSuccess, name, deleteMessage, title);
        });
    }

    $(document).on('hidden.bs.modal', '.modal', function () {
        $('[id^="toast-container-"]').each(function () {
            $(this).find('.toast').each(function () {
                if (!$(this).hasClass('text-bg-success')) {
                    var bsToast = bootstrap.Toast.getInstance(this);
                    if (bsToast) bsToast.hide();
                }
            });
        });
    });

    return {
        ajax: ajaxRequest,
        toast: showToast,
        resetModal: resetModal,
        showErrors: showValidationErrors,
        confirmDelete: confirmDelete,
        confirmWarning: confirmWarning,
        showBadgeList: showBadgeList
    };
})();
