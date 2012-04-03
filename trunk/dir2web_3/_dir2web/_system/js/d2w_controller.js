/*
* Main Script
*/
$(function()
{
    $('body').wrapInner('<div id="_d2w_cbox"></div>');

    $('body').append('<div id="_d2w_obox"></div>');   

    d2w_overlay ();
});


 
 
/*------------------------------------
 * Functions
  -------------------------------------*/
 
function d2w_overlay ()
{
    var winWidth = $(window).width();
    var winHeight = $(window).height();

    $("a[rel='#overlay']").click ( function (event)
    {
        event.preventDefault();
        
        var content = $(this).attr('href');
        var scrTop = $(this).offset().top-300;
      
        $("#_d2w_cbox").fadeOut(1000, function ()
        {
            $("#_d2w_obox").html("<div id=\"_d2w_loader\"><img src=\"_dir2web/_system/default/images/loader.gif\" alt=\"Wait!\"/></div>");
            $("#_d2w_obox").fadeIn('1000');

            $("#_d2w_obox").load (content, function ()
            {
                // set the click event for "Close"
                $("#_d2w_obj_close").click( function ()
                {
                    $('#_d2w_obox').fadeOut('slow', function(){
                        $('#_d2w_cbox').fadeIn(1000);
                                                                        
                        $('html,body').animate({scrollTop: scrTop},'slow');
                    });
                })

            });
        });


    });


 }
	 
	
	 