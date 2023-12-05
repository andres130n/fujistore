<?php
/**
* Plugin Name: Imaxel WooCommerce
* Plugin URI: http://www.imaxel.com
* Description: A wordpress plugin to integrate imaxel with woocommerce and wordpress.
* Version: 1.4.2
* Text Domain: imaxel
* Domain Path: /language/
* Author: Imaxel
* Author URI: http://www.imaxel.com
* License: All right reserved 2016
*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! class_exists( 'WC_Imaxel' ) ) {

		class WC_Imaxel
		{

            var $imaxel_db_version="1.4.0.0";

			public function __construct()
			{
				register_activation_hook(__FILE__, array($this, 'imaxel_createdb'));

				add_action('init', array($this, 'myplugin_load_textdomain'));
				add_action('init', array($this, 'imaxel_init'));

				// called only after woocommerce has finished loading
				add_action('woocommerce_init', array(&$this, 'woocommerce_loaded'));

				// called after all plugins have loaded
				add_action('plugins_loaded', array(&$this, 'plugins_loaded'));

				/*Pantalla proyectos*/
				add_action('admin_menu', array($this, 'register_imaxel_submenu_proyects'), 99);

				/*Tab de configuracion de woocommerce*/
				add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
				add_action('woocommerce_settings_tabs_settings_tab_imaxel', __CLASS__ . '::settings_tab');
				add_action('woocommerce_update_options_settings_tab_imaxel', __CLASS__ . '::update_settings');

				/*Hooks de configuracion de producto woocommerce*/
				add_filter('woocommerce_product_data_tabs', array($this, 'imaxel_product_data_tab'));
				add_action('woocommerce_product_data_panels', array($this, 'imaxel_product_data_fields'));
				add_action('woocommerce_process_product_meta', array($this, 'imaxel_product_data_fields_save'));
				add_action('save_post', array($this, 'imaxel_product_save'));

				/*Hooks de ficha de producto*/
				//add_filter('woocommerce_is_purchasable', array($this,'imaxel_product_is_purchasable'), 10, 2);
				add_action('wp', array($this, 'imaxel_custom_hide_buttons'));

				/*#4883
				 * $hookImxButton=get_option("wc_settings_tab_imaxel_button_hook");
				if(!$hookImxButton){
					$hookImxButton="woocommerce_product_meta_end";
				}
				add_action($hookImxButton, array($this, 'imaxel_custom_buy__button'));*/
				add_action("woocommerce_product_meta_start", array($this, 'imaxel_custom_buy__button'));


				/*MICUENTA*/
				add_action('woocommerce_before_my_account', array($this, 'imaxel_my_projects_imaxel'));

				/*AJAX*/
				if (is_admin()) {
					add_action('wp_ajax_imaxel_update_products', array($this, 'imaxel_update_products'));
				}

				add_action('wp_ajax_imaxel_wrapper', array($this, 'imaxel_wrapper'));
				add_action('wp_ajax_nopriv_imaxel_wrapper', array($this, 'imaxel_wrapper'));
				add_action('wp_ajax_imaxel_edit_project', array($this, 'imaxel_edit_project'));
				add_action('wp_ajax_imaxel_delete_project', array($this, 'imaxel_delete_project'));
				add_action('wp_ajax_imaxel_duplicate_project', array($this, 'imaxel_duplicate_project'));

				add_action('wp_ajax_imaxel_admin_edit_project', array($this, 'imaxel_admin_edit_project'));
				add_action('wp_ajax_imaxel_admin_delete_project', array($this, 'imaxel_admin_delete_project'));
				add_action('wp_ajax_imaxel_admin_duplicate_project', array($this, 'imaxel_admin_duplicate_project'));

				/*Cart*/
				add_action('woocommerce_add_to_cart', array($this, 'imaxel_add_to_cart'), 10, 2);
				add_action('woocommerce_before_calculate_totals', array($this, 'imaxel_custom_cart_price'), 10);

				/*ADMIN*/
				add_action('woocommerce_order_item_add_action_buttons', array($this, 'action_imaxel_woocommerce_order_item_add_action_buttons'), 10, 1);
				add_action('save_post', array($this, 'action_imaxel_order_reprocess'), 10, 3);

				/*ORDER PROCESSING*/
				add_action('woocommerce_order_status_processing', array($this, 'imaxel_woocommerce_order_status_processing'));

				/*Redirecciones*/
				add_action('template_redirect', array($this, 'imaxel_redirection_function'), 1, 2);
				add_action( 'after_setup_theme', array($this,'imaxel_after_setup_theme'));

				/*HOOK LOGIN/REGISTER USER*/
				add_action('wp_login', array($this,'imaxel_login_user'),10,3);
				add_action('user_register', array($this,'imaxel_register_user'));


				include_once('includes/imaxel_operations.php');
				include_once('includes/imx-admin-notices.php');

				// indicates we are running the admin
				if (is_admin()) {
					// ...
				}

				// indicates we are being served over ssl
				if (is_ssl()) {
					// ...
				}

				// take care of anything else that needs to be done immediately upon plugin instantiation, here in the constructor
			}

			#region create tables db y idiomas
			//Funcion para crear tablas en la activacion del plugin
			public function imaxel_createdb()
			{
				global $wpdb;

				$table_name = $wpdb->prefix . 'imaxel_woo_products';
				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
					`id` int(10) NOT NULL AUTO_INCREMENT,
					  `code` varchar(255) CHARACTER SET utf8 NOT NULL,
					  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
					  `type` tinyint(4) NOT NULL,
					  `price` float NOT NULL,
					  PRIMARY KEY (`id`)

				) $charset_collate;";

				//create table
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);

				$table_name = $wpdb->prefix . 'imaxel_woo_projects';
				$sql = "CREATE TABLE $table_name (
					  `id_customer` int(10) unsigned NOT NULL,
					  `id_project` int(10) NOT NULL,
					  `type_project` tinyint(4) NOT NULL,
					  `id_product` int(10) NOT NULL,
					  `id_product_attribute` int(10) NOT NULL,
					  `price_project` float NOT NULL,
					  KEY `id_customer` (`id_customer`),
					  PRIMARY KEY (`id_customer`,`id_project`)
				) $charset_collate;";

				dbDelta($sql);

            }

			public function myplugin_load_textdomain()
			{
				$plugin_dir = basename(dirname(__FILE__)) . '/language/';
				load_plugin_textdomain('imaxel', false, $plugin_dir);
			}

			#4864
			public function imaxel_init(){
				remove_all_filters("woocommerce_registration_redirect");
				//Funciones de redireccion con la prioridad mas alta
				add_filter('woocommerce_login_redirect', array($this, 'imaxel_wc_custom_user_redirect'), PHP_INT_MAX, 2);
				add_filter('woocommerce_registration_redirect', array($this, 'imaxel_wc_custom_user_redirect'), PHP_INT_MAX, 2);
			}

			#endregion

			#region Funciones administracion edicion de pedido
			public function action_imaxel_woocommerce_order_item_add_action_buttons($order)
			{
				$items = $order->get_items();
				$showImaxelButton = false;
				foreach ($items as $item) {
					if ($item["proyecto"]) {
						$showImaxelButton = true;
						break;
					}
				}
				if ($showImaxelButton == true) {
					echo '<button type="button" onclick="document.getElementById(' ."'imaxel_reprocess_order'" .').value=1;document.post.submit();" class="button generate-items">' . __('Imaxel reprocess order', 'imaxel') . '</button>';
					echo '<input type="hidden" value="0" name="imaxel_reprocess_order" id="imaxel_reprocess_order" />';
				}
			}

			public function action_imaxel_order_reprocess($post_id, $post, $update)
			{
				global $wpdb;
				$slug = 'shop_order';
				if (is_admin()) {
					if ($slug != $post->post_type) {
						return;
					}
					if (isset($_POST['imaxel_reprocess_order']) && $_POST['imaxel_reprocess_order']==1) {
						$imaxelOperations = new ImaxelOperations();
						$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
						$publicKey = get_option("wc_settings_tab_imaxel_publickey");
						$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");
						$order = new WC_Order($post->ID);
						$items = $order->get_items();
						$itemsIweb=array();
						$itemsHtml=array();
						foreach($items as $item){
							if ( function_exists( 'WC' ) && ( version_compare( WC()->version, "3.0.0" )>=0)) {
								$projectID=$item["item_meta"]["proyecto"];
							}
							else{
								$projectID=$item["item_meta"]["proyecto"][0];
							}
							if($projectID) {
								$sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                						WHERE id_project =' . $projectID;
								$row = $wpdb->get_row($sql);
								if ($row) {
									if ($row->type_project == 0) {
										$itemsHtml[] = $item;
									} elseif ($row->type_project == 1) {
										$itemsIweb[] = $item;
									}
								}
							}
						}

						$customer = $order->get_address("billing");
						$shipping = $order->get_address("shipping");

                        if(sizeof($itemsHtml)>0){
							$responseHTML = $imaxelOperations->processOrder($publicKey, $privateKey, $order, $itemsHtml, $customer, $shipping);
						}
						if(sizeof($itemsIweb)>0){
							$responseIWEB = $imaxelOperations->processOrder($publicKey, $privateKey, $order, $itemsIweb, $customer, $shipping,$iwebApiUrl,true);
						}
						if ($responseIWEB || $responseHTML) {
							IMX_Admin_Notices::add_success("Imaxel: order reprocessed." . $responseHTML ." " .$responseIWEB);
						} else {
							IMX_Admin_Notices::add_error("Imaxel error, can't reprocess order");
						}
					}
				}
			}
			#endregion

			#region Funciones administracion proyectos
			public function register_imaxel_submenu_proyects()
			{
				add_submenu_page('woocommerce', 'Imaxel Projects', 'Imaxel Projects', 'manage_options', 'imaxel-projects-submenu-page', array($this, 'imaxel_projects_submenu_page_callback'));
			}

			public function imaxel_projects_submenu_page_callback()
			{
				global $wpdb;
				wp_enqueue_style('style', plugins_url('/assets/css/style.css', __FILE__));
				wp_enqueue_script(
						'imaxel_script',
						plugins_url('/assets/js/imaxel_admin.js', __FILE__),
						array('jquery'),
						TRUE
				);
				wp_localize_script('imaxel_script', 'ajax_object',
						array(
								'url' => admin_url('admin-ajax.php'),
								'backurl' => admin_url('admin.php?page=imaxel-projects-submenu-page'),
								'returnurl' => admin_url('admin.php?page=imaxel-projects-submenu-page')
						)
				);

				echo '<h3>' . __("Imaxel projects", "imaxel") . '</h3>';

				//Search by ID
				$ID_search = isset($_GET['numberid_f']) ? abs((int)$_GET['numberid_f']) : '';
				if ($ID_search != "" && $ID_search > 0) {
					$filter_query = " AND id_project='" . $ID_search . "'";
				} else {
					$filter_query = '';
					$ID_search = "";
				}

				//Search by user
				$ID_user = isset($_GET['imaxel_customer_id']) ? abs((int)$_GET['imaxel_customer_id']) : '';
				if ($ID_user != "") {
					$filter_user_query = " AND id_customer='" . $ID_user . "'";
				} else {
					$filter_user_query = '';
				}

				$query = "SELECT * FROM " . $wpdb->prefix . "imaxel_woo_projects WHERE 1=1  " . $filter_query . " " . $filter_user_query . "";

				$project_array = $wpdb->get_results($query . " ORDER BY id_project DESC LIMIT 100");

				$users = get_users();
				if (empty($users))
					return;

				//Print the filter form
				echo '<form id="posts-filter" class="search-box-imaxel" method="get">
						<p>
						<input type="hidden" name="page" value="imaxel-projects-submenu-page"/>
						<span>
						<input type="search" style="display: inline-block;vertical-align: middle;"  id="numberid_f" name="numberid_f" value="' . $ID_search . '" placeholder="' . __('Project ID', 'imaxel') . '">
						</span>
						';

				echo '<span><select name="imaxel_customer_id" style="display: inline-block;vertical-align: middle;">';
				echo '<option value="">' . __('Select customer', 'imaxel') . '</option>';
				foreach ($users as $user) {
					echo '<option ';
					if ($ID_user == $user->ID) {
						echo ' selected="selected" ';
					}
					echo ' value="' . $user->ID . '">' . $user->data->display_name . '</option>';
				}
				echo '</select></span>';

				echo '<span>
					<input type="submit" id="search-submit" class="button" value="' . __('Filter', 'imaxel') . '">
					</span>
					</p>
					</form>';

				//here we go with the table head
				echo '<table class="wp-list-table widefat fixed striped pages">
				<thead>
				<tr>
					<th style="width: 110px;">' . __('Project', 'imaxel') . '</th>
					<th style="width: 80px;">' . __('Woo Order', 'imaxel') . '</th>
					<th>' . __('User name', 'imaxel') . '</th>
					<th>' . __('Products', 'imaxel') . '</th>
					<th style="width: 80px;">' . __('Price', 'imaxel') . '</th>
					<th>' . __('Woo Status', 'imaxel') . '</th>
					<th style="width: 110px;display:none">' . __('Status', 'imaxel') . '</th>
					<th>' . __('Action', 'imaxel') . '</th>
				</tr>
				</thead>';


				$pathImg = plugins_url('/assets/img/', __FILE__);

				foreach ($project_array as $project) {
					//Cargamos pedido
					$query="SELECT * FROM " . $wpdb->prefix . "woocommerce_order_itemmeta
					INNER JOIN " . $wpdb->prefix ."woocommerce_order_items ON  " . $wpdb->prefix .  "woocommerce_order_items.order_item_id=". $wpdb->prefix ."woocommerce_order_itemmeta.order_item_id
					WHERE meta_key='proyecto' and meta_value='" . $project->id_project."'";
					$row = $wpdb->get_row($query);
					unset($order);
					unset($user);
					if($row){
						$order = new WC_Order( $row->order_id );
					}

					echo '<tr id="project-' . $project->id_project . '">
						<td>' . $project->id_project . '</td>
						<td>' . (isset($order) ? "<a href='".admin_url( 'post.php?post=' . absint( method_exists($order,"get_id") ? $order->get_id() : $order->id ) . "&action=edit'") . "'>" . ( method_exists($order,"get_id") ? $order->get_id() : $order->id ) ."</a>" : "" ) . '</td>
						';
					echo '<td>';


					//The customer - link to profile
					$user = get_userdata($project->id_customer);
					if ($user) {
						echo '<a href="' . get_edit_user_link($user->ID) . '">' . esc_attr($user->user_nicename) . '</a>';
					}
					echo '</td>';

					//Product name
					echo '<td>';
					$product = wc_get_product($project->id_product);
					if ($product)
						echo '<a href="' . get_permalink($product->get_id()) . '">' . esc_attr($product->get_title()) . '</a>';
					echo '</td>';
					echo '<td>';

					//Price
					echo $project->price_project;

					//Status in Woo
					echo '</td>
						<td>'. (isset($order) ? $order->get_status() :"" ) .'</td>';
					echo '<td style="display:none">';

					//Status in Imaxel
					'</td>';
					echo '<td>';
					echo '<div>
						'.(!isset($order) ? '<a id="delete" style="" class="imaxel-btn-delete" title="" href=""><img  src="' . $pathImg . 'delete.png"/></a>' :"")
						.'<a id="edit" style="" class="imaxel-btn-edit" title="" href=""><img  src="' . $pathImg . 'edit.png"/></a>'
						.($user!=false ? '<a id="duplicate" title="" class="imaxel-btn-duplicate" href=""><img  src="' . $pathImg . 'duplicate.png"/></a>' : "")
						.'<a id="buy" title="" href="" style="display:none"><img  src="' . $pathImg . 'buy.png"/></a>
					</div>';

					echo '</td>
				</tr>';
				}
				echo '</table>';
			}

			public function imaxel_admin_edit_project()
			{
				global $wpdb;
				global $woocommerce;
				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

				$projectID = intval($_POST["projectID"]);

				if($projectID>0) {
                    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                			WHERE id_project=' . $projectID;
                    $row = $wpdb->get_row($sql);

                    $product = wc_get_product($row->id_product);
                    $variations = $product->get_children();

                    $urlCancel = esc_url($_POST["backURL"]);
                    $urlCart = $woocommerce->cart->get_cart_url();
                    $cartURLParameters = "";

                    $urlSave = "";
                    $urlSaveParameters = "";

                    if (isset($_POST["returnURL"]))
                        $urlCart = esc_url($_POST["returnURL"]);

                    $imaxelOperations = new ImaxelOperations();
                    if ($row->type_project == 0) {
                        $projectUrl = $imaxelOperations->editProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel,$urlSave,$urlSaveParameters);
                    } else {
                        $projectUrl = $imaxelOperations->editProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel,$urlSave,$urlSaveParameters, $iwebApiUrl);
                    }
                    echo $projectUrl;
                    die();
                }
			}

			public function imaxel_admin_duplicate_project()
			{
				global $wpdb;
				global $woocommerce;

				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

				$projectID = intval($_POST["projectID"]);
				if($projectID>0) {

                    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                			WHERE id_project=' . $projectID;
                    $row = $wpdb->get_row($sql);

                    $product = wc_get_product($row->id_product);
                    $variations = $product->get_children();

                    $urlCancel = esc_url($_POST["backURL"]);

                    $urlCart = get_home_url();
                    $cartURLParameters = "";

                    $urlSave="";
                    $urlSaveParameters="";

                    if (isset($_POST["returnURL"]))
                        $urlCart = esc_url($_POST["returnURL"]);

                    $imaxelOperations = new ImaxelOperations();
                    if ($row->type_project == 0) {
                        $projectInfo = $imaxelOperations->duplicateProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel,$urlSave,$urlSaveParameters);
                    } else {
                        $projectInfo = $imaxelOperations->duplicateProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel,$urlSave,$urlSaveParameters, $iwebApiUrl);
                    }

                    if ($projectInfo) {
                        $sql = "INSERT INTO " . $wpdb->prefix . "imaxel_woo_projects (id_customer, id_project, type_project,id_product, id_product_attribute, price_project)
							VALUES (
							" . $row->id_customer . "," . $projectInfo[0] . "," . $row->type_project . "," . $row->id_product . "," . $row->id_product_attribute . "," . $row->price_project .
                            ")";
                        $wpdb->query($sql);
                    }

                    echo $projectInfo[1];
                    die();
                }
			}

			public function imaxel_admin_delete_project()
			{
				global $wpdb;
				$projectID = intval($_POST["projectID"]);
				if ($projectID>0) {
					$sql = 'DELETE FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                			WHERE id_project=' . $projectID;
					$wpdb->query($sql);
				}
				die();
			}
			#endregion

			#region Funciones de configuracion generica de woocommerce
			public static function add_settings_tab($settings_tabs)
			{
				$settings_tabs['settings_tab_imaxel'] = __('Imaxel', 'imaxel');
				return $settings_tabs;
			}

			public static function settings_tab()
			{
				woocommerce_admin_fields(self::get_settings());
			}

			public static function update_settings()
			{
				woocommerce_update_options(self::get_settings());
				global $wpdb;
				WC_Imaxel::update_products();
			}

			private static function update_products()
			{
				global $wpdb;
				$imaxelOperations = new ImaxelOperations();
				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

				$imaxelProducts = $imaxelOperations->downloadProducts($publicKey, $privateKey);
				$imaxelProducts2 = null;

				if ($iwebApiUrl)
					$imaxelProducts2 = $imaxelOperations->downloadProducts($publicKey, $privateKey, $iwebApiUrl);

				if ($imaxelProducts || $imaxelProducts2) {

						//Guardar en base de datos
					if ($imaxelProducts) {
						$imaxelProducts = json_decode($imaxelProducts);
						foreach ($imaxelProducts as $product) {
							$row = $wpdb->query("SELECT * FROM " . $wpdb->prefix . "imaxel_woo_products WHERE type=0 AND code='" . $product->code . "'");
							$productPrice = 0;
							if ($row) {
								$sql = "UPDATE " . $wpdb->prefix . "imaxel_woo_products SET
                                        price=" . $productPrice . ",
                         		        name='" . esc_sql($product->name->default) . "'
                         		        WHERE code='" . $product->code . "' AND type=0";
								$wpdb->query($sql);
							} else {
								$sql = "INSERT INTO `" . $wpdb->prefix . "imaxel_woo_products` (code,name,price,type) VALUES
		                		(
		                		'" . $product->code . "',
		                		'" . esc_sql($product->name->default) . "'," . $productPrice
										. ",0)";
								$wpdb->query($sql);
							}
						}
					}
					if ($imaxelProducts2) {
						$imaxelProducts2 = json_decode($imaxelProducts2);
						if(is_array($imaxelProducts2)) {
                            foreach ($imaxelProducts2 as $product) {
                                $row = $wpdb->query("SELECT * FROM " . $wpdb->prefix . "imaxel_woo_products WHERE type=1 AND code='" . $product->code . "'");
                                $productPrice = 0;
                                if ($row) {
                                    $sql = "UPDATE " . $wpdb->prefix . "imaxel_woo_products SET
                         		            price=" . $productPrice . ",
                         		            name='" . esc_sql($product->name->default) . "'
                         		            WHERE code='" . $product->code . "' AND type=1";
                                    $wpdb->query($sql);
                                } else {
                                    $sql = "INSERT INTO `" . $wpdb->prefix . "imaxel_woo_products` (code,name,price,type) VALUES
		                		(
		                		'" . $product->code . "',
		                		'" . esc_sql($product->name->default) . "'," . $productPrice
                                        . ",1)";
                                    $wpdb->query($sql);
                                }
                            }
                        }
                        else{
						    $imaxelProducts2=null;
                        }
					}

					//Borrado de productos que no existen
					$rows = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "imaxel_woo_products");
					foreach ($rows as $row) {
						$delete = false;
						$exist = false;
						if ($row->type == 0) {
							if (!$imaxelProducts)
								$delete = true;
							else {
								foreach ($imaxelProducts as $imxProduct) {
									if (strcmp($imxProduct->code,$row->code)==0) {
										$exist = true;
										break;
									}
								}
								if ($exist == false)
									$delete = true;
							}
						} else if ($row->type == 1) {
							if (!$imaxelProducts2)
								$delete = true;
							else {
								foreach ($imaxelProducts2 as $imxProduct) {
                                    if (strcmp($imxProduct->code,$row->code)==0) {
										$exist = true;
										break;
									}
								}
								if ($exist == false)
									$delete = true;
							}
						}
						if ($delete == true) {
							$sql = "DELETE FROM " . $wpdb->prefix . "imaxel_woo_products WHERE id=" . $row->id;
							$wpdb->query($sql);
						}
					}
				}
			}

			public static function get_settings()
			{
				$settings = array(
						'section_title' => array(
								'name' => __('Configuration', 'imaxel'),
								'type' => 'title',
								'desc' => '',
								'id' => 'wc_settings_tab_imaxel_section_title'
						),
						'publickey' => array(
								'name' => __('Public key', 'imaxel'),
								'type' => 'text',
								'css' => 'min-width:300px;',
								'desc' => __('Introduce Public key supplied by Imaxel', 'imaxel'),
								'id' => 'wc_settings_tab_imaxel_publickey'
						),
						'privatekey' => array(
								'name' => __('Private key', 'imaxel'),
								'type' => 'text',
								'css' => 'min-width:300px;',
								'desc' => __('Introduce Private key supplied by Imaxel', 'imaxel'),
								'id' => 'wc_settings_tab_imaxel_privatekey'
						),
						'urliwebapi' => array(
								'name' => __('URL IwebApi', 'imaxel'),
								'type' => 'text',
								'css' => 'min-width:300px;',
								'desc' => __('Introduce “URL IwebApi” supplied by Imaxel', 'imaxel'),
								'id' => 'wc_settings_tab_imaxel_urliwebapi'
						),
						'automaticproduction' => array(
								'name' => __('Activate automatic production', 'imaxel'),
								'type' => 'checkbox',
								'id' => 'wc_settings_tab_imaxel_automaticproduction'
						),
						'allowguest' => array(
								'name' => __('Allow guest mode', 'imaxel'),
								'type' => 'checkbox',
								'id' => 'wc_settings_tab_imaxel_allow_guest'
						),
						/*#4883
						 * 'buttonhook' => array(
								'title'    => __( 'Hook WooCommerce Imaxel Button', 'imaxel' ),
								'id'       => 'wc_settings_tab_imaxel_button_hook',
								'default'  => 'all',
								'type'     => 'select',
								'class'    => 'wc-enhanced-select',
								'css'      => 'min-width: 350px;',
								'desc_tip' =>  true,
								'options'  => array(
										'woocommerce_product_meta_end'   => "woocommerce_product_meta_end",
										'woocommerce_product_meta_start'   =>"woocommerce_product_meta_start",
										'woocommerce_single_product_summary' => "woocommerce_single_product_summary",
										'woocommerce_after_single_product_summary' => "woocommerce_after_single_product_summary"
								)
						),*/
						'section_end' => array(
								'type' => 'sectionend',
								'id' => 'wc_settings_tab_imaxel_section_end'
						)
				);
				return apply_filters('wc_settings_tab_imaxel_settings', $settings);
			}

			#endregion

			#region Funciones de configuracion de producto
			public function imaxel_product_data_tab($product_data_tabs)
			{
				$product_data_tabs['imaxel-product-tab'] = array(
						'label' => __('Imaxel', 'imaxel'),
						'target' => 'imaxel_product_data'

				);
				return $product_data_tabs;
			}


			public function imaxel_product_data_fields()
			{
				global $post;
				global $wpdb;
				$imaxel_products = $wpdb->get_results("SELECT id, CONCAT(code,' ',name) as name FROM " . $wpdb->prefix . "imaxel_woo_products" . " WHERE type=0 ORDER BY ".$wpdb->prefix ."imaxel_woo_products.name asc");
				$arrayProducts = array(-1 => __('None', 'imaxel'));
				foreach ($imaxel_products as $item) {
					$arrayProducts[$item->id] = $item->name;
				}

				$imaxel_iweb_products = $wpdb->get_results("SELECT id, CONCAT(code,' ',name) as name FROM " . $wpdb->prefix . "imaxel_woo_products" . " WHERE type=1 ORDER BY ".$wpdb->prefix ."imaxel_woo_products.name asc");
				$arrayIWEBProducts = array(-1 => __('None', 'imaxel'));
				foreach ($imaxel_iweb_products as $item) {
					$arrayIWEBProducts[$item->id] = $item->name;
				}
				$selectedProduct = get_post_meta($post->ID, "_imaxel_selected_product", true);

				wp_enqueue_style('style', plugins_url('/assets/css/style.css', __FILE__));
				wp_enqueue_script(
						'imaxel_script',
						plugins_url('/assets/js/imaxel_admin.js', __FILE__),
						array('jquery'),
						TRUE
				);
				wp_localize_script('imaxel_script', 'ajax_object',
						array(
								'url' => admin_url('admin-ajax.php')
						)
				);
				?>
				<div id="imaxel_product_data" class="panel woocommerce_options_panel" style="padding-left:15px;padding-top:15px">
					<div class="imx-loader" style="display:none"></div>
					<button type="button" class="button button-primary" id="btnImaxelUpdateProducts"><?php _e( 'Update products', 'imaxel' ); ?></button>

					<div>HTML products</div>
					<script>
						var $ = jQuery.noConflict();
					</script>
					<style>
						.imaxel_selected_product,.imaxel_selected_product_iweb{
							width:50%
						}
					</style>

					<?php

					woocommerce_wp_text_input(
							array(
									'id' => '_imaxel_filter_products',
									'label' => __('Filter products', 'imaxel'),
									'placeholder' => '',
									'class' => 'imaxel_filter_products'
							)
					);

					woocommerce_wp_select(array(
									'id' => '_imaxel_selected_product',
									'class' => 'imaxel_selected_product',
									'label' => __('Select one product', 'imaxel'),
									'options' => $arrayProducts,
									'value' => $selectedProduct
							)
					);
					?>
					<div>IWEB products</div>
					<?php

					woocommerce_wp_text_input(
							array(
									'id' => '_imaxel_filter_iweb_products',
									'label' => __('Filter products', 'imaxel'),
									'placeholder' => '',
									'class' => 'imaxel_filter_iweb_products'
							)
					);

					woocommerce_wp_select(array(
							'id' => '_imaxel_selected_product_iweb',
							'class' => 'imaxel_selected_product_iweb',
							'label' => __('Select one product', 'imaxel'),
							'options' => $arrayIWEBProducts,
							'value' => $selectedProduct
					));
					?>

					<script>
						jQuery.fn.filterByText = function (textbox) {
							return this.each(function () {
								var select = this;
								var options = [];
								$(select).find('option').each(function () {
									options.push({value: $(this).val(), text: $(this).text()});
								});
								$(select).data('options', options);

								$(textbox).bind('change keyup', function () {
									var options = $(select).empty().data('options');
									var search = $.trim($(this).val());
									var regex = new RegExp(search, "gi");

									$.each(options, function (i) {
										var option = options[i];
										if (option.text.match(regex) !== null) {
											$(select).append(
													$('<option>').text(option.text).val(option.value)
											);
										}
									});
								});
							});
						};
					</script>

					<script>
						jQuery(function ($) {
							jQuery('.imaxel_selected_product').filterByText(jQuery('.imaxel_filter_products'));
							jQuery('.imaxel_selected_product_iweb').filterByText(jQuery('.imaxel_filter_iweb_products'));
						});
					</script>

				</div>
				<?php
			}

			public function imaxel_product_data_fields_save($post_id)
			{
				update_post_meta($post_id, '_imaxel_selected_product', $_POST['_imaxel_selected_product']);
				if ($_POST['_imaxel_selected_product'] == -1) {
					update_post_meta($post_id, '_imaxel_selected_product', $_POST['_imaxel_selected_product_iweb']);
				}
			}

			public function imaxel_product_save($post_id)
			{
				$product = wc_get_product($post_id);
				$selectedProduct = get_post_meta($post_id, "_imaxel_selected_product", true);

				if ($product && $selectedProduct > 0) {
					wp_set_object_terms($post_id, 'variable', 'product_type');
					if ($product->product_type != "variable" || !$product->get_variation_attributes()["proyecto"]) {
						$thedata = Array('proyecto' => Array(
								'name' => 'proyecto',
								'value' => 'CUSTOM_TEXT',
								'is_visible' => '0',
								'is_variation' => '1',
								'is_taxonomy' => '0'
						));
						update_post_meta($post_id, '_product_attributes', $thedata);

						$variation = array(
								'post_title' => 'Product #' . $product->get_id() . ' Variation',
								'post_content' => '',
								'post_status' => 'publish',
								'post_parent' => $product->get_id(),
								'post_type' => 'product_variation'
						);

						$variation_id = wp_insert_post($variation);

						update_post_meta($variation_id, '_price', "0");
						update_post_meta($variation_id, '_regular_price', "0");
					}
				}
			}

			public function imaxel_update_products()
			{
				WC_Imaxel::update_products();
			}
			#endregion

			#region Fuciones de ficha de producto

			public function imaxel_custom_hide_buttons()
			{
				global $wpdb;
				$product = wc_get_product();
				if ($product) {

					$selectedProduct = get_post_meta($product->get_id(), "_imaxel_selected_product", true);
					if ($selectedProduct && $selectedProduct > 0) {
						$sql = "SELECT * FROM " . $wpdb->prefix . "imaxel_woo_products WHERE id=" . $selectedProduct;
						$imaxelProduct = $wpdb->get_row($sql);
						if ($imaxelProduct) {
							remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
							remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
							remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
							remove_action('woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30);
							remove_action('woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30);
							remove_action('woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30);
						}
					} else {
					}
				}
			}

			public function imaxel_custom_buy__button()
			{
				global $product;
				global $wpdb;

				$selectedProduct = get_post_meta($product->get_id(), "_imaxel_selected_product", true);
				if ($selectedProduct > 0) {
					$sql = "SELECT * FROM " . $wpdb->prefix . "imaxel_woo_products WHERE id=" . $selectedProduct;
					$imaxelProduct = $wpdb->get_row($sql);
					if ($imaxelProduct) {
						wp_enqueue_style('style', plugins_url('/assets/css/style.css', __FILE__));
						if (wp_is_mobile() && $imaxelProduct->type == 1) {
							echo '<div>' . __('Desktop only', 'imaxel') . '</a>
 							</div>';
						} else {
							echo '<div class="crear_ahora_wrapper">
						<div class="imx-loader" style="display:none"></div>
 						<a class="single_add_to_cart_button secondary button alt editor_imaxel" data-productid="' . $product->get_id() . '" >' . __('Create now', 'imaxel') . '</a>
 						</div>';
							wp_enqueue_script(
									'imaxel_script',
									plugins_url('/assets/js/imaxel.js', __FILE__),
									array('jquery'),
									TRUE
							);
							wp_localize_script('imaxel_script', 'ajax_object',
									array(
											'url' => admin_url('admin-ajax.php'),
											'backurl' => get_permalink()
									)
							);
						}
					}
				}
			}

			public function imaxel_wrapper()
			{
				global $wpdb;
				global $woocommerce;
				admin_url('admin-ajax.php');
				$currentURL = get_permalink();
				$product = wc_get_product($_POST["productID"]);
				$variations = $product->get_children();

				$selectedProductID = get_post_meta($product->post->ID, "_imaxel_selected_product", true);
				$sql = "SELECT * FROM " . $wpdb->prefix . "imaxel_woo_products WHERE id=" . $selectedProductID;
				$imaxelProduct = $wpdb->get_row($sql);

				$backURL = esc_url($_POST["backURL"]);

				$cartURL = get_home_url();
				$cartURLParameters= "?imx-add-to-cart=" . $product->get_id() . "&variation_id=" . $variations[0] . "&imx_product_type=" . $imaxelProduct->type;

                $saveURL=get_home_url();
                $saveURLParameters= "?imx-add-to-project=" . $product->get_id() . "&variation_id=" . $variations[0] . "&imx_product_type=" . $imaxelProduct->type;

                $imaxelOperations = new ImaxelOperations();
				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

				if ($imaxelProduct->type == 0) {
					$response = $imaxelOperations->createProject($publicKey, $privateKey, $imaxelProduct->code, $cartURL, $cartURLParameters, $backURL, $saveURL,$saveURLParameters);
				} else {
					$response = $imaxelOperations->createProject($publicKey, $privateKey, $imaxelProduct->code, $cartURL, $cartURLParameters, $backURL, $saveURL, $saveURLParameters,$iwebApiUrl);
				}
				echo $response;
				die();
			}
			#endregion

			#region Fuciones añadir carrito
			public function imaxel_add_to_cart($cart_item_key, $product_id)
			{
				global $wpdb,$woocommerce;
				$guestModeEnabled = get_option("wc_settings_tab_imaxel_allow_guest");
				$userID = get_current_user_id();
				if($userID>0 || $guestModeEnabled=="yes") {
					if(isset($_GET["attribute_proyecto"])) {
						$imaxelOperations = new ImaxelOperations();
						$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
						$publicKey = get_option("wc_settings_tab_imaxel_publickey");
						$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

						$projectID = $_GET["attribute_proyecto"];
						$id_product_attribute = $_GET["variation_id"];
						$productType = 0;
						if (isset($_GET["imx_product_type"]) && $_GET["imx_product_type"] == 1) {
							$projectInfo = $imaxelOperations->readProject($publicKey, $privateKey, $projectID, $iwebApiUrl);
							$productType = 1;
						} else {
							$projectInfo = $imaxelOperations->readProject($publicKey, $privateKey, $projectID);
						}

						if ($projectInfo) {
							$projectInfo = json_decode($projectInfo);
							$productName = $projectInfo->product->name->default;
							$productPrice = $projectInfo->design->price;
							$productVariant = $projectInfo->design->variant_code;
							$productWeight = $projectInfo->design->volumetricWeight;
						}

						$_product = wc_get_product($product_id);
						$_product_price = $_product->get_price();

						$exists = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "imaxel_woo_projects WHERE id_customer=" . $userID . " AND id_project=" . $projectID);
						if (!$exists) {
						  $sql = "INSERT INTO " . $wpdb->prefix . "imaxel_woo_projects (id_customer, id_project, type_project,id_product, id_product_attribute, price_project, weight_project)
						  VALUES (
						  " . $userID . "," . $projectID . "," . $productType . "," . $product_id . "," . $id_product_attribute . "," . $productPrice . "," . $productWeight .
							  ")";
						  $wpdb->query($sql);
						} else {
						  $sql = "UPDATE " . $wpdb->prefix . "imaxel_woo_projects 
							SET 
							  price_project=" . $productPrice . ",
							  weight_project=" . $productWeight . " 
							WHERE
							  id_customer=" . $userID . " and id_project = " . $projectID ;
						  $wpdb->query($sql);
						}
					}
				}
			}

			public function imaxel_custom_cart_price($cart_object)
			{
				global $woocommerce;
				global $wpdb;
				foreach ($cart_object->cart_contents as $key => $value) {
					if(array_key_exists("variation",$value)) {
						if(array_key_exists("attribute_proyecto", $value["variation"])) {
							$projectID = $value["variation"]["attribute_proyecto"];
							if ($projectID) {
								$sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                				WHERE id_project =' . $projectID;
								$row = $wpdb->get_row($sql);
								if ($row) {
									if ( function_exists( 'WC' ) && ( version_compare( WC()->version, "3.0.0" )>=0)) {
										$value['data']->set_price($row->price_project);
                                        $value['data']->set_weight($row->weight_project);
									}
									else{
										$value['data']->price = $row->price_project;
                                        $value['data']->weight= $row->weight_project;
                                    }
								}
							}
						}
					}
				}

			}

			#endregion

			#region Funciones de redireccion

			public function imaxel_wc_custom_user_redirect($redirect)
			{
				global $woocommerce;

                if(isset($_GET["imx-add-to-cart"])) {
					$url=get_home_url();
                    if ( function_exists( 'WC' ) && ( version_compare( WC()->version, "3.1.0" )>=0)) {
                        $url = add_query_arg("imx-add-to-cart", $_GET["imx-add-to-cart"], $url);
                    }
                    else{
                        $url = add_query_arg("add-to-cart", $_GET["imx-add-to-cart"], $url);
                    }
					$url=add_query_arg("variation_id", $_GET["variation_id"], $url);
					$url=add_query_arg("imx_product_type", $_GET["imx_product_type"], $url);
					$url=add_query_arg("attribute_proyecto", $_GET["attribute_proyecto"], $url);
					wp_safe_redirect($url);
				}
                else if(isset($_GET["imx-add-to-project"])) {
                    $url=get_home_url();
                    $url=add_query_arg("imx-add-to-project", $_GET["imx-add-to-project"], $url);
                    $url=add_query_arg("variation_id", $_GET["variation_id"], $url);
                    $url=add_query_arg("imx_product_type", $_GET["imx_product_type"], $url);
                    $url=add_query_arg("attribute_proyecto", $_GET["attribute_proyecto"], $url);
                    wp_safe_redirect($url);
                }
				else{
					$url=get_permalink(get_option('woocommerce_myaccount_page_id'));
					wp_safe_redirect($url);
				}
			}

			public function imaxel_redirection_function()
			{
				global $woocommerce;
				$guestModeEnabled = get_option("wc_settings_tab_imaxel_allow_guest");
                if(isset($_GET["imx-add-to-project"])) {
                    if(is_front_page() && (is_user_logged_in() == false)) {
                        $url = get_permalink(get_option('woocommerce_myaccount_page_id'));
                        $url = add_query_arg("imx-add-to-project", $_GET["imx-add-to-project"], $url);
                        $url = add_query_arg("variation_id", $_GET["variation_id"], $url);
                        $url = add_query_arg("imx_product_type", $_GET["imx_product_type"], $url);
                        $url = add_query_arg("attribute_proyecto", $_GET["attribute_proyecto"], $url);
                        wp_safe_redirect($url);
                    }
                    else if(is_front_page() && (is_user_logged_in() == true )){
                        $this->imaxel_insert_project($_GET["imx-add-to-project"]);
                        $url=get_permalink(get_option('woocommerce_myaccount_page_id'));
                        wp_safe_redirect($url);
                    }
                }
                else{
                    if (is_front_page() && (is_user_logged_in() == false) && $guestModeEnabled=="no") {
                        $url=get_permalink(get_option('woocommerce_myaccount_page_id'));
                        if(isset($_GET["imx-add-to-cart"])) {
                            $url=add_query_arg("imx-add-to-cart", $_GET["imx-add-to-cart"], $url);
                            $url=add_query_arg("variation_id", $_GET["variation_id"], $url);
                            $url=add_query_arg("imx_product_type", $_GET["imx_product_type"], $url);
                            $url=add_query_arg("attribute_proyecto", $_GET["attribute_proyecto"], $url);
                            wp_safe_redirect($url);
                        }
                    }
                    elseif (is_front_page() && (is_user_logged_in() == true || $guestModeEnabled=="yes")) {
                        if(isset($_GET["imx-add-to-cart"])) {
                            if ( function_exists( 'WC' ) && ( version_compare( WC()->version, "3.1.0" )>=0)) {
                                $arr = array();
                                $arr['attribute_proyecto'] = $_GET["attribute_proyecto"];
                                WC()->cart->add_to_cart( $_GET["imx-add-to-cart"], 1,  $_GET["variation_id"],$arr);
                                $this->imaxel_add_to_cart(null,$_GET["imx-add-to-cart"]);
                                $url=$woocommerce->cart->get_cart_url();
                                wp_safe_redirect($url);
                            }
                            else {
                                $url = get_home_url();
                                $url = add_query_arg("add-to-cart", $_GET["imx-add-to-cart"], $url);
                                $url = add_query_arg("variation_id", $_GET["variation_id"], $url);
                                $url = add_query_arg("imx_product_type", $_GET["imx_product_type"], $url);
                                $url = add_query_arg("attribute_proyecto", $_GET["attribute_proyecto"], $url);
                                wp_safe_redirect($url);
                            }
                        }
                        if(isset($_GET["add-to-cart"])) {
                            $url=$woocommerce->cart->get_cart_url();
                            wp_safe_redirect($url);
                        }
                    }
                }
			}

			#endregion

			#region Funciones procesado de pedido automatico
			public function imaxel_woocommerce_order_status_processing($order_id)
			{
				global $wpdb;

				$automaticProcessing = get_option("wc_settings_tab_imaxel_automaticproduction");
				if (!$automaticProcessing)
					return;
				$imaxelOperations = new ImaxelOperations();

				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");
				$order = new WC_Order($order_id);
				$items = $order->get_items();
				$itemsIweb=array();
				$itemsHtml=array();
				foreach($items as $item){
					if ( function_exists( 'WC' ) && ( version_compare( WC()->version, "3.0.0" )>=0)) {
						$projectID=$item["item_meta"]["proyecto"];
					}
					else{
						$projectID=$item["item_meta"]["proyecto"][0];
					}
					if($projectID) {
						$sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                						WHERE id_project =' . $projectID;
						$row = $wpdb->get_row($sql);
						if ($row) {
							if ($row->type_project == 0) {
								$itemsHtml[] = $item;
							} elseif ($row->type_project == 1) {
								$itemsIweb[] = $item;
							}
						}
					}
				}
				$customer = $order->get_address("billing");
                $shipping = $order->get_address("shipping");
                if(sizeof($itemsHtml)>0){
					$response = $imaxelOperations->processOrder($publicKey, $privateKey, $order, $itemsHtml, $customer, $shipping);
				}
				if(sizeof($itemsIweb)>0){
					$response = $imaxelOperations->processOrder($publicKey, $privateKey, $order, $itemsIweb, $customer, $shipping,$iwebApiUrl,true);
				}
			}

			#endregion

			#region Funciones de mi cuenta
			public function imaxel_my_projects_imaxel($user_id)
			{
				global $woocommerce;
				$cart_object=$woocommerce->cart;
				$projectsInCart=Array();
				foreach ($cart_object->cart_contents as $key => $value) {
					if(array_key_exists("variation",$value)) {
						if (array_key_exists("attribute_proyecto", $value["variation"])) {
							$projectsInCart[] = $value["variation"]["attribute_proyecto"];
						}
					}
				}

				$filters = array(
						'post_status' => 'any',
						'post_type' => 'shop_order',
						'posts_per_page' => 2000,
						'paged' => 1,
						'orderby' => 'modified',
						'order' => 'DESC',
						'meta_query' => array(
								array(
										'key' => '_customer_user',
										'value' => get_current_user_id(),
										'compare' => '='
								)
						)
				);

				$loop = new WP_Query($filters);
				//LOOP DATA ORDERS
				while ($loop->have_posts()) {
					$loop->the_post();
					$order = new WC_Order($loop->post->ID);
					$user_id = (method_exists($order,"get_user_id") ? $order->get_user_id() : $order->id) ;
					$data_extra = $order->get_items();
					foreach ($data_extra as $producto) {
						if(isset($producto["proyecto"])) {
							$order_data["" . $producto["proyecto"] . ""] = array(
									'order_id' => (method_exists($order,"get_id") ? $order->get_id() : $order->id) ,
									'status_WC' => $order->get_status(),
									'line_total' => $producto["line_total"],
									'client_id' => '' .  (method_exists($order,"get_billing_first_name") ? $order->get_billing_first_name() : $order->billing_first_name) . ' ' . (method_exists($order,"get_billing_first_name") ? $order->get_billing_last_name() : $order->get_billing_last_name) . '',
									'user_id' => '' . $user_id . ''
							);
							$order_data["" . $producto["proyecto"] . "_WC"] = new WC_Order($loop->post->ID);
						}
					}
				}
				wp_reset_query();


				global $wpdb;
				$userID = get_current_user_id();
				$sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                WHERE id_customer =' . $userID;

				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

				if ($projects = $wpdb->get_results($sql)) {
					$imaxelOperations = new ImaxelOperations();
					foreach ($projects as $key => $project) {
						if($project->type_project==1) {
							$projectInfo = $imaxelOperations->readProject(
									$publicKey,
									$privateKey,
									$project->id_project,
									$iwebApiUrl
							);
						}
						else{
							$projectInfo = $imaxelOperations->readProject(
									$publicKey,
									$privateKey,
									$project->id_project
							);
						}
						if ($projectInfo) {
							$projectInfo = json_decode($projectInfo);
							$projects[$key]->date = $projectInfo->updatedAt;
							$projects[$key]->product = $projectInfo->product->name->default;
						}
						else{
							$projects[$key]->error=true;
						}
						if(isset($order_data)) {
							if(isset($order_data[$project->id_project])) {
								$order = $order_data[$project->id_project];
								$projects[$key]->order = $order;
							}
						}
						$projects[$key]->id_product = $project->id_product;
						if (in_array($project->id_project, $projectsInCart)){
							$projects[$key]->inCart=true;
						}

					}
				}

				$pathImg = plugins_url('/assets/img/', __FILE__);

				wp_enqueue_style('style', plugins_url('/assets/css/style.css', __FILE__));
				wp_enqueue_script(
						'imaxel_script',
						plugins_url('/assets/js/imaxel_myaccount.js', __FILE__),
						array('jquery'),
						TRUE
				);
				wp_localize_script('imaxel_script', 'ajax_object',
						array(
								'url' => admin_url('admin-ajax.php'),
								'literal_delete_warning' => __('Are you sure you want to delete this project?','imaxel'),
								'backurl' => get_permalink()
						)
				);

				//TABLE IN MY ACCOUNT
				if ($projects) :
                    usort($projects, function($a, $b) {
                        return strtotime($b->date) - strtotime($a->date);
                    });
                    ?>

					<div id="divImaxelProjects">
						<h2><?php echo apply_filters('woocommerce_my_account_my_orders_title', __('My projects', 'imaxel')); ?></h2>
						<table class="shop_table shop_table_responsive my_account_orders">
							<thead>
							<tr>
								<th class="order-number"><span
											class="nobr"><?php echo __('Project', 'imaxel'); ?></span></th>
								<th class="order-date"><span class="nobr"><?php echo __('Updated', 'imaxel'); ?></span>
								</th>
								<th class="order-status"><span class="nobr"><?php echo __('Order', 'imaxel'); ?></span>
								</th>
								<th class="order-total"><span class="nobr"><?php echo __('Product', 'imaxel'); ?></span>
								</th>
								<th class="order-actions">&nbsp;</th>
							</tr>
							</thead>

							<tbody><?php
							foreach ($projects as $project) {
								echo '<tr id="project-' . $project->id_project . '">
									<td>' . $project->id_project . '</td>
									<td>' . strftime('%Y-%m-%d %H:%M:%S', strtotime($project->date)) . '</td>
									<td>'.(isset($project->order) ? $project->order["order_id"] :"").'</td>
									<td>' . $project->product . '</td>
									<td>
									<div>
										'.(!isset($project->inCart) ? '<a id="delete" style="" class="imaxel-btn-delete" title="" href=""><img  src="' . $pathImg . 'delete.png"/></a>' :"")
										.(!isset($project->order) && !isset($project->inCart) ? '<a id="edit" style="" class="imaxel-btn-edit" title="" href=""><img  src="' . $pathImg . 'edit.png"/></a>' :"")
										.'<a id="duplicate" title="" class="imaxel-btn-duplicate" href=""><img  src="' . $pathImg . 'duplicate.png"/></a>
										<a id="buy" title="" href="" style="display:none"><img  src="' . $pathImg . 'buy.png"/></a>
									</div>
									</td>
								</tr>
								';
							}
							?></tbody>
						</table>
					</div>
				<?php endif;
			}

			public function imaxel_edit_project()
			{
				global $wpdb;
				global $woocommerce;
				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");
                $userID = get_current_user_id();
				$projectID = intval($_POST["projectID"]);
                if($projectID>0) {

                    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                			WHERE id_customer =' . $userID . " AND id_project=" . $projectID;
                    $row = $wpdb->get_row($sql);

                    $product = wc_get_product($row->id_product);
                    $variations = $product->get_children();

                    $urlCancel = esc_url($_POST["backURL"]);

                    $urlCart = get_home_url();
                    if ( function_exists( 'WC' ) && ( version_compare( WC()->version, "3.1.0" )>=0)) {
                        $cartURLParameters = "?imx-add-to-cart=" . $row->id_product . "&variation_id=" . $variations[0] . "&attribute_proyecto=" . $projectID . "&imx_product_type=" . $row->type_project;
                    }
                    else{
                        $cartURLParameters = "?add-to-cart=" . $row->id_product . "&variation_id=" . $variations[0] . "&attribute_proyecto=" . $projectID . "&imx_product_type=" . $row->type_project;
                    }

                    if (isset($_POST["returnURL"]))
                        $urlCart = esc_url($_POST["returnURL"]);

                    $imaxelOperations = new ImaxelOperations();
                    if ($row->type_project == 0) {
                        $projectUrl = $imaxelOperations->editProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel, "", "");
                    } else {
                        $projectUrl = $imaxelOperations->editProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel, "", "", $iwebApiUrl);
                    }
                    echo $projectUrl;
                    die();
                }
			}

			public function imaxel_duplicate_project()
			{
				global $wpdb;
				global $woocommerce;

				$privateKey = get_option("wc_settings_tab_imaxel_privatekey");
				$publicKey = get_option("wc_settings_tab_imaxel_publickey");
				$iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

				$projectID = intval($_POST["projectID"]);
				if($projectID>0) {
                    $userID = get_current_user_id();

                    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                			WHERE id_customer =' . $userID . " AND id_project=" . $projectID;
                    $row = $wpdb->get_row($sql);

                    $product = wc_get_product($row->id_product);
                    $variations = $product->get_children();

                    $urlCancel = esc_url($_POST["backURL"]);

                    $urlCart = get_home_url(); //$woocommerce->cart->get_cart_url();
                    if ( function_exists( 'WC' ) && ( version_compare( WC()->version, "3.1.0" )>=0)) {
                        $cartURLParameters = "?imx-add-to-cart=" . $row->id_product . "&variation_id=" . $variations[0] . "&imx_product_type=" . $row->type_project;
                    }
                    else{
                        $cartURLParameters = "?add-to-cart=" . $row->id_product . "&variation_id=" . $variations[0] . "&imx_product_type=" . $row->type_project;
                    }

                    $saveURL = get_home_url();
                    $saveURLParameters = "?imx-add-to-project=" . $row->id_product . "&variation_id=" . $variations[0] . "&imx_product_type=" . $row->type_project;

                    if (isset($_POST["returnURL"]))
                        $urlCart = esc_url($_POST["returnURL"]);

                    $imaxelOperations = new ImaxelOperations();
                    if ($row->type_project == 0) {
                        $projectUrl = $imaxelOperations->duplicateProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel, $saveURL, $saveURLParameters);
                    } else {
                        $projectUrl = $imaxelOperations->duplicateProject($publicKey, $privateKey, $projectID, $urlCart, $cartURLParameters, $urlCancel, $saveURL, $saveURLParameters, $iwebApiUrl);
                    }
                    if ($projectUrl) {
                        echo $projectUrl[1];
                    }
                    die();
                }
			}

			public function imaxel_delete_project()
			{
				global $wpdb;
				$projectID = intval($_POST["projectID"]);
				$userID = get_current_user_id();

				if ($projectID>0 && $userID) {
					$sql = 'DELETE FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                			WHERE id_customer =' . $userID . " AND id_project=" . $projectID;
					$wpdb->query($sql);
				}
				die();
			}
			#endregion


			public function woocommerce_loaded()
			{
				global $woocommerce;
				global $product;
			}

			public function plugins_loaded()
			{
                if ( class_exists('WC_Local_Pickup_Plus') ) {

                }
                if ( get_site_option( 'imaxel_db_version' ) != $this->imaxel_db_version ) {
                    $this->update_plugin_db($this->imaxel_db_version);
                }
			}

			private function update_plugin_db($version){

                global $wpdb;
                if($version=="1.4.0.0"){
                    $table_name = $wpdb->prefix . 'imaxel_woo_projects';
                    $wpdb->query("ALTER TABLE " . $table_name ." ADD weight_project FLOAT NULL DEFAULT 0");
			    }
                add_option( 'imaxel_db_version', $this->imaxel_db_version );
            }

			public function imaxel_after_setup_theme(){
			}

			#region Funciones asignacion proyectos anonimos
			private function imaxel_assign_anonymous_project($userID){
				//Analizar carrito
				global $woocommerce;
				global $wpdb;
				$items = $woocommerce->cart->get_cart();
				foreach ($items as $key => $value) {
					if(array_key_exists("variation",$value)) {
						if(array_key_exists("attribute_proyecto", $value["variation"])) {
							$projectID = $value["variation"]["attribute_proyecto"];
							if ($projectID) {
								$sql = 'SELECT * FROM ' . $wpdb->prefix . 'imaxel_woo_projects
                							WHERE id_project =' . $projectID . " AND id_customer=0";
								$row = $wpdb->get_row($sql);
								if ($row) {
									$sql = "UPDATE " . $wpdb->prefix . "imaxel_woo_projects
												 SET id_customer=" .$userID.
											" WHERE id_project=" . $projectID;
									$wpdb->query($sql);
								}
							}
						}
					}
				}
			}
			public function imaxel_register_user($userID){
				$guestModeEnabled = get_option("wc_settings_tab_imaxel_allow_guest");
				if($guestModeEnabled=="yes"){
					$this->imaxel_assign_anonymous_project($userID);
				}
			}

			public function imaxel_login_user($user_login, $user){
				$guestModeEnabled = get_option("wc_settings_tab_imaxel_allow_guest");
				if($guestModeEnabled=="yes"){
					$userID = $user->ID;
					$this->imaxel_assign_anonymous_project($userID);
				}
			}
			#endregion

            #region Funciones privadas
            private function imaxel_insert_project($product_id){
                global $wpdb,$woocommerce;

                $imaxelOperations = new ImaxelOperations();
                $privateKey = get_option("wc_settings_tab_imaxel_privatekey");
                $publicKey = get_option("wc_settings_tab_imaxel_publickey");
                $iwebApiUrl = get_option("wc_settings_tab_imaxel_urliwebapi");

                $userID = get_current_user_id();
                $projectID = $_GET["attribute_proyecto"];
                $id_product_attribute = $_GET["variation_id"];
                $productType = 0;
                if (isset($_GET["imx_product_type"]) && $_GET["imx_product_type"] == 1) {
                    $projectInfo = $imaxelOperations->readProject($publicKey, $privateKey, $projectID, $iwebApiUrl);
                    $productType = 1;
                } else {
                    $projectInfo = $imaxelOperations->readProject($publicKey, $privateKey, $projectID);
                }

                if ($projectInfo) {
                    $projectInfo = json_decode($projectInfo);
                    $productName = $projectInfo->product->name->default;
                    $productPrice = $projectInfo->design->price;
                    $productVariant = $projectInfo->design->variant_code;
                }

                $_product = wc_get_product($product_id);
                $_product_price = $_product->get_price();

                $exists = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "imaxel_woo_projects WHERE id_customer=" . $userID . " AND id_project=" . $projectID);
                if (!$exists) {
                    $sql = "INSERT INTO " . $wpdb->prefix . "imaxel_woo_projects (id_customer, id_project, type_project,id_product, id_product_attribute, price_project)
							VALUES (
							" . $userID . "," . $projectID . "," . $productType . "," . $product_id . "," . $id_product_attribute . "," . $productPrice .
                        ")";
                    $wpdb->query($sql);
                }
            }
            #endregion
		}
	}
	// finally instantiate our plugin class and add it to the set of globals
	$GLOBALS['wc_imaxel'] = new WC_Imaxel();
}