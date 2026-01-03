(function($) {
    'use strict';

    $(document).ready(function() {
        var $selectAll = $('<button type="button" class="button button-small">Select All</button>');
        var $selectNone = $('<button type="button" class="button button-small">Select None</button>');
        
        $('.rlm-checkbox-list').each(function() {
            var $list = $(this);
            var $buttons = $('<div class="rlm-checkbox-actions" style="margin-bottom: 10px;"></div>');
            
            $buttons.append($selectAll.clone().on('click', function() {
                $list.find('input[type="checkbox"]').prop('checked', true);
            }));
            
            $buttons.append(' ');
            
            $buttons.append($selectNone.clone().on('click', function() {
                $list.find('input[type="checkbox"]').prop('checked', false);
            }));
            
            $list.before($buttons);
        });

        $('input[name="rlm_date_from"], input[name="rlm_date_to"]').on('change', function() {
            var $from = $('input[name="rlm_date_from"]');
            var $to = $('input[name="rlm_date_to"]');
            
            if ($from.val() && $to.val() && $from.val() > $to.val()) {
                alert('The "Date From" must be before "Date To"');
                $(this).val('');
            }
        });

        var $linksPerPost = $('input[name="rlm_links_per_post"]');
        if ($linksPerPost.length) {
            $linksPerPost.on('change', function() {
                var val = parseInt($(this).val());
                if (val < 1) $(this).val(1);
                if (val > 10) $(this).val(10);
            });
        }
    });

})(jQuery);
