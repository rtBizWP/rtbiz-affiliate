jQuery(document).ready( function(){
    //datepicker
    jQuery("#date").datetimepicker(  {dateFormat: 'yy-mm-dd', timeFormat: 'HH:mm:ss', showSecond: true} );
    
//    jQuery('#user').suggest(ajaxurl + '?action=rt_aff_users_lookup');
    
    jQuery("#user").autocomplete({
        source: function( request, response ) {
        jQuery.ajax({
          url: ajaxurl,
          dataType: "json",
          type:'post',
          data: {
            action: "rt_aff_users_lookup",
            maxRows: 10,
            query: request.term
          },
          success: function( data ) {
            response( jQuery.map( data, function( item ) {
              return {
                id: item.id,
                login_name: item.login_name,
                imghtml: item.imghtml,
                name:item.name
              }
            }));
          }
        });
      },minLength: 2,
        select: function(event, ui) {
            jQuery("#user").val(ui.item.login_name);
            jQuery("#user_id").val(ui.item.id);
            return false;
        }
    }).data("ui-autocomplete")._renderItem = function(ul, item) {
        return jQuery("<li></li>").data("ui-autocomplete-item", item).append("<a>" + item.imghtml + "&nbsp;" + item.name + "</a>").appendTo(ul);
    };
    
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
