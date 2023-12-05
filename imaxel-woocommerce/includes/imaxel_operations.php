<?php

class ImaxelOperations
{


    public function downloadProducts($publicKey, $privateKey,$endpoint="http://services.imaxel.com/api/v3"){

        $PUBLIC_KEY=$publicKey;
        $PRIVATE_KEY=$privateKey;

        $endpoint.="/products";
        $datetime = new DateTime("".date('y-m-d H:i:s.u'));

        date_add($datetime, date_interval_create_from_date_string('10 minutes'));
        if($PUBLIC_KEY==""){
            return "";
        }
        else{
            $policy ='{
	            "publicKey": "'.$PUBLIC_KEY.'",
	            "expirationDate": "'.$datetime->format('c').'"
	        }';

            $policy = base64_encode($policy);
            $signedPolicy = base64_encode(hash_hmac("SHA256", $policy, $PRIVATE_KEY, true));

            $params = array(
                "policy" => "".$policy."",
                "signedPolicy" => "".urlencode($signedPolicy).""
            );

            $productos = $this->doRequest($endpoint.'?policy='.urlencode($policy).'&signedPolicy='.urlencode($signedPolicy).'');

            if($productos==""){
                downloadProducts($publicKey, $privateKey);
            }else{
                return $productos;
            }
        }
    }


    public function createProject($publicKey, $privateKey,$productCode,$urlCart,$urlCartParameters,$urlCancel,$urlSave,$urlSaveParameters,$endpoint="http://services.imaxel.com/api/v3/"){

        $productIWEB=false;
        if($endpoint!="http://services.imaxel.com/api/v3/") {
            $productIWEB=true;
        }

        $PUBLIC_KEY=$publicKey;
        $PRIVATE_KEY=$privateKey;

        $output='';
        $datetime = new DateTime("".date('y-m-d H:i:s.u'));
        date_add($datetime, date_interval_create_from_date_string('10 minutes'));

        $policy ='{
	        "productCode": "'.$productCode.'",
	        "publicKey": "'.$PUBLIC_KEY.'",
	        "expirationDate": "'.$datetime->format('c').'"
	    }';

        $policy = base64_encode($policy);
        $signedPolicy = base64_encode(hash_hmac("SHA256", $policy, $PRIVATE_KEY, true));

        $params = array(
            "productCode" => "".$productCode."",
            "policy" => "".$policy."",
            "signedPolicy" => "".urlencode($signedPolicy).""
        );

        $newProject = $this->doPost($endpoint."/projects",$params);
        //Comprobar que hay datos

        preg_match('/{\"id\":\"(.+)\",\"app\":{\"id\":\"/', $newProject, $match);
        $newProjectID=(int)$match[1];
        if($newProjectID==0) {
            return null;
        }

        $urlCart.=urlencode($urlCartParameters."&attribute_proyecto=".$newProjectID);
        $urlSave.=urlencode($urlSaveParameters."&attribute_proyecto=".$newProjectID);

        $urlCancel=urlencode($urlCancel);

        if($productIWEB==false) {
            $policy = '{
              "projectId": "' . $newProjectID . '",
              "backURL": "' . $urlCancel . '",
              "addToCartURL": "' . $urlCart . '",
              "publicKey": "' . $PUBLIC_KEY . '",
              "redirect": "1",
              "expirationDate": "' . $datetime->format('c') . '"
		    }';
        }
        else{
            $locale = get_locale();
            $policy = '{
              "projectId": "' . $newProjectID . '",
              "lng":"'.$locale.'",
              "backURL": "' . $urlCancel . '",
              "addToCartURL": "' . $urlCart . '",
              "saveURL": "' . $urlSave . '",
              "publicKey": "' . $PUBLIC_KEY . '",
              "redirect": "1",
              "expirationDate": "' . $datetime->format('c') . '"
		    }';
        }

        $policy = base64_encode($policy);
        $signedPolicy = base64_encode(hash_hmac("SHA256", $policy, $PRIVATE_KEY, true));

        if($productIWEB==false) {
            $output .= $endpoint . "projects/" . $newProjectID . '/editUrl?backURL=' . $urlCancel . '&addToCartURL=' . $urlCart . '&policy=' . $policy . '&signedPolicy=' . urlencode($signedPolicy) . '&redirect=1';
        }
        else{
            $output .= $endpoint . "projects/" . $newProjectID . '/editUrl?backURL=' . $urlCancel . '&addToCartURL=' . urlencode($urlCart).'&lng='.$locale.'&policy=' . $policy . '&signedPolicy=' . urlencode($signedPolicy) . '&redirect=1';
        }

        return $output;

    }

    public function readProject($publicKey, $privateKey,$projectID,$endpoint="http://services.imaxel.com/api/v3/"){

        $PUBLIC_KEY=$publicKey;
        $PRIVATE_KEY=$privateKey;

        $endpoint.='/projects/'.(int)$projectID.'';
        $datetime = new DateTime("".date('y-m-d H:i:s.u'));
        date_add($datetime, date_interval_create_from_date_string('10 minutes'));

        $policy ='{
	        "projectId": "'.(int)$projectID.'",
	        "publicKey": "'.$PUBLIC_KEY.'",
	        "expirationDate": "'.$datetime->format('c').'"
	    }';

        $policy = base64_encode($policy);
        $signedPolicy = base64_encode(hash_hmac("SHA256", $policy, $PRIVATE_KEY, true));

        $proyecto_datos = $this->doRequest($endpoint.'?policy='.$policy.'&signedPolicy='.urlencode($signedPolicy).'');

        if($proyecto_datos==""){
            $this->readProject($publicKey, $privateKey, $projectID);
        }else{
            return $proyecto_datos;
        }
    }

    public function editProject($publicKey, $privateKey, $projectID, $urlCart,$urlCartParameters, $urlCancel,$urlSave,$urlSaveParameters,$endpoint="http://services.imaxel.com/api/v3/"){

        $productIWEB=false;
        if($endpoint!="http://services.imaxel.com/api/v3/") {
            $productIWEB=true;
        }

        $PUBLIC_KEY=$publicKey;
        $PRIVATE_KEY=$privateKey;

        $datetime = new DateTime("".date('y-m-d H:i:s.u'));
        date_add($datetime, date_interval_create_from_date_string('10 minutes'));

        $urlCart.=urlencode($urlCartParameters);
        $urlSave.=urlencode($urlSaveParameters);

        if($productIWEB==false) {
            $policy = '{
                "projectId": "' . $projectID . '",
                "backURL": "' . $urlCancel . '",
                "addToCartURL": "' . $urlCart . '",
                "publicKey": "' . $PUBLIC_KEY . '",
                "expirationDate": "' . $datetime->format('c') . '"
	        }';
        }
        else{
            $locale = get_locale();
            $policy = '{
                "projectId": "' . $projectID . '",
                "lng":"'.$locale.'",
                "backURL": "' . $urlCancel . '",
                "addToCartURL": "' . $urlCart . '",
                "saveURL": "' . $urlSave . '",
                "publicKey": "' . $PUBLIC_KEY . '",
                "expirationDate": "' . $datetime->format('c') . '"
	        }';
        }

        $policy = base64_encode($policy);
        $signedPolicy = base64_encode(hash_hmac("SHA256", $policy, $PRIVATE_KEY, true));

        if($productIWEB==false) {
            $url = $endpoint.'/projects/'.(int)$projectID.'/editUrl?backURL='.$urlCancel.'&addToCartURL='.$urlCart.'&policy='.$policy.'&signedPolicy='.urlencode($signedPolicy).'&redirect=1';
        }
        else{
            $url = $endpoint.'/projects/'.(int)$projectID.'/editUrl?backURL='.$urlCancel.'&addToCartURL='.urlencode($urlCart).'&lng='.$locale.'&policy='.$policy.'&signedPolicy='.urlencode($signedPolicy).'&redirect=1';
        }

        return $url;
    }

    public function duplicateProject($publicKey, $privateKey, $projectID, $urlCart, $urlCartParameters,$urlCancel,$urlSave,$urlSaveParameters,$endpoint="http://services.imaxel.com/api/v3/"){

        $PUBLIC_KEY=$publicKey;
        $PRIVATE_KEY=$privateKey;

        $datetime = new DateTime("".date('y-m-d H:i:s.u'));
        date_add($datetime, date_interval_create_from_date_string('10 minutes'));

        $policy ='{
	        "projectId": "'.$projectID.'",
	        "publicKey": "'.$PUBLIC_KEY.'",
	        "expirationDate": "'.$datetime->format('c').'"
	    }';

        $policy = base64_encode($policy);
        $signedPolicy = base64_encode(hash_hmac("SHA256", $policy, $PRIVATE_KEY, true));

        $params = array(
            "projectId" => "".(int)$projectID."",
            "policy" => "".$policy."",
            "signedPolicy" => "".urlencode($signedPolicy).""
        );

        $newProject = $this->doPost($endpoint."/projects",$params);

        if($newProject){
            $newProject=json_decode($newProject);

            if($newProject->id){
                $urlCartParameters.="&attribute_proyecto=".$newProject->id;
                if(strlen($urlSaveParameters)>0)
                    $urlSaveParameters.="&attribute_proyecto=".$newProject->id;
                return array($newProject->id,$this->editProject($publicKey,$privateKey,$newProject->id,$urlCart,$urlCartParameters,$urlCancel,$urlSave,$urlSaveParameters,$endpoint));
            }
        }

        return "";
    }


    public function processOrder($publicKey, $privateKey, $order, $products,$customer,$addressDelivery, $endpoint="http://services.imaxel.com/api/v3/",$iweb=false){

        $PUBLIC_KEY=$publicKey;
        $PRIVATE_KEY=$privateKey;

        date_default_timezone_set('Europe/Madrid');
        $datetime = new DateTime("".date('y-m-d H:i:s.u'));
        date_add($datetime, date_interval_create_from_date_string('10 minutes'));

        $jobs = $products;

        $dataA ="";
        $aux=0;
        foreach($jobs as $job){
            $dataA .= "{\"project\":{\"id\": \"".$job["proyecto"]."\"},\"units\":".$job["qty"]."}";
            if((count($jobs)-1)==$aux){}else{$dataA .= ',';}
            $aux++;
        }

        $dataA .="";

        $dataB = "{";

        $dataB .="\"billing\":{
				\"email\":\"".$customer["email"]."\",
				\"firstName\":\"".addcslashes($customer["first_name"], '"\\/')."\",
				\"lastName\":\"".addcslashes($customer["last_name"], '"\\/')."\",
				\"phone\": \"".addcslashes($customer["phone"], '"\\/')."\"
			},
			\"saleNumber\":\"".$order->id."\",";


        $arrayPaymentsBankTransfer=array("bacs", "cheque");
        $arrayPaymentsCreditCard=array("paypal", "redsys","myredsys");
        if(in_array($order->payment_method,$arrayPaymentsBankTransfer)){
            $paymentTypeID=6;
        }
        else if(in_array($order->payment_method,$arrayPaymentsCreditCard)){
            $paymentTypeID=2;
        }
        else{
            $paymentTypeID=3;
        }
        $dataB.="\"payment\":{
				\"name\": \"".$order->payment_method_title ."\",
				\"instructions\":\"\",
				\"type\": \"".$paymentTypeID."\"
			},";

        $pickup_locations = array();
        if (class_exists('WC_Local_Pickup_Plus'))
        {
            foreach ( $order->get_shipping_methods() as $shipping_item ) {
                if ( isset( $shipping_item['pickup_location'] ) ) {
                    $location = maybe_unserialize( $shipping_item['pickup_location'] );
                    $pickup_locations[] = $location;
                }
            }
        }

        if(count($pickup_locations)>0) {
            $dataB .= "\"pickpoint\":{
                    \"name\":\"" . addcslashes($pickup_locations[0]["company"], '"\\/') . "\",
                    \"address\": \"" . addcslashes($pickup_locations[0]["address_1"], '"\\/') . addcslashes($pickup_locations[0]["address_2"], '"\\/') . "\",
                    \"city\":\"" . addcslashes($pickup_locations[0]["city"], '"\\/') . "\",
                    \"postalCode\":\"" . addcslashes($pickup_locations[0]["postcode"], '"\\/') . "\",                    
                    \"country\":\"" . addcslashes($pickup_locations[0]["country"], '"\\/') . "\",                    
                    \"firstName\":\"" . addcslashes($pickup_locations[0]["company"], '"\\/') . "\",
                    \"phone\":\"" . addcslashes($pickup_locations[0]["phone"], '"\\/') . "\",
                    \"instructions\":\"" . addcslashes($pickup_locations[0]["note"], '"\\/') . "\"
                },";
        }
        else {
            $dataB .= "\"recipient\":{
                \"address\": \"" . addcslashes($addressDelivery["address_1"], '"\\/') . addcslashes($addressDelivery["address_2"], '"\\/') . "\",
                \"city\":\"" . addcslashes($addressDelivery["city"], '"\\/') . "\",
                \"postalCode\":\"" . addcslashes($addressDelivery["postcode"], '"\\/') . "\",
                \"province\":\"" . addcslashes($addressDelivery["state"], '"\\/') . "\",
                \"country\":\"" . addcslashes($addressDelivery["country"], '"\\/') . "\",
                \"email\":\"" . $customer["email"] . "\",
                \"firstName\":\"" . addcslashes($addressDelivery["first_name"], '"\\/') . "\",
                \"lastName\":\"" . addcslashes($addressDelivery["last_name"], '"\\/') . "\",
                \"phone\":\"" . addcslashes($customer["phone"], '"\\/') . "\"
            },";

            $dataB .= "\"shippingMethod\": {
				\"amount\": " . $order->get_total_shipping() . ",
				\"name\":\"" . $order->get_shipping_method() . "\",
				\"instructions\":\"" . "" . "\"
			},";
        }

        $dataB .=
        "\"discount\": {
            \"amount\": 0,
            \"name\": \"\",
            \"code\": \"\"
        },
        \"total\": ".$order->get_total()."";

        $dataB .="
			}
		";

        $policy ='{
	        "jobs": ['.$dataA.'],
	        "checkout":'.$dataB.',
	        "publicKey":"'.$PUBLIC_KEY.'",
	        "expirationDate": "'.$datetime->format('c').'"
	    }';

        $policy = base64_encode($policy);
        $signedPolicy = base64_encode(hash_hmac("SHA256", $policy, $PRIVATE_KEY, true));

        $paramsb = '{
	        "jobs":['.$dataA.'],
	        "checkout":'.$dataB.',
	        "policy":"'.$policy.'",
	        "signedPolicy": "'.$signedPolicy.'"
	    }';

        $proyecto_datos = $this->doPostOrder($endpoint."/orders",$paramsb);

        return $proyecto_datos;
    }

    private function doRequest ($Url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        if (FALSE === $content)
            throw new Exception(curl_error($ch), curl_errno($ch));
        curl_close($ch);
        return $content;
    }

    private function doPost($url,$params)
    {
        $postData = '';
        foreach($params as $k => $v)
        {
            $postData .= $k . '='.$v.'&';
        }
        rtrim($postData, '&');
        $timeout=5;
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER, false);
        curl_setopt($ch,CURLOPT_AUTOREFERER, true);
        curl_setopt($ch,CURLOPT_POST, count($postData));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, $timeout);

        $output=curl_exec($ch);

        curl_close($ch);
        return $output;
    }

    function doPostOrder($url,$params)
    {
        $postData = $params;

        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $output=curl_exec($ch);

        curl_close($ch);
        return $output;

    }

}