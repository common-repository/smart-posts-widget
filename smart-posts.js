/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery(function () {
    //hookup the event    
   jQuery("#smart-posts-widget-choose_cat_tag select").change(function(){
        console.log(jQuery(this).attr("id"));        
        var tagselect =jQuery(this).attr("id").replace( 'cat_tag','selected_tag');
        var catselect =jQuery(this).attr("id").replace( 'cat_tag','cat');        
        //console.log(tagselect);        
        if(jQuery(this).val() ==='cat') {          
            jQuery('.'+catselect).show();
            jQuery('.'+tagselect).hide();
           // jQuery('.'+tagselect).css( "color", "red" );
        }
        else if(jQuery(this).val() ==='tag') {
            jQuery('.'+catselect).hide();
            jQuery('.'+tagselect).show();        
        }
  });
});

function change_smart_postwidget(current_smart_post_wid) {
   alert(current_smart_post_wid);
}