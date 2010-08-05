jQuery(document).ready(function() {
    var validator = jQuery("#rt_aff_contact").validate({
            rules: {
                name:{
                    required: true
                },
                email:{
                    required: true,
                    email:true
                },
                blog_url: {
                    required: true,
                    url: true
                }
            },

            // the errorPlacement has to take the table layout into account
            errorPlacement: function(error, element) {
                if ( element.is(":radio") )
                    error.appendTo( element.parent().next().next() );
                else if ( element.is(":checkbox") )
                    error.appendTo ( element.next() );
                else
                    error.appendTo( element.parent() );
            },
            // set this class to error-labels to indicate valid fields
            success: function(label) {
                // set &nbsp; as text for IE
                //label.html("").removeClass("error");
                label.remove();
                //label.html("&nbsp;").addClass("checked");
            }
        });

        jQuery("#wp_theme").click(function(){
           jQuery("#show_hide").slideToggle('slow');
        });
  });