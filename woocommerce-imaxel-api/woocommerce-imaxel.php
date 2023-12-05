<?php
/*
Plugin Name: WooCommerce - Integración Imaxel
Description: Integración de con Imaxel.
Version: 1.0
Author: César Tenesaca Landi
Author URI: http://www.twitter.com/cesartenesaca
*/
require('ValidarIdentificacion.php');
//require_once 'imaxel/vendor/autoload.php';
require('Imaxel.php');
add_shortcode( 'integracionimaxel', 'integracion_imaxel' );
add_shortcode( 'integracionimaxel2', 'integracion_imaxel2' );

/**
* Check for Posproceso Response
*
* @access public
* @return void
*/
/*
function integracion_imaxel() {

  //print_r($_POST);

  //$order_id = '0005';
  //$order_subtotal = number_format('120',2);

  //if(isset($_POST['TransactionId'])){

    $order_id = $_POST['TransactionId'];
    $order_subtotal = number_format($_POST['TransactionValue']/100,2);

    $exist = false;

    foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
      $_product = $values['data'];
      if($_product->id == "1342"){
        if($values['variation']['attribute_id-orden'] == $order_id){
          $exist = true;
        }
      }
    }

    if(!$exist){
      $arr['attribute_id-orden'] = $order_id;
      $arr['Subtotal Orden'] = $order_subtotal;
      WC()->cart->add_to_cart( "1342", "1","1454",$arr);
    }

  //}

}
*/
function integracion_imaxel2() {


    $imaxel = new Imaxel();
    $session = $imaxel->createSession();
    $workspace = $imaxel->createWorkSpace($session->SessionKey);
    //$productos = $imaxel->getProductList($session->SessionKey);

    return $session->SessionKey." ".$workspace->workspaceId;

    //print_r($productos);

    //return $imaxel->createSession();
}
/*
function login_imaxel(){
  if(isset($_POST['CustomerEmail'])) {
    //integracion_imaxel();
    programmatic_login();
  }
}
*/
/*
function check_imaxel(){
  if(isset($_POST['TransactionId'])) {
    integracion_imaxel();
    //programmatic_login('cesarte2005@yahoo.com');
  }
}
*/
add_action( 'woocommerce_before_calculate_totals', 'add_custom_price' );
//add_action('plugins_loaded', 'check_imaxel');
//add_action('wp_loaded', 'check_imaxel');
//add_action('init', 'login_imaxel');
add_action('template_redirect', 'init_register');
//add_action( 'woocommerce_checkout_before_customer_details', 'integracion_imaxel' );

function init_register(){
    $value = "no";
    if ( !is_user_logged_in() ) {
        if (is_front_page()){
            $value = "yes";
        }
    }

    //echo $value;

    setcookie("open_register", $value);
}
/*
function my_function_admin_bar($content) {
    return ( current_user_can( "administrator" ) ) ? $content : false;
}
add_filter( "show_admin_bar" , "my_function_admin_bar");
*/
function add_custom_price( $cart_object ) {
    //$custom_price = '20'; // This will be your custome price
    foreach ( $cart_object->cart_contents as $key => $value ) {
      $_product = $value['data'];
      if(isset($value['variation']['pa_subtotal'])){
        $custom_price = $value['variation']['pa_subtotal'];
        $value['data']->set_price($custom_price);
      }
    }
}

/**
 * Programmatically logs a user in
 *
 * @param string $email
 * @return bool True if the login was successful; false if it wasn't
 */
/*
function programmatic_login() {

  $email = $_POST['CustomerEmail'];

  if ( is_user_logged_in() ) {
    //wp_logout();
  }
  add_filter('authenticate', 'allow_programmatic_login', 10, 3);    // hook in earlier than other callbacks to short-circuit them
  $user = wp_signon(array('user_login' => $email), false);
  remove_filter('authenticate', 'allow_programmatic_login', 10, 3);

  if (is_a($user, 'WP_User')) {
    wp_set_current_user($user->ID, $user->user_login);

    if (is_user_logged_in()) {
      return true;
    }
  }


  return false;
}

*/

