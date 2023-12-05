jQuery(document).ready( function() {

   jQuery(".imaxel-btn-edit").click( function() {

      jQuery(this).parent().children().hide();
      jQuery(this).parent().append('<div class="imx-loader"></div>');
      var projectID = jQuery(this).closest("tr").attr("id");

      projectID=projectID.split("-")[1];
      var backURL=ajax_object.backurl;
      jQuery.ajax({
         url : ajax_object.url,
         type : 'POST',
         datatype: 'json',
         data : {
            action: 'imaxel_edit_project',
            projectID:projectID,
            backURL:backURL
	     },
         success: function(imaxelresponse,myAjax) {
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
	     }
      })   
	  return false;
   })

   jQuery(".imaxel-btn-duplicate").click( function() {

      jQuery(this).parent().children().hide();
      jQuery(this).parent().append('<div class="imx-loader"></div>');
      var projectID = jQuery(this).closest("tr").attr("id");

      projectID=projectID.split("-")[1];
      var backURL=ajax_object.backurl;
      jQuery.ajax({
         url : ajax_object.url,
         type : 'POST',
         datatype: 'json',
         data : {
            action: 'imaxel_duplicate_project',
            projectID:projectID,
            backURL:backURL
         },
         success: function(imaxelresponse,myAjax) {
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
         }
      })
      return false;
   })

   jQuery(".imaxel-btn-delete").click( function() {

       var r = confirm(ajax_object.literal_delete_warning);
       if (r == true) {
           jQuery(this).parent().children().hide();
           jQuery(this).parent().append('<div class="imx-loader"></div>');
           var projectID = jQuery(this).closest("tr").attr("id");
           projectID=projectID.split("-")[1];
           var backURL=ajax_object.backurl;
           jQuery.ajax({
               url : ajax_object.url,
               type : 'POST',
               datatype: 'json',
               data : {
                   action: 'imaxel_delete_project',
                   projectID:projectID,
                   backURL:backURL
               },
               success: function(imaxelresponse,myAjax) {
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
               }
           })
       } else {
       }
      return false;

   })

});