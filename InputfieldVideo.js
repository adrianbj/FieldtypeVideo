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

})(jQuery);