/**
 * An 'authenticate' filter callback that authenticates the user using only the username.
 *
 * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
 * and unhooked immediately after it fires.
 *
 * @param WP_User $user
 * @param string $email
 * @param string $password
 * @return bool|WP_User a WP_User object if the username matched an existing user, or false if it didn't
 */
function allow_programmatic_login( $user, $email, $password ) {
  return get_user_by( 'email', $email );
}

add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields', 1000 );
add_filter( 'woocommerce_default_address_fields' , 'custom_override_default_address_fields' );

function custom_override_checkout_fields( $fields ) {

  $fields['billing']['billing_email']['class'] = array('form-row-wide');
  $fields['billing']['billing_phone']['class'] = array('form-row-wide');
  $fields['billing']['billing_phone']['label'] = __('Teléfono Fijo', 'woocommerce');
  $fields['billing']['billing_phone']['required'] = true;
  unset($fields['billing']['billing_phone']['validate']);
  unset($fields['billing']['billing_phone']['type']);
  //unset($fields['billing']['billing_phone']);

  $new_field['billing_ccruc'] = array(
      'label'     => __('Cédula / RUC', 'woocommerce'),
      //'placeholder'   => _x('', 'placeholder', 'woocommerce'),
      'required'  => true,
      'class'     => array('form-row-wide'),
      'clear'     => true
  );

  $fields['billing'] = array_slice($fields['billing'], 0, 3, true) +
      $new_field +
      array_slice($fields['billing'], 3, count($fields['billing'])-3, true);


    $new_field_cellphone = array(
        'label'     => __('Teléfono Celular', 'woocommerce'),
        //'placeholder'   => _x('', 'placeholder', 'woocommerce'),
        'required'  => true,
        'class'     => array('form-row-wide'),
        'clear'     => true
    );

    $fields['billing']['billing_cellphone'] = $new_field_cellphone;

    return $fields;
}

add_action('woocommerce_checkout_process', 'custom_checkout_field_process');

function custom_checkout_field_process() {

  if ( isset( $_POST['billing_ccruc'] )){

    echo $_POST['billing_ccruc'];

    $validador = new ValidarIdentificacion();
    $ccruc = $_POST['billing_ccruc'];
    $correcto = false;

// validar CI
    if ($validador->validarCedula($ccruc)) {
      $correcto = true;
    }

// validar RUC persona natural
    if ($validador->validarRucPersonaNatural($ccruc)) {
      $correcto = true;
    }

// validar RUC sociedad privada
    if ($validador->validarRucSociedadPrivada($ccruc)) {
      $correcto = true;
    }

// validar RUC sociedad ublica
    if ($validador->validarRucSociedadPublica($ccruc)) {
      $correcto = true;
    }

    if(!$correcto){
      wc_add_notice( __( 'Número de Cédula / RUC no es correcto.' ), 'error' );
    }

  }

}

add_action( 'woocommerce_checkout_update_order_meta', 'custom_checkout_field_update_order_meta' );

function custom_checkout_field_update_order_meta( $order_id ) {
  if ( ! empty( $_POST['billing_ccruc'] ) ) {
    update_post_meta( $order_id, 'Cédula / RUC', sanitize_text_field( $_POST['billing_ccruc'] ) );
  }
    if ( ! empty( $_POST['billing_cellphone'] ) ) {
        update_post_meta( $order_id, 'Celular', sanitize_text_field( $_POST['billing_cellphone'] ) );
    }
    if ( ! empty( $_POST['billing_sector'] ) ) {
        update_post_meta( $order_id, 'Sector', sanitize_text_field( $_POST['billing_sector'] ) );
    }
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'custom_checkout_field_display_admin_order_meta', 10, 1 );

function custom_checkout_field_display_admin_order_meta($order){
    echo '<p><strong>'.__('Cédula / RUC').':</strong> ' . get_post_meta( $order->id, 'Cédula / RUC', true ) . '</p>';
    echo '<p><strong>'.__('Celular').':</strong> ' . get_post_meta( $order->id, 'Celular', true ) . '</p>';
    echo '<p><strong>'.__('Sector').':</strong> ' . get_post_meta( $order->id, 'Sector', true ) . '</p>';
}

