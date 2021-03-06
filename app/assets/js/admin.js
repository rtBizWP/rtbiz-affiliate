jQuery(document).ready( function(){
    //datepicker
    jQuery("#date").datetimepicker(  {dateFormat: 'yy-mm-dd', timeFormat: 'HH:mm:ss', showSecond: true} );
    if(jQuery("#user").length > 0) {
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
            return jQuery("<li></li>").data("ui-autocomplete-item", item).append("<a class='rt-aff-user-ac'>" + item.imghtml + "&nbsp;" + item.name + "</a>").appendTo(ul);
        };
    }
    
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
    jQuery(".rtAff-delete-payment").click(function(e){
       e.preventDefault();
       if(confirm("Are you sure you want to delete this record ?")) {
           window.location  = jQuery(this).data("href");
        }
    });
});
