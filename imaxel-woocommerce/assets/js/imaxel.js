jQuery(document).ready( function() {
/**/
   jQuery("a.editor_imaxel").click( function() {


      jQuery("a.editor_imaxel").hide();
      jQuery(".imx-loader").show();

      var productID = jQuery(this).attr("data-productid");
      var backURL=ajax_object.backurl;
      jQuery.ajax({
         url : ajax_object.url,
         type : 'POST',
         datatype: 'json',
         data : {
            action: 'imaxel_wrapper',
            productID:productID,
            backURL:backURL
	     },
         success: function(imaxelresponse,myAjax) {
	        //console.log(myAjax);
            if(myAjax == "success") {
               console.log(imaxelresponse);
               window.location.replace(imaxelresponse);
            }
            else {
               console.log(imaxelresponse);
               window.location.replace(imaxelresponse);
            }
         },
         error: function(imaxelresponse,myAjax) {
	     	console.log(imaxelresponse);
	     	//window.location.replace(imaxelresponse);
	     }
      })   
	  return false;
   })
   
       
	
});