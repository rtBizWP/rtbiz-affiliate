jQuery(document).ready( function(){
    //datepicker
    jQuery("#date").datetimepicker(  {dateFormat: 'yy-mm-dd', timeFormat: 'HH:mm:ss', showSecond: true} );
    
    jQuery('#time_action').click(function() {
        time_duration = jQuery('#time_duration').val();
        jQuery.ajax({
            type: "POST",
            url: 'admin-ajax.php',
            data: 'action=rt_affiliate_summary&time_duration='+time_duration,
            success: function(msg){
                jQuery('#rt_stats').html(msg);
            }
        });
    });
});
