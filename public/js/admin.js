var Admin = (function () {
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
                var msg = 'An error occured';
                if (xhr.responseJSON) {
                    msg = xhr.responseJSON.message || msg;
                    if (xhr.responseJSON.errors) {
                        var first = Object.values(xhr.responseJSON.errors)[0];
                        if (Array.isArray(first)) msg = first[0];
                    }
                }
                showToast(msg, 'danger');
                if (onError) onError(xhr);
            }
        });
    }

    function showToast(message, type) {
        type = type || 'success';
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

        var $container = $('#toast-container');
        if (!$container.length) {
            $container = $('<div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;"></div>').appendTo('body');
        }
        var $toast = $(html).appendTo($container);
        var bsToast = new bootstrap.Toast($toast[0], { delay: 4000 });
        bsToast.show();
        $toast.on('hidden.bs.toast', function () { $toast.remove(); });
    }

    function resetModal(modalEl) {
        var $form = $(modalEl).find('form');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $form.find('.text-danger').remove();
    }

    function showValidationErrors(modalEl, errors) {
        var $form = $(modalEl).find('form');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $.each(errors, function (field, msgs) {
            var $input = $form.find('[name="' + field + '"]');
            $input.addClass('is-invalid');
            $input.after('<div class="invalid-feedback">' + msgs[0] + '</div>');
        });
    }

    function confirmDelete(url, onSuccess, message) {
        var $modal = $('#confirmDeleteModal');
        if (message) {
            $modal.find('.modal-body p').html(message);
        } else {
            $modal.find('.modal-body p').html($('#confirmDeleteDefaultMessage').val());
        }
        $modal.data('delete-url', url);
        $modal.data('on-success', onSuccess);
        new bootstrap.Modal($modal[0]).show();
    }

    function confirmWarning(message, url, onSuccess) {
        $('#confirmWarningMessage').html(message);
        var $modal = $('#confirmWarningModal');
        new bootstrap.Modal($modal[0]).show();
        $('#btnConfirmWarning').off('click').on('click', function () {
            bootstrap.Modal.getInstance($modal[0]).hide();
            confirmDelete(url, onSuccess);
        });
    }

    return {
        ajax: ajaxRequest,
        toast: showToast,
        resetModal: resetModal,
        showErrors: showValidationErrors,
        confirmDelete: confirmDelete,
        confirmWarning: confirmWarning
    };
})();
