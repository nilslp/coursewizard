jQuery(document).ready(function(){
   
   lpscheduler_remove_option_selected_attr();
    
    function lpscheduler_remove_option_selected_attr(){
           var $ = jQuery;
    
        $('.scheduler-select').change(function(){
          //  var option = $(this '.selected-option').each().children().attr('selected');
        //    if(option == 'selected'){
                
                //var not_selected = $(this).children('option.notselected-option');
               // var chosen_option =  $(this).children('option.notselected-option').click().val();
                
              
                
                $(this).children('option.selected-option').removeAttr('selected');
                
                $(this).children('option.notselected-option').click().attr('SELECTED','yes');
         //   }
        });
    }
    
});