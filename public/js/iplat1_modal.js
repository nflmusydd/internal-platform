$(function () {

    // Reset posisi ke tengah saat modal ditutup
    $(document).on('hidden.bs.modal', '.modal', function () {
        var $content = $(this).find('.modal-content');
        $content.css({ transform: '' });
        $content.removeData('pos-x').removeData('pos-y'); // Reset memori posisi
    });

    // Logika Dragging
    $(document).on('mousedown', '.modal-header', function (e) {
        var $content = $(this).closest('.modal-content');
        
        // Ambil posisi pergeseran sebelumnya (jika modal sudah pernah didrag)
        var currentX = parseFloat($content.data('pos-x')) || 0;
        var currentY = parseFloat($content.data('pos-y')) || 0;

        // koordinat awal mouse saat diklik
        var startX = e.pageX;
        var startY = e.pageY;

        $(this).css('cursor', 'move');

        $(document).on('mousemove.dragmodal', function (e) {
            // Hitung selisih jarak tarikan mouse
            var dx = e.pageX - startX;
            var dy = e.pageY - startY;

            // Geser modal menggunakan CSS Transform (GPU Accelerated & sangat smooth)
            $content.css({
                transform: 'translate(' + (currentX + dx) + 'px, ' + (currentY + dy) + 'px)'
            });
        });

        // Saat klik dilepas
        $(document).one('mouseup', function (e) {
            $(document).off('mousemove.dragmodal'); // Hapus event mousemove
            
            // Simpan posisi terakhir ke memori
            var dx = e.pageX - startX;
            var dy = e.pageY - startY;
            $content.data('pos-x', currentX + dx);
            $content.data('pos-y', currentY + dy);
        });
    });

});