add_filter('woocommerce_email_order_meta_keys', 'custom_checkout_field_order_meta_keys');

function custom_checkout_field_order_meta_keys( $keys ) {
  $keys['Cédula / RUC'] = '_billing_ccruc';
    $keys['Celular'] = '_billing_cellphone';
    $keys['Sector'] = '_billing_sector';
return $keys;
}

function custom_override_default_address_fields( $address_fields ) {
  unset($address_fields['postcode']);
  //unset($address_fields['country']);

    $new_field_cellphone = array(
        'label'     => __('Sector', 'woocommerce'),
        //'placeholder'   => _x('', 'placeholder', 'woocommerce'),
        'required'  => true,
        'class'     => array('form-row-wide'),
        'clear'     => true
    );

    $address_fields['sector'] = $new_field_cellphone;

  $address_fields['city']['label'] = 'Ciudad';
  $address_fields['state']['label'] = 'Provincia';
  $address_fields['state']['type'] = 'select';
  $address_fields['state']['options'] = array(
      //'' => 'Selecione una Provincia',
      'Azuay' => 'Azuay',
      'Bolivar' => 'Bolivar',
      'Cañar' => 'Cañar',
      'Carchi' => 'Carchi',
      'Chimborazo' => 'Chimborazo',
      'Cotopaxi' => 'Cotopaxi',
      'El Oro' => 'El Oro',
      'Esmeraldas' => 'Esmeraldas',
      'Galápgos' => 'Galápagos',
      'Guayas' => 'Guayas',
      'Imbabura' => 'Imbabura',
      'Loja' => 'Loja',
      'Los Ríos' => 'Los Ríos',
      'Manabí' => 'Manabí',
      'Morona Santiago' => 'Morona Santiago',
      'Napo' => 'Napo',
      'Orellana' => 'Orellana',
      'Pastaza' => 'Pastaza',
      'Pichincha' => 'Pichincha',
      'Santa Elena' => 'Santa Elena',
      'Santo Domingo de los Tsáchilas' => 'Santo Domingo de los Tsáchilas',
      'Sucumbíos' => 'Sucumbíos',
      'Tungurahua' => 'Tungurahua',
      'Zamora Chinchipe' => 'Zamora Chinchipe'
  );

  return $address_fields;
}

add_action( 'woocommerce_order_status_processing', 'order_status_processing');
add_action( 'woocommerce_order_status_on-hold', 'order_status_on_hold');

//add_shortcode( 'orderstatus', 'order_status_processing' );

function order_status_on_hold($order_id) {
    $dealer_order_number = get_post_meta($order_id, "dealer_order_number", true);
    if($dealer_order_number == ""){
        prepare_production($order_id);
    }
}

function order_status_processing($order_id) {
    $dealer_order_number = get_post_meta($order_id, "dealer_order_number", true);
    if($dealer_order_number == ""){
        prepare_production($order_id);
    }
    produce($order_id);
}

function prepare_production($order_id){
    $imaxel = new Imaxel();
    $order = new WC_Order($order_id);
    $items = $order->get_items();

    $body = array(
        "__type" => "Imaxel.WebCounter.Models.ProjectModel.Production",
        "ItemsProductions" => array(),
        "RecalculePrice" => "false"
    );

    foreach ( $items as $key => $item ) {
        $product = new WC_Product($item["product_id"]);
        $is_photo = get_post_meta($product->id, "iweb_is_photo", true);
        if($is_photo == "yes") {

            $_projects = WC()->session->get("iweb-projects");

            foreach($_projects as $_project_item){
                if($_project_item["project_id"] == $item["pa_project_id"]){
                    $_project = $_project_item;
                    break;
                }
            }

            $session = $imaxel->keepSessionAlive($_project["session_key"]);

            $proyect = $imaxel->getProject($session->SessionKey, $item["project_id"]);


            $itemsProduction[] = array(
                "__type" => "Imaxel.WebCounter.Models.ProjectModel.ItemProduction",
                "ProjectId" => $item["pa_project_id"],
                "Price" => $item["pa_subtotal"],
                "Quantity" => $item["qty"]
            );

            remove_project($item["pa_project_id"]);

        }
    }

    $body["ItemsProductions"] = $itemsProduction;

    //$body["Production"][] = $body_direct;

    $response = $imaxel->prepareProduction($body);
    update_post_meta($order->id, "dealer_order_number", $response->dealerOrderNumber);
}

