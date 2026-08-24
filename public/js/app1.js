$(function () {

    $(document).on('show.bs.modal', '.modal', function () {
        var $dialog = $(this).find('.modal-dialog');
        if ($dialog.data('original-centered')) {
            $dialog.addClass('modal-dialog-centered');
        }
        $dialog.css({ position: '', left: '', top: '', margin: '' });
    });

    $(document).on('mousedown', '.modal-header', function (e) {
        var $dialog = $(this).closest('.modal-dialog');
        var $modal = $dialog.closest('.modal');

        if ($dialog.hasClass('modal-dialog-centered')) {
            $dialog.data('original-centered', true);
            $dialog.removeClass('modal-dialog-centered').css({
                'margin-top': $modal.scrollTop() + 50 + 'px',
                'align-self': 'flex-start'
            });
        }

        var startX = e.pageX - $dialog.offset().left;
        var startY = e.pageY - $dialog.offset().top;

        $(document).on('mousemove.dragmodal', function (e) {
            $dialog.css({
                position: 'relative',
                left: e.pageX - startX - $dialog.parent().offset().left + 'px',
                top: e.pageY - startY - $dialog.parent().offset().top + 'px',
                margin: '0'
            });
        });

        $(document).one('mouseup', function () {
            $(document).off('mousemove.dragmodal');
        });

        e.preventDefault();
    });

});
