(function($) {

    function initImagePickers($context) {
        $context.find('select.image-picker').each(function() {
            var $select = $(this);
            if($select.data('imagepicker-initialized')) return;
            $select.imagepicker();
            $select.data('imagepicker-initialized', true);
        });
    }

    $(document).ready(function() {
        initImagePickers($(document));
    });

    // Re-initialize when inputfields are reloaded via AJAX (repeaters, etc.)
    $(document).on('reloaded', '.Inputfield', function() {
        initImagePickers($(this));
    });

    // InputfieldFile inserts uploaded item markup directly and only triggers
    // `reloaded` when that markup contains nested Inputfields. The poster
    // picker is direct item markup, so initialize it on upload completion.
    $(document).on('AjaxUploadDone', '.InputfieldFileList', function() {
        initImagePickers($(this));
    });

})(jQuery);