function produce($order_id){
    $imaxel = new Imaxel();
    $dealer_order_number = get_post_meta($order_id, "dealer_order_number", true);
    $imaxel->produce($dealer_order_number);
}

add_action( 'wp_enqueue_scripts', 'imaxel_enqueue_js' );
function imaxel_enqueue_js() {
    wp_enqueue_script( 'messi-js', plugins_url( '/js/messi/messi.min.js' , __FILE__ ), array( 'jquery' ), '', true );
    wp_enqueue_script( 'easyxdm-js', plugins_url( '/js/easyxdm/easyXDM.min.js' , __FILE__ ), array( 'jquery' ), '', true );
    wp_enqueue_script( 'imaxel-js', plugins_url( '/js/imaxel.js' , __FILE__ ), array( 'jquery' ), '', true );
    wp_enqueue_style( 'messi', plugins_url( '/js/messi/messi.min.css' , __FILE__ ));
}

add_action( 'wp_ajax_create_product', 'create_product_callback' );
add_action( 'wp_ajax_nopriv_create_product', 'create_product_callback' );

add_action( 'wp_ajax_add_photo_product_to_cart', 'add_photo_product_to_cart_callback' );
add_action( 'wp_ajax_nopriv_add_photo_product_to_cart', 'add_photo_product_to_cart_callback' );

add_action( 'wp_ajax_open_project', 'open_project_callback' );
add_action( 'wp_ajax_nopriv_open_project', 'open_project_callback' );

add_action( 'wp_ajax_delete_project', 'delete_project_callback' );
add_action( 'wp_ajax_nopriv_delete_project', 'delete_project_callback' );


function create_product_callback() {

    //$product_sku = $_POST["productsku"];

    //WC()->cart->set_session();
/*
    if(!isset($_SESSION)) {
        session_start();
    }
*/
    $variation_id = $_POST["variationid"];

    $variation = new WC_Product_Variation($variation_id);

    $product_sku = $variation->get_sku();

    $imaxel = new Imaxel();
    $session = $imaxel->createSession();
    $workspace = $imaxel->createWorkSpace($session->SessionKey);
    $project = $imaxel->createProject($session->SessionKey, $product_sku);
    $product = $imaxel->getProductByCode($session->SessionKey, $product_sku);

    $_projects = WC()->session->get("iweb-projects");
    //$_projects = $_SESSION["iweb-projects"];

    if(!$_projects){
        $_projects = array();
    }

    $_project = array(
        "session_key" => $session->SessionKey,
        "project_id" => $project->ProjectId,
        "product_code" => $product->productCode,
        "product_name" => $product->name,
        "product_variation" => $variation_id
    );

    /*$arr['pa_project_id'] = 0;
    $arr['pa_subtotal'] = 0;

    $cart_item_key = WC()->cart->add_to_cart($variation->id, "1", $variation->variation_id , $arr, $arr);

    WC()->cart->remove_cart_item($cart_item_key);

    WC()->cart->set_session();*/

    $_projects[] = $_project;

    WC()->session->set("iweb-projects", $_projects);
    //$_SESSION["iweb-projects"] = $_projects;

    $data = array(
        "sessionid" => $session->SessionKey,
        "projectid" => $project->ProjectId
    );

    echo json_encode( $data );

    wp_die();

}

