<?php
/**
 * Created by PhpStorm.
 * User: cesartenesaca
 * Date: 12/5/15
 * Time: 9:37
 */

require_once 'unirest-php/src/Unirest.php';

class Imaxel {

    function requestAPI($command, $body){
        $headers = array("Accept" => "application/json");
        $response = Unirest\Request::get("http://ips1405.imaxel.com/WebCounterApi/api/v3.ashx?cmd=".$command, $headers, $body);
        $response_raw = str_replace("http://","", $response->raw_body);
        $sValidJson = preg_replace("/(\n[\t ]*)([^\t ]+):/", "$1\"$2\":", $response_raw);
        $response_parsed = json_decode($sValidJson);
        return $response_parsed->result;
    }

    function createSession(){
        $body = array(
            "dlrid" => "1"
        );
        $response = $this->requestAPI("CreateSession", $body);
        return $response;
    }

    function keepSessionAlive($session){
        $body = array(
            "tk" => $session
        );
        $response = $this->requestAPI("KeepSessionAlive", $body);
        return $response;
    }

    function expireSession($session){
        $body = array(
            "tk" => $session
        );
        $response = $this->requestAPI("ExpireSession", $body);
        return $response;
    }

    function createWorkSpace($session){
        $body = array(
            "tk" => $session
        );
        $response = $this->requestAPI("CreateWorkSpace", $body);
        return $response;
    }

    function createProject($session, $product_code){
        $body = array(
            "tk" => $session,
            "pc" => $product_code
        );
        $response = $this->requestAPI("CreateProject", $body);
        return $response;
    }

    function getProject($session, $project_id){
        $body = array(
            "tk" => $session,
            "prjId" => $project_id
        );
        $response = $this->requestAPI("GetProject", $body);
        return $response;
    }

    function getProductById($session, $product_id){
        $body = array(
            "tk" => $session,
            "prid" => $product_id
        );
        $response = $this->requestAPI("GetProduct", $body);
        return $response;
    }

    function getProductByCode($session, $product_code){
        $body = array(
            "tk" => $session,
            "pc" => $product_code
        );
        $response = $this->requestAPI("GetProduct", $body);
        return $response;
    }

    function getProductList($session){
        $body = array(
            "tk" => $session
        );
        $response = $this->requestAPI("GetProductList", $body);
        return $response;
    }

    function prepareProduction($body){
/*
        print_r(json_encode($body));

        $command = "PrepareProduction";
        $headers = array("Accept" => "application/json");
        $response = Unirest\Request::post("http://ips.1405.imaxel.com/WebCounterApi/api/v3.ashx?cmd=".$command."&dlrid=1", $headers, $body);
        print_r($response);
        $response_raw = str_replace("http://","", $response->raw_body);
        $sValidJson = preg_replace("/(\n[\t ]*)([^\t ]+):/", "$1\"$2\":", $response_raw);
        $response_parsed = json_decode($sValidJson);
        return $response_parsed->result;*/

        //echo json_encode($body);

        $ch = curl_init("http://ips1405.imaxel.com/WebCounterApi/api/v3.ashx?cmd=PrepareProduction&dlrid=1");
        curl_setopt_array($ch, array(
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode($body)
        ));

        $response = curl_exec($ch);

        // the message
        //$msg = json_encode($body).$response;

        //mail("cesar.tenesaca@gmail.com","Test Production",$msg);

        if($response === FALSE){
            die(curl_error($ch));
        }

        $sValidJson = preg_replace("/(\n[\t ]*)([^\t ]+):/", "$1\"$2\":", $response);
        //$responseData = json_decode($sValidJson);

        $responseData = json_decode($sValidJson);

        return $responseData->result;

    }

    function produce($dealer_order_number){
        $body = array(
            "dlrid" => "1",
            "dlrOrdNum" => $dealer_order_number
        );
        $response = $this->requestAPI("Produce", $body);
        return $response;
    }


}