/**
 * Created by cesartenesaca on 13/5/15.
 */

jQuery(document).ready(function($) {

    var iWebWidth = $( window ).width() - 60;
    var iWebHeight = $( window ).height() - 60;
    var messiWin = null;
    var iWebMargin = 5;

    jQuery(".add_photo_product").on("click",function(e){
        //var product_sku = jQuery(this).attr('id');
        console.log("add_photo_product");
        var variation_id = jQuery('input[name=variation_id]').val();
        var data = {
            'action': 'create_product',
            'variationid' : variation_id
        };
        jQuery.post("https://www.fujistore.com.ec/wp-admin/admin-ajax.php", data, function(response) {
            console.log(response.sessionid);
            openIWeb("https://ips1405.imaxel.com/WebCounter/WebCounter.aspx?dlrid=1&tk="+response.sessionid);
        }, "json");
        e.preventDefault();
    });

    jQuery(".iweb-project").on("click",function(e){
        var project_id = jQuery(this).attr('id');
        var data = {
            'action': 'open_project',
            'projectid' : project_id
        };
        jQuery.post("https://www.fujistore.com.ec/wp-admin/admin-ajax.php", data, function(response) {
            openIWeb("https://ips1405.imaxel.com/WebCounter/WebCounter.aspx?dlrid=1&tk="+response.sessionid);
        }, "json");
        e.preventDefault();
    });

    jQuery(".delete-iweb-project").on("click",function(e){
        var project_id = jQuery(this).attr('id');
        var data = {
            'action': 'delete_project',
            'projectid' : project_id
        };
        //console.log(project_id);
        jQuery.post("https://www.fujistore.com.ec/wp-admin/admin-ajax.php", data, function(response) {
            location.reload(true);
            //console.log(project_id);
        }, "json");
        e.preventDefault();
    });

    function openIWeb(url) {

        var messiWidth = iWebWidth+(iWebMargin*2);
        var messiHeight = iWebHeight+(iWebMargin*2);

        jQuery("#embeddedcontainer").html('<div id="embedded" style="position:relative; overflow:hidden; margin:0px; padding:0px; width:'+messiWidth+'px;height:'+messiHeight+'px"></div>');
        var socket = new easyXDM.Socket({
            remote:url,
            container: "embedded",
            onMessage: function(message, origin){
                if(message=="resize"){
                    this.container.getElementsByTagName("iframe")[0].style.height = iWebHeight + "px";
                    this.container.getElementsByTagName("iframe")[0].style.width = iWebWidth + "px";
                    this.container.getElementsByTagName("iframe")[0].style.overflow = "hidden";
                    this.container.getElementsByTagName("iframe")[0].style.position = "absolute";
                    this.container.getElementsByTagName("iframe")[0].style.top = iWebMargin + 'px';
                    this.container.getElementsByTagName("iframe")[0].style.left = iWebMargin + 'px';
                    this.container.getElementsByTagName("iframe")[0].scrolling="no";
                    this.container.getElementsByTagName("iframe")[0].frameborder = 0;
                } else{
                    var array = message.split("&");
                    onEditionFinish(array[0],array[1],array[2]); }
            } });
        messiWin = new Messi(jQuery("#embedded"),
            {   width:messiWidth,
                height:messiHeight,
                modal:true,
                closeButton:false,
                modalOpacity: .4,
                padding:'0px', center:true,
            viewport: {top: (screen.height - messiHeight) / 2, left: (screen.width-messiWidth) / 2}, callback: function(){
                if(socket!=null){ socket.destroy(); socket = null;
                } }
        });

    }

    function onEditionFinish(addToCart, projectid, projectStatus){
        messiWin.hide();
        if(addToCart=='true'){
            addProjectToCart(projectid);
        }else{
            new Messi('Cerrando editor...', {modal:true , closeButton:false, modalOpacity: .1});
            location.reload(true);
        }
    }

    function addProjectToCart(projectid){
        var data = {
            'action': 'add_photo_product_to_cart',
            'projectid' : projectid
        };
        jQuery.post("https://www.fujistore.com.ec/wp-admin/admin-ajax.php", data, function(response) {
            window.location = "https://www.fujistore.com.ec/carro/";
        }, "json");
    }

    //Custom login popup

    function openFancybox() {
        // launches fancybox after half second when called

        console.log("open");
        /*
        setTimeout(function () {
            $('.register-link').trigger('click');
        }, 500);
        */
        zMAjaxLoginRegister.open_register();

    };

    //openFancybox();

    var visited = $.cookie('open_register'); // create the cookie

    console.log(visited);

    if (visited == 'yes') {
        //return false; // second page load, cookie is active so do nothing
        //Disable home register
        //openFancybox();
    } else {
        //openFancybox(); // first page load, launch fancybox
    };

    // assign cookie's value and expiration time
    /*$.cookie('register-opened', 'yes', {
        expires: 7 // the number of days the cookie will be effective
    });*/

});