function open_project_callback(){
/*
    if(!isset($_SESSION)) {
        session_start();
    }
*/
    $imaxel = new Imaxel();

    $project_id = $_POST["projectid"];

    $_projects = WC()->session->get("iweb-projects");
    //$_projects = $_SESSION["iweb-projects"];

    //print_r(WC()->session->get_session_data());

    foreach($_projects as $_project_item){
        if($_project_item["project_id"] == $project_id){
            $_project = $_project_item;
            break;
        }
    }

    $session = $imaxel->keepSessionAlive($_project["session_key"]);

    if($session->SessionKey != $_project["session_key"]){
        $_project["session_key"] = $session->SessionKey;
        update_project($_project);
    }

    $data = array(
        "sessionid" => $_project["session_key"],
        "projectid" => $_project["project_id"]
    );

    echo json_encode( $data );

    wp_die();
}

function delete_project_callback(){
/*
    if(!isset($_SESSION)) {
        session_start();
    }
*/
    $imaxel = new Imaxel();

    $project_id = $_POST["projectid"];

    $_projects = WC()->session->get("iweb-projects");
    //$_projects = $_SESSION["iweb-projects"];

    //print_r(WC()->session->get_session_data());

    foreach($_projects as $_project_item){
        if($_project_item["project_id"] == $project_id){
            $_project = $_project_item;
            break;
        }
    }

    remove_project($project_id);

    //$session = $imaxel->keepSessionAlive($_project["session_key"]);

    $session_result = $imaxel->expireSession($_project["session_key"]);

    $data = array(
        "sessionresult" => $session_result,
    );

    echo json_encode( $data );

    wp_die();

}

function add_photo_product_to_cart_callback() {

    //WC()->cart->set_session();
    /*if(!isset($_SESSION)) {
        session_start();
    }*/

    $imaxel = new Imaxel();

    $project_id = $_POST["projectid"];

    $_project = get_project($project_id);

    $projects = $imaxel->getProject($_project["session_key"], $project_id);

    foreach($projects as $project){
        //$_product = new WC_Product_Variable($_project["product_variation"]);
        $_product = wc_get_product($_project["product_variation"]);

        $arr['pa_project_id'] = $project->ProjectId;
        $arr['pa_subtotal'] = $project->Price;

        WC()->cart->add_to_cart($_product->id, "1", $_product->variation_id , $arr, $arr);

        remove_project($project_id);

    }

    $data = array(
        "projectid" => $project->ProjectId
    );

    echo json_encode( $data );

    wp_die();

}

function get_project($project_id){

    $_projects = WC()->session->get("iweb-projects");
    //$_projects = $_SESSION["iweb-projects"];


    foreach($_projects as $id => $_project_item){
        if($_project_item["project_id"] == $project_id){
            return $_project_item;
        }
    }

    return false;
}

function update_project($new_project){

    $_projects = WC()->session->get("iweb-projects");
    //$_projects = $_SESSION["iweb-projects"];

    foreach($_projects as $id => $_project_item){
        if($_project_item["project_id"] == $new_project["project_id"]){
            $_projects[$id] = $new_project;
            break;
        }
    }

    WC()->session->set("iweb-projects", $_projects);
    //$_SESSION["iweb-projects"] = $_projects;
}

function remove_project($project_id){

    if(isset(WC()->session)) {
        //if(isset($_SESSION)) {


        $_projects = WC()->session->get("iweb-projects");
        //$_projects = $_SESSION["iweb-projects"];

        foreach ($_projects as $id => $_project_item) {
            if ($_project_item["project_id"] == $project_id) {
                unset($_projects[$id]);
                break;
            }
        }

        WC()->session->set("iweb-projects", $_projects);
        //$_SESSION["iweb-projects"] = $_projects;
    }
}

function get_product_by_sku( $sku ) {

    global $wpdb;

    $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

    echo "Product Id"+$product_id;

    //if ( $product_id ) return $product_id ;

    return $product_id;

}

class wp_iweb_plugin extends WP_Widget {

    // constructor
    function wp_iweb_plugin() {
        parent::WP_Widget(false, $name = 'iWeb Widget');
        /*if(!isset($_SESSION)) {
            session_start();
        }*/
    }

