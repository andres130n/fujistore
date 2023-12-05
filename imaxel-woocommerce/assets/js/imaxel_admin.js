jQuery(document).ready( function() {
   jQuery("#btnImaxelUpdateProducts").click( function() {
      jQuery("#btnImaxelUpdateProducts").hide();
      jQuery(".imx-loader").show();
      jQuery.ajax({
         url : ajax_object.url,
         type : 'POST',
         datatype: 'json',
         data : {
            action: 'imaxel_update_products'
	     },
         success: function(imaxelresponse,myAjax) {
            location.reload();
         },
         error: function(imaxelresponse,myAjax) {
	     	console.log(imaxelresponse);
	     }
      });
	  return false;
   })

   jQuery(".imaxel-btn-duplicate, .imaxel-btn-edit, .imaxel-btn-delete").click( function() {

      jQuery(this).parent().children().hide();
      jQuery(this).parent().append('<div class="imx-loader"></div>');
      var action="imaxel_admin_duplicate_project";
      if(jQuery(this).attr("class")=="imaxel-btn-edit")
         action="imaxel_admin_edit_project";
      if(jQuery(this).attr("class")=="imaxel-btn-delete")
         action="imaxel_admin_delete_project";

      var projectID = jQuery(this).closest("tr").attr("id");
      projectID=projectID.split("-")[1];
      var backURL=ajax_object.backurl;
      var returnURL=ajax_object.returnurl;
      jQuery.ajax({
         url : ajax_object.url,
         type : 'POST',
         datatype: 'json',
         data : {
            action: action,
            projectID:projectID,
            backURL:backURL,
            returnURL:returnURL
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
   });

});