    // widget form creation
    function form($instance) {
        // Check values
        if( $instance) {
            $title = esc_attr($instance['title']);
        } else {
            $title = '';
        }
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>

        <?php
    }

    // widget update
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        // Fields
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    // widget display
    function widget($args, $instance) {

        extract( $args );
        // these are the widget options
        $title = apply_filters('widget_title', $instance['title']);

        //echo $before_widget;
        // Display the widget
        echo '<div id="projects_widget" class="widget wp_widget_plugin_box">';

        $_projects = WC()->session->get("iweb-projects");

        //$_projects = $_SESSION["iweb-projects"];

        if($_projects){
            // Check if title is set
            if ( $title ) {
                echo $before_title . $title . $after_title;
                //echo $title;
            }
            foreach($_projects as $_project){
                echo '<p class="wp_widget_plugin_text">
                        <a href="#" class="iweb-project" id="'.$_project["project_id"].'">'.$_project["product_name"].'</a><br />
                        <a href="#" class="delete-iweb-project" id="'.$_project["project_id"].'">Eliminar</a>
                        </p>';
            }
        }

        echo '</div>';
        //echo $after_widget;
    }
}

// register widget
add_action('widgets_init', create_function('', 'return register_widget("wp_iweb_plugin");'));


/*
add_filter( 'product_type_selector', 'add_photo_product' );
function add_photo_product( $types ){
    $types[ 'photo' ] = __( 'Producto de foto acabado' );
    return $types;
}


add_action( 'woocommerce_loaded', 'create_photo_product' );

function create_photo_product()
{

    class WC_Product_Photo extends WC_Product_Variable
    {*/
        /**
         * __construct function.
         *
         * @access public
         * @param mixed $product
         */
        /*public function __construct($product)
        {
            $this->product_type = 'photo';
            parent::__construct($product);
        }
    }

}
*/
/*
define( 'YOUR_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/templates/' );

add_action('woocommerce_variable_add_to_cart', 'add_to_cart');

function add_to_cart() {
    wc_get_template( 'single-product/add-to-cart/photo.php',$args = array(), $template_path = '', YOUR_TEMPLATE_PATH);
}
*/
/*
add_action('woocommerce_product_options_general_product_data','show_photo');

function show_photo(){
    echo "<script>jQuery('.show_if_variable').addClass('show_if_photo');</script>";
}
*/

function iweb_plugin_path() {
    // gets the absolute path to this plugin directory
    return untrailingslashit( plugin_dir_path( __FILE__ ) );
}

add_filter( 'woocommerce_locate_template', 'iweb_woocommerce_locate_template', 10, 3 );

function iweb_woocommerce_locate_template( $template, $template_name, $template_path ) {

    global $woocommerce;

    $_template = $template;

    $plugin_path = iweb_plugin_path() . '/woocommerce/templates/';

    if ( ! $template_path ) {
        $template_path = $woocommerce->template_url;
    }

    // Modification: Get the template from this plugin, if it exists
    if (file_exists( $plugin_path . $template_name ) ) {
        $template = $plugin_path . $template_name;
    }

    // Look within passed path within the theme - second priority

    if(! $template) {
        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );
    }

    // Use default template
    if ( ! $template )
        $template = $_template;

    // Return what we found
    return $template;

}

//Tipo foto acabado

// add the settings under ‘General’ sub-menu
add_action( 'woocommerce_product_options_general_product_data', 'iweb_add_custom_settings' );
function iweb_add_custom_settings() {
    global $woocommerce, $post;
    echo '<div class="options_group">';

    // Create a checkbox for product purchase status
    woocommerce_wp_checkbox(
        array(
            'id' => 'iweb_is_photo',
            'label' => __('Es producto foto acabado', 'woocommerce' )
        ));

    echo '</div>';

}

add_action( 'woocommerce_process_product_meta', 'iweb_save_custom_settings' );
function iweb_save_custom_settings( $post_id ){
    // save purchasable option
    $is_photo = isset( $_POST['iweb_is_photo'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, 'iweb_is_photo', $is_photo );
}

?>
