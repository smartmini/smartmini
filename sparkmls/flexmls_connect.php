<?php
/*
Plugin Name: Sparkmls
Plugin URI: http://www.flexmls.com/wpdemo/
Description: Provides FlexMLS&reg; Customers with FlexMLS&reg; IDX features on their WordPress websites. <strong>Tips:</strong> <a href="admin.php?page=fmc_admin_settings">Activate your FlexMLS&reg; IDX plugin</a> on the settings page; <a href="widgets.php">add widgets to your sidebar</a> using the Widgets Admin under Appearance; and include widgets on your posts or pages using the FlexMLS&reg; IDX Widget Short-Code Generator on the Visual page editor.
Author: FBS
Version: 3.5.11.1
Author URI: http://www.flexmls.com/
*/
defined( 'ABSPATH' ) or die( 'This plugin requires WordPress' );

const FMC_API_BASE = 'api.flexmls.com';
const FMC_API_VERSION = 'v1';
const FMC_LOCATION_SEARCH_URL = 'https://www.flexmls.com';
const FMC_PLUGIN_VERSION = '3.5.11.1';

define( 'FMC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

global $auth_token_failures;
$auth_token_failures = 0;

$fmc_version = FMC_PLUGIN_VERSION;
$fmc_plugin_dir = dirname(realpath(__FILE__));
$fmc_plugin_url = plugins_url() .'/sparkmls';

if( defined( 'FMC_DEV' ) && FMC_DEV && WP_DEBUG ){
	ini_set( 'error_log', FMC_PLUGIN_DIR . '/debug.log' );
}

class FlexMLS_IDX {

	function __construct(){
		global $wpdb;
		require_once( 'Admin/autoloader.php' );
		require_once( 'Shortcodes/autoloader.php' );
		require_once( 'SparkAPI/autoloader.php' );
		require_once( 'Widgets/autoloader.php' );

		require_once( 'lib/base.php' );
		require_once( 'lib/flexmls-json.php' );
		require_once( 'lib/settings-page.php' );
		require_once( 'lib/flexmlsAPI/Core.php' );
		require_once( 'lib/flexmlsAPI/WordPressCache.php' );
		require_once( 'lib/oauth-api.php' );
		require_once( 'lib/apiauth-api.php' );
		require_once( 'lib/fmc_settings.php' );
		require_once( 'lib/fmcStandardStatus.php' );
		require_once( 'lib/account.php' );
		require_once( 'lib/idx-links.php' );
		require_once( 'pages/portal-popup.php' );
		require_once( 'components/widget.php' );
		require_once( 'components/photo_settings.php' );
		require_once( 'components/listing-map.php' );
		require_once( 'pages/core.php' );
		require_once( 'pages/full-page.php' );
		require_once( 'pages/listing-details.php' );
		require_once( 'pages/search-results.php' );
		require_once( 'pages/fmc-agents.php' );
		require_once( 'pages/next-listing.php' );
		require_once( 'pages/prev-listing.php' );
		require_once( 'pages/oauth-login.php' );

		add_action( 'admin_enqueue_scripts', array( '\FlexMLS\Admin\Enqueue', 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'flexmls_hourly_cache_cleanup', array( '\FlexMLS\Admin\Update', 'hourly_cache_cleanup' ) );
		add_action( 'plugins_loaded', array( '\FlexMLS\Admin\Settings', 'update_settings' ), 9 );
		add_action( 'plugins_loaded', array( $this, 'session_start' ) );
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		//add_action( 'wp_ajax_fmcShortcodeContainer', array( 'flexmlsConnect', 'shortcode_container' ) );
		add_action( 'wp_ajax_fmcShortcodeContainer', array( '\FlexMLS\Admin\TinyMCE', 'tinymce_shortcodes' ) );
		add_action( 'wp_ajax_tinymce_shortcodes_generate', array( '\FlexMLS\Admin\TinyMCE', 'tinymce_shortcodes_generate' ) );
		add_action( 'wp_ajax_fmcleadgen_shortcode', array( '\FlexMLS\Shortcodes\LeadGeneration', 'tinymce_form' ) );
		add_action( 'wp_ajax_fmcleadgen_submit', array( '\FlexMLS\Shortcodes\LeadGeneration', 'submit_lead' ) );
		add_action( 'wp_ajax_nopriv_fmcleadgen_submit', array( '\FlexMLS\Shortcodes\LeadGeneration', 'submit_lead' ) );
		add_action( 'wp_enqueue_scripts', array( '\FlexMLS\Admin\Enqueue', 'wp_enqueue_scripts' ) );

		add_shortcode( 'idx_frame', array( 'flexmlsConnect', 'shortcode' ) );
		add_shortcode( 'lead_generation', array( '\FlexMLS\Shortcodes\LeadGeneration', 'shortcode' ) );
		add_shortcode( 'neighborhood_page', array( 'FlexMLS\Shortcodes\NeighborhoodPage', 'shortcode' ) );

		// $SparkAPI = new \SparkAPI\Core();
		// $SparkAPI->clear_cache( true );
	}

	function admin_menu(){
		$SparkAPI = new \SparkAPI\Core();
		$auth_token = $SparkAPI->generate_auth_token();
		add_menu_page( 'SparkMLS&reg; IDX', 'SparkMLS&reg; IDX', 'edit_posts', 'fmc_admin_intro', array( '\FlexMLS\Admin\Settings', 'admin_menu_cb_intro' ), 'dashicons-location', 77 );
		if( $auth_token ){
			add_submenu_page( 'fmc_admin_intro', 'SparkMLS&reg; IDX: Add Neighborhood', 'Add Neighborhood', 'edit_pages', 'fmc_admin_neighborhood', array( '\FlexMLS\Admin\Settings', 'admin_menu_cb_neighborhood' ) );
		}
		add_submenu_page( 'fmc_admin_intro', 'FlexMLS&reg; IDX: Settings', 'Settings', 'manage_options', 'fmc_admin_settings', array( '\FlexMLS\Admin\Settings', 'admin_menu_cb_settings' ) );
	}

	function admin_notices(){
		if( current_user_can( 'manage_options' ) ){
			$required_php_extensions = array();
			if( !extension_loaded( 'curl' ) ){
				$required_php_extensions[] = 'cURL';
			}
			if( !extension_loaded( 'bcmath' ) ){
				$required_php_extensions[] = 'BC Math';
			}
			if( count( $required_php_extensions ) ){
				printf(
					'<div class="notice notice-error"><p>Your website&#8217;s server does not have <em>' . implode( '</em> or <em>', $required_php_extensions ) . '</em> enabled which %1$s required for the FlexMLS&reg; IDX plugin. Please contact your webmaster and have %2$s enabled on your website hosting plan.</p></div>',
					_n( 'is', 'are', count( $required_php_extensions ) ),
					_n( 'this extension', 'these extensions', count( $required_php_extensions ) )
				);
			}
			$options = get_option( 'fmc_settings' );
			if( empty( $options[ 'api_key' ] ) || empty( $options[ 'api_secret' ] ) ){
				printf(
					'<div class="notice notice-warning">
						<p>You must enter your FlexMLS&reg; API Credentials. <a href="%1$s">Click here</a> to enter your API credentials, or <a href="%2$s">contact FlexMLS&reg; support</a>.</p>
					</div>',
					admin_url( 'admin.php?page=fmc_admin_settings' ),
					admin_url( 'admin.php?page=fmc_admin_intro&tab=support' )
				);
			} else {
				$SparkAPI = new \SparkAPI\Core();
				$auth_token = $SparkAPI->generate_auth_token();
				if( false === $auth_token ){
					printf(
						'<div class="notice notice-error">
							<p>You are not connected to the FlexMLS&reg; API. <a href="%1$s">Click here</a> to verify that your API credentials are correct, or <a href="%2$s">contact FlexMLS&reg; support</a>.</p>
						</div>',
						admin_url( 'admin.php?page=fmc_admin_settings' ),
						admin_url( 'admin.php?page=fmc_admin_intro&tab=support' )
					);
				} else {
					if( !isset( $options[ 'google_maps_api_key' ] ) || empty( $options[ 'google_maps_api_key' ] ) ){
						printf(
							'<div class="notice notice-warning is-dismissible">
								<p>You have not entered a Google Maps API Key. It&#8217;s not required for the FlexMLS&reg; IDX plugin, but maps will not show on your site without a Google Maps API key. <a href="%1$s">Click here</a> to enter your Google Map API Key, or <a href="%2$s" target="_blank">generate a Google Map API Key here</a>.</p>
							</div>',
							admin_url( 'admin.php?page=fmc_admin_settings&tab=gmaps' ),
							'https://developers.google.com/maps/documentation/javascript/get-api-key#get-an-api-key'
						);
					}
				}
			}
		}
	}

	public static function plugin_activate(){
		$is_fresh_install = false;
		if( false === get_option( 'fmc_settings' ) ){
			$is_fresh_install = true;
		}
		\FlexMLS\Admin\Update::set_minimum_options( $is_fresh_install );
		add_action( 'shutdown', 'flush_rewrite_rules' );
		if( false === get_option( 'fmc_plugin_version' ) ){
			add_option( 'fmc_plugin_version', FMC_PLUGIN_VERSION, null, 'no' );
		}
	}

	public static function plugin_deactivate(){
		$SparkAPI = new \SparkAPI\Core();
		$SparkAPI->clear_cache( true );
	}

	public static function plugin_uninstall(){
		$timestamp = wp_next_scheduled( 'flexmls_hourly_cache_cleanup' );
		if( $timestamp ){
			wp_unschedule_event( $timestamp, 'flexmls_hourly_cache_cleanup' );
		}
		$SparkAPI = new \SparkAPI\Core();
		$SparkAPI->clear_cache( true );
		delete_option( 'fmc_cache_version' );
		delete_option( 'fmc_plugin_version' );
		delete_option( 'fmc_settings' );
		flush_rewrite_rules();
	}

	function session_start(){
		if( !session_id() ){
			session_start();
		}
		$SparkAPI = new \SparkAPI\Core();
		$fmc_plugin_version = get_option( 'fmc_plugin_version' );
		if( false === $fmc_plugin_version || version_compare( $fmc_plugin_version, FMC_PLUGIN_VERSION, '<' ) ){
			\FlexMLS\Admin\Update::set_minimum_options();
			$did_clear_cache = $SparkAPI->clear_cache( true );
			update_option( 'fmc_plugin_version', FMC_PLUGIN_VERSION, 'no' );
		}
		if( !wp_next_scheduled( 'flexmls_hourly_cache_cleanup' ) ){
			wp_schedule_event( time(), 'hourly', 'flexmls_hourly_cache_cleanup');
		}
		$auth_token = $SparkAPI->generate_auth_token();
	}

	function widgets_init(){
		global $fmc_widgets;
		$SparkAPI = new \SparkAPI\Core();
		$auth_token = $SparkAPI->generate_auth_token();

		if( $auth_token ){
			register_widget( '\\FlexMLS\\Widgets\\LeadGeneration' );
		}

		// This will come out soon once all of the widgets have been
		// rebuilt as native WordPress widgets and called using
		// register_widget above.
		if( $auth_token && $fmc_widgets ){
			foreach( $fmc_widgets as $class => $wdg ){
				if( file_exists( FMC_PLUGIN_DIR . 'components/' . $wdg[ 'component' ] ) ){
					require_once( FMC_PLUGIN_DIR . 'components/' . $wdg[ 'component' ] );
					// All widgets require a "key" or auth token so this can be removed
					/*
					$meets_key_reqs = false;
					if ($wdg['requires_key'] == false || ($wdg['requires_key'] == true && flexmlsConnect::has_api_saved())) {
						$meets_key_reqs = true;
					}
					*/
					if( class_exists( $class, false ) && true == $wdg[ 'widget' ] ){
						register_widget( $class );
					}
					if( false == $wdg[ 'widget' ] ){
						new $class();
					}
				}
			}
		}
	}

	static function write_log( $log, $title = 'FlexMLS Log Item' ){
		error_log( '---------- ' . $title . ' ----------' );
		if( is_array( $log ) || is_object( $log ) ){
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}

	public function filter_fields_options(){
		 global $fmc_api;

	    // pull StandardFields from the API to verify searchability prior to searching

		 echo '<a style ="color: blue;
    float: right;
    margin-bottom: 25px;" href="#not_searchable">click here for "Not Searchable" fields list</a></br>';
		
	    $fieldsresults = $fmc_api->GetStandardFieldsPlusHasList();
	    echo '<table id="mytable" class="table table-bordred table-striped">
				<thead>
				<tr>
				<th>No.</th>
				<th>Fields</th>
				<th>Type</th>
				<th>Multiselect</th>
				<th>Searchable</th>
				<th>Options</th>

				</tr>
				</thead>
				<tbody>';
				
				if(!empty($fieldsresults))
				{ 
					$i= 0; 
					foreach( $fieldsresults as $key=>$result ) {	
						 if(!empty($result['Searchable'])){
						$i++;		    	
										
				        echo '<tr><td>'.$i.'</td>';
					     echo "<td>";
					     echo $result['Label'];
					    echo '</td>';
					    echo "<td>";
					     echo $result['Type'];
					    echo '</td>';
					    echo "<td>";
					    if(empty($result['MultiSelect']))
					    {					  
					      echo 'False';
					    }else{
					    	echo "True";
					    }
					    echo '</td>';
					    echo "<td>";
					    if(empty($result['Searchable']))
					    {					  
					      echo 'False';
					    }else{
					    	echo "True";
					    }
					    echo '</td>';
					     echo "<td>";
					    if(!empty($result['HasListValues']))
					    {
					    	foreach( $result['HasListValues'] as $keys=>$val ) {
					    		echo $val['Name'];
					    		echo ", ";
					    	}
					    }					   
					    echo '</td></tr>';
					}
					}
				 }				
			    
			    echo '</tbody>
				</table>'; 

				echo '<h1 id="not_searchable" style="color:black;font-weight:bold;font-size: 35px;">Filter fields options (Not Searchable)</h1><table id="mytable" class="table table-bordred table-striped">
				<thead>
				<tr>
				<th>No.</th>
				<th>Fields</th>
				<th>Type</th>
				<th>Multiselect</th>
				<th>Searchable</th>
				<th>Options</th>

				</tr>
				</thead>
				<tbody>';
				
				if(!empty($fieldsresults))
				{ 
					$i= 0; 
					foreach( $fieldsresults as $key=>$result ) {	
						 if(empty($result['Searchable'])){
						$i++;		    	
										
				        echo '<tr><td>'.$i.'</td>';
					     echo "<td>";
					     echo $result['Label'];
					    echo '</td>';
					    echo "<td>";
					     echo $result['Type'];
					    echo '</td>';
					    echo "<td>";
					    if(empty($result['MultiSelect']))
					    {					  
					      echo 'False';
					    }else{
					    	echo "True";
					    }
					    echo '</td>';
					    echo "<td>";
					    if(empty($result['Searchable']))
					    {					  
					      echo 'False';
					    }else{
					    	echo "True";
					    }
					    echo '</td>';
					     echo "<td>";
					    if(!empty($result['HasListValues']))
					    {
					    	foreach( $result['HasListValues'] as $keys=>$val ) {
					    		echo $val['Name'];
					    		echo ", ";
					    	}
					    }					   
					    echo '</td></tr>';
					}
					}
				 }	

			    
			    echo '</tbody>
				</table>'; 
	}


	public function custom_fields_options()
	{
		global $fmc_api;
		$fieldsresults_custom = $fmc_api->GetCustomFields();
		if(!empty($fieldsresults_custom))
		{
			
			foreach( $fieldsresults_custom as $key=>$result ) {	
				
				
				foreach( $result as $keys=>$vals ) {
					if(!empty($vals['Fields']))
					{	$i= 0; 
							echo '<h1 style="color:black;font-weight:bold;text-align:center;">'.$vals['Label'].'</h1><table id="mytable" class="table table-bordred table-striped">
								<thead>
								<tr>
								<th>No.</th>
								<th>Fields</th>
								<th>Type</th>							
								<th>Searchable</th>							
								<th>Options</th>
								</tr>
								</thead>
								<tbody>';
								foreach( $vals['Fields'] as $k=>$v ) {
									
									$i++; 
									echo '<tr><td>'.$i.'</td>';
									echo "<td>";
									echo $v['Label'];
									echo '</td>';
									echo "<td>";
									echo $v['Type'];
									echo '</td>';
									echo "<td>";
									if(empty($v['Searchable']))
									{					  
									echo 'False';
									}else{
									echo "True";
									}
									echo '</td>';
									echo "<td>";
									if($v['HasList'] == 1)
									{
										
										$fieldsresults_custom_options = $fmc_api->GetCustomField($k);
										if(!empty($fieldsresults_custom_options))
										{
											foreach ($fieldsresults_custom_options as $ok => $ov) {
												if(!empty($ov[$k]['FieldList']))
												{
													foreach ($ov[$k]['FieldList'] as $kn => $vn) {
														echo $vn['Name'];
														echo ", ";
													}
												}											
											}
										}
										
									}
									
									echo '</td>';
									echo '</tr>';
								}
					}
					echo '</tbody>
					</table>'; 
				}						
			}
		}
	}



	public function get_detail( $tags, $SavelistingId, $UserId, $SavedOndate, $view_type ){

		global $fmc_special_page_caught;
	    global $wp_query;
	    global $fmc_api;
	    global $fmc_api_portal;

	    $tag = get_query_var('fmc_tag'); 
	    $oauth_tag = get_query_var('oauth_tag');
	    $vow_tag = get_query_var('fmc_vow_tag');
	   

	    if ($vow_tag) {
	      $tag = $vow_tag;
	      $type = 'fmc_vow_tag';
	    }
	    else {
	      //default
	      $type = null;
	    }

	    
	    $api = ($type == 'fmc_vow_tag') ? $fmc_api_portal : $fmc_api;
	    // parse passed parameters for browsing capability
	    $flexmlsConnectPageCore = new flexmlsConnectPageCore($api);
	    list($params, $cleaned_raw_criteria, $context) = $flexmlsConnectPageCore->parse_search_parameters_into_api_request();

	    //$this->search_criteria = $cleaned_raw_criteria;

	    preg_match('/mls\_(.*?)$/', $tags, $matches);

	    $id_found = $matches[1];

	    $filterstr = "ListingId Eq '{$id_found}'";

	    if ( flexmlsConnect::wp_input_get('m') ) {
	      $filterstr .= " and MlsId Eq '".flexmlsConnect::wp_input_get('m')."'";
	    }

	    $params = array(
	        '_filter' => $filterstr,
	        '_limit' => 1,
	        '_expand' => 'Photos,Videos,OpenHouses,VirtualTours,Documents,Rooms,CustomFields,Supplement'
	    );


	    $flexmlsAPI_Core = new flexmlsAPI_Core($api);

	    $result = $flexmlsAPI_Core->GetListings($params);

	    $listing = (count($result) > 0) ? $result[0] : null;

	    //echo "<pre>"; print_r($listing); die;

	    $standard_fields_plus = $flexmlsAPI_Core->GetStandardFields();
    	$standard_fields_plus = $standard_fields_plus[0];
    	// set some variables
	    $record =& $listing;
	    $sf =& $record['StandardFields'];
	    $listing_address = flexmlsConnect::format_listing_street_address($record);
	    $first_line_address = htmlspecialchars($listing_address[0]);
	    $second_line_address = htmlspecialchars($listing_address[1]);
	    $one_line_address = htmlspecialchars($listing_address[2]);

	  

	    $status_class = ($sf['MlsStatus'] == 'Closed') ? 'status_closed' : '';

	    

	  	// Photos
	    if (count($sf['Photos']) >= 1) {
	    $main_photo_url = $sf['Photos'][0]['Uri640'];
	    $main_photo_caption = htmlspecialchars($sf['Photos'][0]['Caption'], ENT_QUOTES);

	      //set alt value
	      if(!empty($main_photo_caption)){
	        $main_photo_alt = $main_photo_caption;
	      }
	      elseif(!empty($one_line_address)){
	        $main_photo_alt = $one_line_address;
	      }
	      else{
	        $main_photo_alt = "Photo for listing #" . $sf['ListingId'];
	      }

	    //set title value
	    $main_photo_title = "Photo for ";
	    if(!empty($one_line_address)) {
	      $main_photo_title .= $one_line_address . " - ";
	    }
	    $main_photo_title .= "Listing #" . $sf['ListingId'];

	    }

	    // figure out if there's a previous listing
        $link_to_details_criteria['p'] = ( $this_result_overall_index != 1 ) ? 'y' : 'n';

        // figure out if there's a next listing possible
        $link_to_details_criteria['n'] = ( $this_result_overall_index < $this->total_rows ) ? 'y' : 'n';

	    $link_to_details = flexmlsConnect::make_nice_address_url( $record, $link_to_details_criteria, 'fmc_tag' );

	    if($view_type == 'return back end view')
	    {
	    	$user_info = get_userdata( $UserId );	
	    	// echo "<pre>"; print_r($user_info->data->display_name); die;
	    	// print_r($user_info);
	    	return  array(
                    'id'          => $SavelistingId,
                    'user'        => "<a href='user-edit.php?user_id=".$UserId."'>".$user_info->data->display_name."</a>",
                    'property_image'       => "<a href='{$link_to_details}'><img src='{$main_photo_url}' class='flexmls_connect__main_image' title='{$main_photo_title}' alt='{$main_photo_alt}' style='width:100px;height:100px'  /></a>",
                    'detail' => "<ul style='margin:0px;'><li><strong>Street Number: </strong>{$first_line_address}</li><li><strong>City: </strong>{$second_line_address}</li><li><strong>Street Name: </strong>{$one_line_address}</li></ul>",
                    'date'    => $SavedOndate
                    );

        	

	    }else{
	    	

			$desk_html = '<tr id="savedlisting'.$SavelistingId.'"><td>';
		    $desk_html .= "<img src='{$main_photo_url}' class='flexmls_connect__main_image' title='{$main_photo_title}' alt='{$main_photo_alt}' style='width:100px;height:100px'  />";
		    $desk_html .= '</td>';

			$desk_html .= "<td><ul style='margin:0px;'><li>";
			$desk_html .= "<strong>Street Number: </strong>{$first_line_address}</li><li>";
			$desk_html .= "<strong>City: </strong>{$second_line_address}</li><li>";
			$desk_html .= "<strong>Street Name: </strong>{$one_line_address}</li></ul></td>";
			$desk_html .= "<td class='td-actions' colspan='2'>";
			$desk_html .= '<p data-placement="top" data-toggle="tooltip" title="" style="width:70px;float:left;" data-original-title="Delete">';
			$desk_html .= "<button class='btn btn-danger btn-xs active search_delete' data-title='Delete' data-id='{$SavelistingId}'>";
			$desk_html .= '<span class="glyphicon glyphicon-trash"></span></button></p>';
			$desk_html .= "<a href='{$link_to_details}''>Show</a>";
			$desk_html .= "</td>
				</tr>";

				 $mobss_html = '<div class="panel panel-default" id="mobsavedlisting'.$SavelistingId.'">
			      <div class="panel-heading">
			        <h4 class="panel-title">
			          <a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion_savedlisting"  href="#collapse'.$SavelistingId.'">Saved Listing</a>
			        </h4>
			      </div>
			      <div id="collapse'.$SavelistingId.'" class="panel-collapse collapse">
			      <div class="panel-body">
		      	 <div class="flexmls_connect__ld_detail_table">
				 <div class="flexmls_connect__detail_header">Property Images</div>
				 <div class="flexmls_connect__ld_property_detail_row">
				 <div class="flexmls_connect__ld_property_detail">';
				 $mobss_html .= "<img src='{$main_photo_url}' class='flexmls_connect__main_image' title='{$main_photo_title}' alt='{$main_photo_alt}' style='width:100px;height:100px'  /></div></div></div>";
				 $mobss_html .= '<div class="flexmls_connect__ld_detail_table">
				 <div class="flexmls_connect__detail_header">Details</div>
				 <div class="flexmls_connect__ld_property_detail_row">
				 <div class="flexmls_connect__ld_property_detail">';
				 $mobss_html .= "<ul style='margin:0px;'><li><strong>Street Number: </strong>{$first_line_address}</li><li><strong>City: </strong>{$second_line_address}</li><li><strong>Street Name: </strong>{$one_line_address}</li></ul>
				 </div></div></div>";
				 $mobss_html .= '<div class="flexmls_connect__ld_detail_table">
				 <div class="flexmls_connect__detail_header">Action</div>
				 <div class="flexmls_connect__ld_property_detail_row" style="height:50px;">
				 <div class="flexmls_connect__ld_property_detail">';
				 $mobss_html .= '<p data-placement="top" data-toggle="tooltip" title="" style="width:70px;float:left;" data-original-title="Delete">';
				 $mobss_html .= "<button class='btn btn-danger btn-xs active search_delete' data-title='Delete' data-id='{$SavelistingId}'><span class='glyphicon glyphicon-trash'></span></button></p> </div></div></div>";
				 $mobss_html .= '<div class="flexmls_connect__ld_detail_table">
				 <div class="flexmls_connect__detail_header">Action</div>
				 <div class="flexmls_connect__ld_property_detail_row" style="height:50px;">
				 <div class="flexmls_connect__ld_property_detail">';
				 $mobss_html .= '<p data-placement="top" data-toggle="tooltip" title="" style="width:70px;float:left;" data-original-title="Delete">';
				 $mobss_html .= "<a href='{$link_to_details}'>Show</a> </div></div></div>";
		    	 $mobss_html .= '</div></div></div>';

		    return array('desk_html'=>$desk_html,'mob_html'=>$mobss_html);
	    }    
		
	}


	public function get_MajorChangeType_saved_listing( $tags, $SavelistingId, $UserId, $SavedOndate,$majorchangetype, $MinMajorChangeTimestamp, $MaxMajorChangeTimestamp, $price_reduced_ids = array()){
		$body = "";
		global $fmc_special_page_caught;
	    global $wp_query;
	    global $fmc_api;
	    global $fmc_api_portal;

	    $tag = get_query_var('fmc_tag'); 
	    $oauth_tag = get_query_var('oauth_tag');
	    $vow_tag = get_query_var('fmc_vow_tag');
	   

	    if ($vow_tag) {
	      $tag = $vow_tag;
	      $type = 'fmc_vow_tag';
	    }
	    else {
	      //default
	      $type = null;
	    }

	    
	    $api = ($type == 'fmc_vow_tag') ? $fmc_api_portal : $fmc_api;
	    // parse passed parameters for browsing capability
	    $flexmlsConnectPageCore = new flexmlsConnectPageCore($api);
	    list($params, $cleaned_raw_criteria, $context) = $flexmlsConnectPageCore->parse_search_parameters_into_api_request();

	    //$this->search_criteria = $cleaned_raw_criteria;

	    preg_match('/mls\_(.*?)$/', $tags, $matches);

	    $id_found = $matches[1];

	    $filterstr = "ListingId Eq '{$id_found}'";

	    if(!empty($majorchangetype))
	    {
	    	$filterstr .= " and MajorChangeType Eq '{$majorchangetype}'";
	    }

	    if(!empty($MinMajorChangeTimestamp) && !empty($MaxMajorChangeTimestamp))
	    {
	    	$filterstr .= " and MajorChangeTimestamp Bt '{$MinMajorChangeTimestamp}','{$MaxMajorChangeTimestamp}'";
	    }

	    if ( flexmlsConnect::wp_input_get('m') ) {
	      $filterstr .= " and MlsId Eq '".flexmlsConnect::wp_input_get('m')."'";
	    }
		//echo $filterstr; die;
	    $params = array(
	        '_filter' => $filterstr,
	        '_limit' => 1,
	        '_expand' => 'Photos,Videos,OpenHouses,VirtualTours,Documents,Rooms,CustomFields,Supplement'
	    );


	    $flexmlsAPI_Core = new flexmlsAPI_Core($api);

	    $result = $flexmlsAPI_Core->GetListings($params);

	    $listing = (count($result) > 0) ? $result[0] : null;

	    if(!empty($price_reduced_ids) && !empty($listing) && $listing != null)
	    {
	    	$price_reduced_status = 'available';
	    }

	  //  echo "<pre>"; print_r($listing); die;
	    if($majorchangetype == 'Price Reduced' && !empty($listing) && $listing != null)
	    {
	    	$record =& $listing;	    	
	    	$price_reduced_data = array();
	    	foreach ($result as $record) {
	    		array_push($price_reduced_data, $record['Id']);
	    	}
	    	return $price_reduced_data;
	    	exit;
	    }
	    else if(!empty($listing) && $listing != null && $majorchangetype != 'Price Reduced')
	    {
	    	$standard_fields_plus = $flexmlsAPI_Core->GetStandardFields();
	    	$standard_fields_plus = $standard_fields_plus[0];
	    	// set some variables
		    $record =& $listing;

		    $my_list = $flexmlsAPI_Core->GetListing($record['Id']);
		    $status = $my_list['StandardFields']['StandardStatus']; 
		    $pid = $record['Id'];
		    $status = $record['StandardStatus']; 
		    $photos = $fmc_api->GetListingPhotos($pid);
			      	 
		    $sf =& $record['StandardFields'];

		    $listing_address = flexmlsConnect::format_listing_street_address($record);
		    $first_line_address = htmlspecialchars($listing_address[0]);
		    $second_line_address = htmlspecialchars($listing_address[1]);
		    $one_line_address = htmlspecialchars($listing_address[2]);

		  

		    $status_class = ($sf['MlsStatus'] == 'Closed') ? 'status_closed' : '';

		    

		  	// Photos
		    //if (count($sf['PhotosCount']) >= 1) {
		    // $photos = $fmc_api->GetListingPhotos($pid);
		    // echo "<pre>"; print_r($photos); echo "<br>";
		    // if ( count($sf['Photos']) >= 1 ) {		    		  
			   //      //Find primary photo and assign it to $main_photo_url variable.
    		//  	$count_photos = count($sf['Photos']);

		    //     $i = 0;
		    //     while($i < $count_photos){
		    //       if($sf['Photos'][$i]['Primary'] === TRUE){
		    //         $main_photo_url =     $sf['Photos'][$i]['Uri300'];
		    //         $main_photo_urilarge = $sf['Photos'][$i]['UriLarge'];
		    //         $caption = htmlspecialchars($sf['Photos'][$i]['Caption'], ENT_QUOTES);
		    //         break;
		    //       }
		    //       $i++;
		    //     }		    		 
	         	
		    //   } else {
		    //   	$count_photos = count($photos);
		    //     $i = 0;
		    //     while($i < $count_photos){
		    //       if($photos[$i]['Primary'] === TRUE){
		    //         $main_photo_url =     $photos[$i]['Uri300'];
		    //         $main_photo_urilarge = $photos[$i]['UriLarge'];
		    //         $caption = htmlspecialchars($photos[$i]['Caption'], ENT_QUOTES);
		    //         break;
		    //       }
		    //       $i++;
		    //     }

		    //     if(empty($main_photo_url))
		    //     {
		    //       $main_photo_url = "{$fmc_plugin_url}/assets/images/nophoto.gif";
		    //       $main_photo_urilarge = "{$fmc_plugin_url}/assets/images/nophoto.gif";
		    //       $caption = "";
		    //     }
		    //   }

		    if ( count($sf['Photos']) >= 1 ) {
		        //Find primary photo and assign it to $main_photo_url variable.
		         $count_photos = count($sf['Photos']);

		        $i = 0;
		        while($i < $count_photos){
		          if($sf['Photos'][$i]['Primary'] === TRUE){
		            $main_photo_url =     $sf['Photos'][$i]['Uri300'];
		            $main_photo_urilarge = $sf['Photos'][$i]['UriLarge'];
		            $caption = htmlspecialchars($sf['Photos'][$i]['Caption'], ENT_QUOTES);
		            break;
		          }
		          $i++;
		        }

		      } else {
		        // $photos = $fmc_api->GetListingPhotos($record['Id']);
		        // $count_photos = count($photos);
		        // $i = 0;
		        // while($i < $count_photos){
		        //   if($photos[$i]['Primary'] === TRUE){
		        //     $main_photo_url =     $photos[$i]['Uri300'];
		        //     $main_photo_urilarge = $photos[$i]['UriLarge'];
		        //     $caption = htmlspecialchars($photos[$i]['Caption'], ENT_QUOTES);
		        //     break;
		        //   }
		        //   $i++;
		        // }
		        
		        if(empty($main_photo_url))
		        {
		          $main_photo_url = "{$fmc_plugin_url}/assets/images/nophoto.gif";
		          $main_photo_urilarge = "{$fmc_plugin_url}/assets/images/nophoto.gif";
		          $caption = "";
		        }
		        
		      }

		      //set alt value
		      if(!empty($main_photo_caption)){
		        $main_photo_alt = $main_photo_caption;
		      }
		      elseif(!empty($one_line_address)){
		        $main_photo_alt = $one_line_address;
		      }
		      else{
		        $main_photo_alt = "Photo for listing #" . $sf['ListingId'];
		      }

		    //set title value
		    $main_photo_title = "Photo for ";
		    if(!empty($one_line_address)) {
		      $main_photo_title .= $one_line_address . " - ";
		    }
		    $main_photo_title .= "Listing #" . $sf['ListingId'];

		    //}

		    // figure out if there's a previous listing
	        $link_to_details_criteria['p'] = ( $this_result_overall_index != 1 ) ? 'y' : 'n';

	        // figure out if there's a next listing possible
	        $link_to_details_criteria['n'] = ( $this_result_overall_index < $this->total_rows ) ? 'y' : 'n';

		    $link_to_details = flexmlsConnect::make_nice_address_url( $record, $link_to_details_criteria, 'fmc_tag' );

		    $body.= '<table class="m_6340947293587095594m_5820570660198366816MsoNormalTable" style="border-top:solid #e0e0e0 1.0pt;border-left:none;border-bottom:none;border-right:none" cellspacing="0" cellpadding="0" border="1">
				            <tbody>
				            <tr style="height:88.5pt">
				            <td style="border:none;padding:1.25pt 6.75pt 6.75pt 6.75pt;height:88.5pt;overflow:hidden" valign="middle" width="150">
				            <div>
				            <p class="MsoNormal">
				            <a href="'.$link_to_details.'" target="_blank" rel="noreferrer">
				            <span style="color: #5C8CD7; text-decoration: none">
				            <img id="" src="'.$main_photo_url.'" alt="Listing Update" border="0"  width="180" height="135">
				            </span>
				            </a><!-- o ignored -->
				            </p>
				            </div>
				            </td>
				            <td valign="middle" style="border: none; padding: 0; overflow: hidden">
				            <div><p class="MsoNormal" style="padding:0px !important; margin:0px !important;">';
				           if($majorchangetype == 'Price Change' && $price_reduced_status  == "available" && in_array($record['Id'], $price_reduced_ids)){
							$body.='<img id="" alt="Listing Arrow" border="0" width="13" height="13" src="'.get_template_directory_uri().'/images/red-arr.png">';
				            }else if($majorchangetype == 'Price Change'){
				            $body.='<img id="" src="'.get_template_directory_uri().'/images/green-arr.png" alt="Listing Arrow" border="0" width="13" height="13">';
				            }
				           
				            if(flexmlsConnect::is_not_blank_or_restricted($sf['ListPrice'])){
				            	$price = '$' . flexmlsConnect::gentle_price_rounding($sf['ListPrice']);
				            } 
				            $body.='
				            <span style="font-size: 11pt">
				            <a href="'.$link_to_details.'" target="_blank" rel="noreferrer">
				            <span style="color: black; text-decoration: none">'.$price.'</span>
				            </a> <!-- o ignored -->
				            </span>
				            </p>
				            </div>
				            <div style="margin-top: 2.25pt">
				            <p class="MsoNormal" style="padding:0px !important; margin:0px !important;">
				            <span style="font-size: 9pt; color: #5C8CD7">
				            <a href="'.$link_to_details.'" target="_blank" rel="noreferrer">
				            <span style="color: #5C8CD7; text-decoration: none">'.$first_line_address.'</span>
				            </a> <!-- o ignored -->
				            </span>
				            </p>
				            </div>
				            <div style="margin-top: 1pt">
				            <p class="MsoNormal" style="padding:0px !important; margin:0px !important;">
				            <span style="font-size: 9.0pt; color: darkgray">'. $second_line_address .'<!-- o ignored --></span>
				            </p>
				            </div>
				            <div style="margin-top: 1pt">
				            <p class="MsoNormal" style="padding:0px !important; margin:0px !important;">';
				            if($my_list['StandardFields']['StandardStatus'] == 'Active')
				            {
				            	$body.='<span style="font-size: 9.0pt; color: #6DB267">'.$my_list['StandardFields']['StandardStatus'].'</span>';
				            }else if($my_list['StandardFields']['StandardStatus'] == 'Pending')
				            {
				            	$body.='<span style="font-size: 9.0pt; color: #FFAD6F">'.$my_list['StandardFields']['StandardStatus'].'</span>';
				            }else if($my_list['StandardFields']['StandardStatus'] == 'Closed')
				            {
				            	$body.='<span style="font-size: 9.0pt; color: #576FFF">'.$my_list['StandardFields']['StandardStatus'].'</span>';
				            }
				           
				              
				            $body.='<br><span style="font-size: 9.0pt; color: #286090">'.$search_arr['MajorChangeType'].'</span><span style="font-size: 9.0pt; color: darkgray"> <!-- o ignored --></span>
				            </p>
				            </div>
				            </td>
				            </tr>
				            </tbody>
				            </table>';
								

			return $body; 
	    }else{
	    	return 0;
	    }      
		
	}

	public function get_MajorChangeType_saved_search( $search_arr , $type, $listing_url ){
		//print_r($search_arr);
		$body = "";
		global $fmc_special_page_caught;
	    global $wp_query;
	    global $fmc_api;
	    global $fmc_api_portal;

	    $tag = get_query_var('fmc_tag'); 
	    $oauth_tag = get_query_var('oauth_tag');
	    $vow_tag = get_query_var('fmc_vow_tag');
	   

	    if ($vow_tag) {
	      $tag = $vow_tag;
	      $type = 'fmc_vow_tag';
	    }
	    else {
	      //default
	      $type = null;
	    }

	    
	    $api = ($type == 'fmc_vow_tag') ? $fmc_api_portal : $fmc_api;
	    // parse passed parameters for browsing capability
	    $flexmlsConnectPageCore = new flexmlsConnectPageCore($api);
	    list($params, $cleaned_raw_criteria, $context) = $flexmlsConnectPageCore->parse_search_parameters_into_api_request($search_arr);


	    $flexmlsAPI_Core = new flexmlsAPI_Core($api);

	    $result = $flexmlsAPI_Core->GetListings($params);

	    $listing = (count($result) > 0) ? $result[0] : null;

	    if(!empty($search_arr['price_reduced_data']) && !empty($listing) && $listing != null)
	    {
	    	$price_reduced_ids = 'available';
	    }

	    if($search_arr['MajorChangeType'] == 'Price Reduced' && $type == 'Price Reduced' && !empty($listing) && $listing != null)
	    {
	    	$price_reduced_data = array();
	    	foreach ($result as $record) {

	    		array_push($price_reduced_data, $record['Id']);
	    	}
	    	return $price_reduced_data;
	    	exit;
	    }

	    else if(!empty($listing) && $listing != null && $search_arr['MajorChangeType'] != 'Price Reduced') {
		    foreach ($result as $record) {
		      //echo "<pre>"; print_r($record); die;
		      $result_count++;
		      // Establish some variables
		      $listing_address = flexmlsConnect::format_listing_street_address($record);
		      $first_line_address = htmlspecialchars($listing_address[0]);
		      $second_line_address = htmlspecialchars($listing_address[1]);
		      $one_line_address = htmlspecialchars($listing_address[2]);
		      $link_to_details_criteria = $this->search_criteria;

		      $this_result_overall_index = ($this->page_size * ($this->current_page - 1)) + $result_count;
		      $my_list = $flexmlsAPI_Core->GetListing($record['Id']);
		      $status = $my_list['StandardFields']['StandardStatus'];

		      $pid = $record['Id'];
		   	   $photos = $fmc_api->GetListingPhotos($pid);
			  $status = $record['StandardStatus'];
		      $sf =& $record['StandardFields'];


		      // figure out if there's a previous listing
		      $link_to_details_criteria['p'] = ($this_result_overall_index != 1) ? 'y' : 'n';

		      //$link_to_details_criteria['m'] = $sf['MlsId'];

		      // figure out if there's a next listing possible
		      $link_to_details_criteria['n'] = ( $this_result_overall_index < $this->total_rows ) ? 'y' : 'n';

		      $link_to_details = flexmlsConnect::make_nice_address_url($record, $link_to_details_criteria, $this->type);

		      $rand = mt_rand();

			     // Image
			      // if ( count($sf['Photos']) >= 1 ) {		    		  
			      //   //Find primary photo and assign it to $main_photo_url variable.
		    		 // 	$count_photos = count($sf['Photos']);

				     //    $i = 0;
				     //    while($i < $count_photos){
				     //      if($sf['Photos'][$i]['Primary'] === TRUE){
				     //        $main_photo_url =     $sf['Photos'][$i]['Uri300'];
				     //        $main_photo_urilarge = $sf['Photos'][$i]['UriLarge'];
				     //        $caption = htmlspecialchars($sf['Photos'][$i]['Caption'], ENT_QUOTES);
				     //        break;
				     //      }
				     //      $i++;
				     //    }		    		 
			         	
			      // } else {
			      // 	$count_photos = count($photos);
			      //   $i = 0;
			      //   while($i < $count_photos){
			      //     if($photos[$i]['Primary'] === TRUE){
			      //       $main_photo_url =     $photos[$i]['Uri300'];
			      //       $main_photo_urilarge = $photos[$i]['UriLarge'];
			      //       $caption = htmlspecialchars($photos[$i]['Caption'], ENT_QUOTES);
			      //       break;
			      //     }
			      //     $i++;
			      //   }

			      //   if(empty($main_photo_url))
			      //   {
			      //     $main_photo_url = "{$fmc_plugin_url}/assets/images/nophoto.gif";
			      //     $main_photo_urilarge = "{$fmc_plugin_url}/assets/images/nophoto.gif";
			      //     $caption = "";
			      //   }
			      // }

		      if ( count($sf['Photos']) >= 1 ) {
		        //Find primary photo and assign it to $main_photo_url variable.
		         $count_photos = count($sf['Photos']);

		        $i = 0;
		        while($i < $count_photos){
		          if($sf['Photos'][$i]['Primary'] === TRUE){
		            $main_photo_url =     $sf['Photos'][$i]['Uri300'];
		            $main_photo_urilarge = $sf['Photos'][$i]['UriLarge'];
		            $caption = htmlspecialchars($sf['Photos'][$i]['Caption'], ENT_QUOTES);
		            break;
		          }
		          $i++;
		        }

		      } else {
		        // $photos = $fmc_api->GetListingPhotos($record['Id']);
		        // $count_photos = count($photos);
		        // $i = 0;
		        // while($i < $count_photos){
		        //   if($photos[$i]['Primary'] === TRUE){
		        //     $main_photo_url =     $photos[$i]['Uri300'];
		        //     $main_photo_urilarge = $photos[$i]['UriLarge'];
		        //     $caption = htmlspecialchars($photos[$i]['Caption'], ENT_QUOTES);
		        //     break;
		        //   }
		        //   $i++;
		        // }
		        
		        if(empty($main_photo_url))
		        {
		          $main_photo_url = "{$fmc_plugin_url}/assets/images/nophoto.gif";
		          $main_photo_urilarge = "{$fmc_plugin_url}/assets/images/nophoto.gif";
		          $caption = "";
		        }
		        
		      }

	   

      			$body.= '<table class="m_6340947293587095594m_5820570660198366816MsoNormalTable" style="border-top:solid #e0e0e0 1.0pt;border-left:none;border-bottom:none;border-right:none" cellspacing="0" cellpadding="0" border="1">
				            <tbody>
				            <tr style="height:88.5pt">
				            <td style="border:none;padding:1.25pt 6.75pt 6.75pt 6.75pt;height:88.5pt;overflow:hidden" valign="middle" width="150">
				            <div>
				            <p class="MsoNormal">
				            <a href="'.$link_to_details.'" target="_blank" rel="noreferrer">
				            <span style="color: #5C8CD7; text-decoration: none">
				            <img id="" src="'.$main_photo_url.'" alt="Listing Update" border="0"  width="180" height="135">
				            </span>
				            </a><!-- o ignored -->
				            </p>
				            </div>
				            </td>
				            <td valign="middle" style="border: none; padding: 0; overflow: hidden">
				            <div><p class="MsoNormal" style="padding:0px !important; margin:0px !important;">';
				            if($search_arr['MajorChangeType'] == 'Price Change' && $price_reduced_ids == "available" && in_array($record['Id'], $search_arr['price_reduced_data'])){
							$body.='<img id="" alt="Listing Arrow" border="0" width="13" height="13" src="'.get_template_directory_uri().'/images/red-arr.png">';
				            }else if($search_arr['MajorChangeType'] == 'Price Change'){
				            $body.='<img id="" src="'.get_template_directory_uri().'/images/green-arr.png" alt="Listing Arrow" border="0" width="13" height="13">';
				            }
				            if(flexmlsConnect::is_not_blank_or_restricted($sf['ListPrice'])){
				            	$price = '$' . flexmlsConnect::gentle_price_rounding($sf['ListPrice']);
				            } 
				            $body.='
				            <span style="font-size: 11pt">
				            <a href="'.$link_to_details.'" target="_blank" rel="noreferrer">
				            <span style="color: black; text-decoration: none">'.$price.'</span>
				            </a> <!-- o ignored -->
				            </span>
				            </p>
				            </div>
				            <div style="margin-top: 2.25pt">
				            <p class="MsoNormal" style="padding:0px !important; margin:0px !important;">
				            <span style="font-size: 9pt; color: #5C8CD7">
				            <a href="'.$link_to_details.'" target="_blank" rel="noreferrer">
				            <span style="color: #5C8CD7; text-decoration: none">'.$first_line_address.'</span>
				            </a> <!-- o ignored -->
				            </span>
				            </p>
				            </div>
				            <div style="margin-top: 1pt">
				            <p class="MsoNormal" style="padding:0px !important; margin:0px !important;">
				            <span style="font-size: 9.0pt; color: darkgray">'. $second_line_address .'<!-- o ignored --></span>
				            </p>
				            </div>
				            <div style="margin-top: 1pt">
				            <p class="MsoNormal" style="padding:0px !important; margin:0px !important;">';
				            if($my_list['StandardFields']['StandardStatus'] == 'Active')
				            {
				            	$body.='<span style="font-size: 9.0pt; color: #6DB267">'.$my_list['StandardFields']['StandardStatus'].'</span>';
				            }else if($my_list['StandardFields']['StandardStatus'] == 'Pending')
				            {
				            	$body.='<span style="font-size: 9.0pt; color: #FFAD6F">'.$my_list['StandardFields']['StandardStatus'].'</span>';
				            }else if($my_list['StandardFields']['StandardStatus'] == 'Closed')
				            {
				            	$body.='<span style="font-size: 9.0pt; color: #576FFF">'.$my_list['StandardFields']['StandardStatus'].'</span>';
				            }
				           
				              
				            $body.='<br><span style="font-size: 9.0pt; color: #286090">'.$search_arr['MajorChangeType'].'</span><span style="font-size: 9.0pt; color: darkgray"> <!-- o ignored --></span>
				            </p>
				            </div>
				            </td>
				            </tr>
				            </tbody>
				            </table>';
				}
				$body.= '<div style="margin-top: 37.5pt">
			            <p class="MsoNormal" style="text-align: center" align="center">
			            <span style="font-family: &quot;Helvetica&quot;,sans-serif">
			            <a href="'.$listing_url.'" target="_blank" rel="noreferrer">
			            <b>
			            <span style="font-size: 10.5pt;padding:0px 5px; color: white; text-transform: uppercase; background: #4A76CD; text-decoration: none">View All Listings</span></b>
			            </a><!-- o ignored -->
			            </span>
			            </p>
			            </div>
			            
			            <div style="margin-top: 37.5pt">
			            <div style="margin-top: 18.75pt; margin-bottom: 18.75pt">
			            <div class="MsoNormal" style="text-align: center" align="center">
			            <span style="font-family: &quot;Helvetica&quot;,sans-serif">
			            <hr style="color: #D9D9D9" width="100%" size="1" align="center">
			            </span>
			            </div>
			            </div>
			            <div>
			            <div style="margin-right: 15.0pt; float: left">
			            </div>

			            </div>
			            </div>';

			return $body; 
		}
		else{
			return 0; 
		}
	}

}
$FlexMLS_IDX = new FlexMLS_IDX();

register_activation_hook( __FILE__, array( 'FlexMLS_IDX', 'plugin_activate' ) );
register_deactivation_hook( __FILE__, array( 'FlexMLS_IDX', 'plugin_deactivate' ) );
register_uninstall_hook( __FILE__, array( 'FlexMLS_IDX', 'plugin_uninstall' ));

/*
* Define widget information
*/

global $fmc_widgets;
$fmc_widgets = array(
    'fmcMarketStats' => array(
        'component' => 'market-statistics.php',
        'title' => "FlexMLS&reg;: Market Statistics",
        'description' => "Show market statistics on your blog",
        'requires_key' => true,
        'shortcode' => 'market_stats',
        'max_cache_time' => 0,
        'widget' => true
        ),
    'fmcPhotos' => array(
        'component' => 'photos.php',
        'title' => "FlexMLS&reg;: IDX Slideshow",
        'description' => "Show photos of selected listings",
        'requires_key' => true,
        'shortcode' => 'idx_slideshow',
        'max_cache_time' => 0,
        'widget' => true
        ),
    'fmcSearch' => array(
        'component' => 'search.php',
        'title' => "FlexMLS&reg;: IDX Search",
        'description' => "Allow users to search for listings",
        'requires_key' => true,
        'shortcode' => 'idx_search',
        'max_cache_time' => 0,
        'widget' => true
        ),
    'fmcLocationLinks' => array(
        'component' => 'location-links.php',
        'title' => "FlexMLS&reg;: 1-Click Location Searches",
        'description' => "Allow users to view listings from a custom search narrowed to a specific area",
        'requires_key' => true,
        'shortcode' => 'idx_location_links',
        'max_cache_time' => 0,
        'widget' => true
        ),
    'fmcIDXLinksWidget' => array(
        'component' => 'idx-links.php',
        'title' => "FlexMLS&reg;: 1-Click Custom Searches",
        'description' => "Share popular searches with your users",
        'requires_key' => true,
        'shortcode' => 'idx_custom_links',
        'max_cache_time' => 0,
        'widget' => true
        ),
    /*
    'fmcLeadGen' => array(
        'component' => 'lead-generation.php',
        'title' => "FlexMLS&reg;: Contact Me Form",
        'description' => "Allow users to share information with you",
        'requires_key' => true,
        'shortcode' => 'lead_generation',
        'max_cache_time' => 0,
        'widget' => true
        ),
       */
    /*
    'fmcNeighborhoods' => array(
        'component' => 'neighborhoods.php',
        'title' => "FlexMLS&reg;: Neighborhood Page",
        'description' => "Create a neighborhood page from a template",
        'requires_key' => true,
        'shortcode' => 'neighborhood_page-hold',
        'max_cache_time' => 0,
        'widget' => false
        ),
    */
    'fmcListingDetails' => array(
        'component' => 'listing-details.php',
        'title' => "FlexMLS&reg;: IDX Listing Details",
        'description' => "Insert listing details into a page or post",
        'requires_key' => true,
        'shortcode' => 'idx_listing_details',
        'max_cache_time' => 0,
        'widget' => false
        ),
    'fmcSearchResults' => array(
        'component' => 'search-results.php',
        'title' => "FlexMLS&reg;: IDX Listing Summary",
        'description' => "Insert a summary of listings into a page or post",
        'requires_key' => true,
        'shortcode' => 'idx_listing_summary',
        'max_cache_time' => 0,
        'widget' => false
        ),
    /*The agent search widget is only available to Offices and Mls's (not of usertype member)*/
    'fmcAgents' => array(
        'component' => 'fmc-agents.php',
        'title' => "FlexMLS&reg;: IDX Agent List",
        'description' => "Insert agent information into a page or post",
        'requires_key' => true,
        'shortcode' => 'idx_agent_search',
        'max_cache_time' => 0,
        'widget' => false
        ),
    'fmcAccount' => array(
        'component' => 'my-account.php',
        'title' => "FlexMLS&reg;: Log in",
        'description' => "Portal Login/Registration",
        'requires_key' => true,
        'shortcode' => 'idx_portal_login',
        'max_cache_time' => 0,
        'widget' => true
        ),
    );


$fmc_special_page_caught = array(
    'type' => null
);




$options = get_option('fmc_settings');
$api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
$api_secret = isset( $options['api_secret'] ) ? $options['api_secret'] : '';
$fmc_api = new flexmlsConnectUser($api_key,$api_secret);

if($options && array_key_exists('oauth_key', $options) && array_key_exists('oauth_secret', $options)) {
  $fmc_api_portal = new flexmlsConnectPortalUser($options['oauth_key'], $options['oauth_secret']);
}

$api_ini_file = $fmc_plugin_dir . '/lib/api.ini';

$fmc_location_search_url = FMC_LOCATION_SEARCH_URL;

if (file_exists($api_ini_file)) {
  $local_settings = parse_ini_file($api_ini_file);
  if (array_key_exists('api_base', $local_settings)) {
    $fmc_api->api_base = trim($local_settings['api_base']);
    $fmc_api_portal->api_base = trim($local_settings['api_base']);
  }
  if (array_key_exists('location_search_url', $local_settings)) {
    $fmc_location_search_url = trim($local_settings['location_search_url']);
  }
}


$fmc_instance_cache = array();


/*
* register the init functions with the appropriate WP hooks
*/
//add_action('widgets_init', array('flexmlsConnect', 'widget_init') );

$fmc_admin = new flexmlsConnectSettings;

add_action('admin_menu', array('flexmlsConnect', 'admin_menus_init') );
add_action('init', array('flexmlsConnect', 'initial_init') );


add_filter('query_vars', array('flexmlsConnectPage', 'query_vars_init') );
add_action('init', array('flexmlsConnectPage','do_rewrite'));
add_action('wp', array('flexmlsConnectPage', 'catch_special_request') );
add_action('wp', array('flexmlsConnect', 'wp_init') );

$fmc_search_results_loaded = false;



add_action("wp_ajax_save_my_listing", "save_my_listing");
add_action("wp_ajax_nopriv_save_my_listing", "save_my_listing");

function save_my_listing() {	
	global $wpdb;
	$listing_data = json_encode(array('ListingStatus'=>$_REQUEST['ListingStatus'],'ListingPrice'=>$_REQUEST['ListingPrice'],'ListingId'=>$_REQUEST['ListingId'],'ListingTag'=>$_REQUEST['ListingTag'],));
    $status = $wpdb->insert( "saved_listing", array( "listing_data" => $listing_data, "user_id" => $_REQUEST['UserId'],"date" => date('Y-m-d h:i:s')), array( '%s', '%d', '%s') );
	if($status){
		echo 1;
    }else{
    	echo 0;
    }
}






add_action("wp_ajax_saved_listing_delete", "saved_listing_delete");
add_action("wp_ajax_nopriv_saved_listing_delete", "saved_listing_delete");

function saved_listing_delete() {
	global $wpdb; 
    $id = filter_var($_REQUEST["id"], FILTER_SANITIZE_NUMBER_FLOAT);
    $status = $wpdb->delete( 'saved_listing', array( 'id' => $_REQUEST['id'] ), array( '%d' ) );
    if(!empty($status)){
      echo json_encode(array("status"=>"ok"));  die;
    }else{
       echo 0; die;
    }
}



add_action("wp_ajax_fmcListing_schedule_showing", "fmcListing_schedule_showing");
add_action("wp_ajax_nopriv_fmcListing_schedule_showing", "fmcListing_schedule_showing");

function fmcListing_schedule_showing() {
	global $fmc_api;
    $api_my_account = $fmc_api->GetMyAccount();

    $send_email=($api_my_account['Emails'][0]['Address']);
    $mytest = flexmlsConnect::wp_input_get_post('flexmls_connect__important');

    //This is our bot blocker... if it is set, then pretend like everything went okay
    if (!empty($_POST['flexmls_connect__important'])){
      exit("SUCCESS");
    }

    $action = $api_my_account['UserType'];
   
        if (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent'))){
            $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent');
        }
        elseif (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_office'))){
            $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_office');
        }
        else{
            $action = "SendToMls";
        }
   

    try{
     
        $headers = 'From: '. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . "\r\n";
       $message = flexmlsConnect::wp_input_get_post('flexmls_connect__message') . "\r\n\r\n". flexmlsConnect::wp_input_get_post('flexmls_connect__from_name').' <'. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . ">\r\n";    
       $message .= "<br>Sender's Name : " .$_POST['flexmls_connect__from_name'];
       $message .= "<br>Sender's Email : " .$_POST['flexmls_connect__from'];
       $message .= "<br>Sender's Subject : " .$_POST['flexmls_connect__subject'];  
       
        //wp_mail($send_email, flexmlsConnect::wp_input_get_post('flexmls_connect__subject'), $message, $headers);
       //$send_email = "damini.jaiswal@techinfini.com";
       //include (plugins_url("sparkmls/lib/Mandrill.php"));

       require_once("lib/Mandrill.php"); 
        $mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
        $sen_name = "Alaska Dream Makers Info";
          $sen_email = "info@alaskaland.info";
          $rec_name = "Larry";
          $rec_email = $send_email;
          $email_type = "to";
          $email_sub = "Schedule Showing";
          $msg_type = "html";         
          $box_msg = "Schedule Showing Details : .<br />";
          $box_msg .= $message;
          $message = array();
          $to = array();
          $to[] = array(
          'email' => $rec_email,
          'name' => $rec_name,
          'type' => $email_type
          );
          $message['subject'] = flexmlsConnect::wp_input_get_post('flexmls_connect__subject');
          $message[$msg_type] = $box_msg;
          $message['from_email'] = $sen_email;
          $message['from_name'] = $sen_name;
          $message['to'] = $to;
          
          $email_result = $mandrill->messages->send($message);
          if($email_result[0]['status'] == 'sent')
          {
          	die('SUCCESS');
          }else{
          	die('NOT SENT');
          }
        
    
    } catch(Exception $e) {
      die('There was an error sending the e-mail: ' .$e->getMessage());
    }
}


add_action("wp_ajax_fmcListing_contact", "fmcListing_contact");
add_action("wp_ajax_nopriv_fmcListing_contact", "fmcListing_contact");

function fmcListing_contact() {
	global $fmc_api; 
        $api_my_account = $fmc_api->GetMyAccount();

        $send_email=($api_my_account['Emails'][0]['Address']);
        $mytest = flexmlsConnect::wp_input_get_post('flexmls_connect__important');

        //This is our bot blocker... if it is set, then pretend like everything went okay
        if (!empty($_POST['flexmls_connect__important'])){
          //exit("SUCCESS");
        }

        $action = $api_my_account['UserType'];
        
            if (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent'))){
                $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_agent');
            }
            elseif (flexmlsConnect::is_not_blank_or_restricted(flexmlsConnect::wp_input_get_post('flexmls_connect__to_office'))){
                $send_email=flexmlsConnect::wp_input_get_post('flexmls_connect__to_office');
            }
            else{
                $action = "SendToMls";
            }

        try{         

         
            $headers = 'From: '. flexmlsConnect::wp_input_get_post('flexmls_connect__from') . "\r\n";
            $message = flexmlsConnect::wp_input_get_post('flexmls_connect__message') . "\r\n\r\n". flexmlsConnect::wp_input_get_post('flexmls_connect__from_name');
            $message .= "<br>Sender's Name : " .$_POST['flexmls_connect__from_name'];
            $message .= "<br>Sender's Email : " .$_POST['flexmls_connect__from'];
            $message .= "<br>Sender's Subject : " .$_POST['flexmls_connect__subject'];
            //wp_mail($send_email, flexmlsConnect::wp_input_get_post('flexmls_connect__subject'), $message, $headers);

            //$send_email = "damini.jaiswal@techinfini.com";

            //include (plugins_url("sparkmls/lib/Mandrill.php"));
            require_once("lib/Mandrill.php");
            $mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
            $sen_name = "Alaska Dream Makers Info";
              $sen_email = "info@alaskaland.info";
              $rec_name = "Larry";
              $rec_email = $send_email;
              $email_type = "to";
              $email_sub = "Ask Question";
              $msg_type = "html";         
              $box_msg = "Ask Question Details : .<br />";
              $box_msg .= $message;
              $message = array();
              $to = array();
              $to[] = array(
              'email' => $rec_email,
              'name' => $rec_name,
              'type' => $email_type
              );
              $message['subject'] = flexmlsConnect::wp_input_get_post('flexmls_connect__subject');
              $message[$msg_type] = $box_msg;
              $message['from_email'] = $sen_email;
              $message['from_name'] = $sen_name;
              $message['to'] = $to;
              
              $email_result = $mandrill->messages->send($message);
              if($email_result[0]['status'] == 'sent')
              {
              	die("SUCCESS");
              }else{
	            $subject = flexmlsConnect::wp_input_get_post('flexmls_connect__subject');

	            $body =  "This message has been auto-generated by your wordpress site.\n\n The following person has attempted to contact you:\n";
	            $body .= "This message was sent from this page: " . flexmlsConnect::wp_input_get_post('flexmls_connect__page_lead') . "\n";
	            $body .= "Name: " . flexmlsConnect::wp_input_get_post('flexmls_connect__from_name') . "\n";
	            $body .= "Email: " . flexmlsConnect::wp_input_get_post('flexmls_connect__from'). "\n\n";
	            $body .= "Message:\n";

	                    $body .= flexmlsConnect::wp_input_get_post('flexmls_connect__message');
	            $Contact = array();
	            $Contact['DisplayName'] = flexmlsConnect::wp_input_get_post('flexmls_connect__from_name');
	            $Contact['PrimaryEmail'] = flexmlsConnect::wp_input_get_post('flexmls_connect__from');

	                flexmlsConnect::add_contact($Contact);

	                if (flexmlsConnect::message_me($subject,
	                                            $body,
	                                            flexmlsConnect::wp_input_get_post('flexmls_connect__from')))
	                {
	                    //die("SUCCESS");
	                }
	                else
	                {
	                    throw new Exception("An Error occured while attempting to contact the site.");
	                }
      		}
    }catch(Exception $e) {
      		die('There was an error sending the e-mail: ' .$e->getMessage());
    }
    return;
}

add_action("wp_ajax_city_autosuggestions", "city_autosuggestions");
add_action("wp_ajax_nopriv_city_autosuggestions", "city_autosuggestions");

function city_autosuggestions() {
	global $fmc_api;

    // pull StandardFields from the API to verify searchability prior to searching
    $city = $fmc_api->GetStandardField('City'); 
    $arr = array();  
    $i = 0; 
    foreach ($city[0]['City']['FieldList'] as  $key=>$value) {
	      $name = strtoupper($value['Name']);
	      $val = strlen(strtoupper($_REQUEST['val']));
	      if (substr($name, 0, $val) === strtoupper($_REQUEST['val'])) {  // Yoshi version
	          //$arr[$i] = $value['Value'];
	          array_push($arr, $value['Value']);
	          $i++;
	      }      
	                    
	  }
	sort($arr);
	echo json_encode($arr);
	die();
}


add_action("wp_ajax_savedsearch_emailchange", "savedsearch_emailchange");
add_action("wp_ajax_nopriv_savedsearch_emailchange", "savedsearch_emailchange");

function savedsearch_emailchange() {	
	if(!empty($_REQUEST['id']))
	{
		global $wpdb; 
		$sql1 = "Update `saved_search` SET `email_management` = '".$_REQUEST['checked']."' where `id` = '".$_REQUEST['id']."'";
		$wpdb->query($sql1);
		echo json_encode(array("status"=>"ok")); die;
	}else{
		die;
	}
}

add_action("wp_ajax_savedsearch_emailfrequency", "savedsearch_emailfrequency");
add_action("wp_ajax_nopriv_savedsearch_emailfrequency", "savedsearch_emailfrequency");

function savedsearch_emailfrequency() {	
	if(!empty($_REQUEST['id']))
	{
		global $wpdb; 
		if($_REQUEST['updateopt'] == 'emailon_pricechange')
		{
			$sql1 = "Update `saved_search` SET `price_change` = '".$_REQUEST['checked']."' where `id` = '".$_REQUEST['id']."'";
		}else if($_REQUEST['updateopt'] == 'emailon_statuschange')
		{
			$sql1 = "Update `saved_search` SET `status_change` = '".$_REQUEST['checked']."' where `id` = '".$_REQUEST['id']."'";
		}else if($_REQUEST['updateopt'] == 'emailon_newlisting')
		{
			$sql1 = "Update `saved_search` SET `new_listing` = '".$_REQUEST['checked']."' where `id` = '".$_REQUEST['id']."'";
		}
		
		$wpdb->query($sql1);
		echo json_encode(array("status"=>"ok")); die;
	}else{
		die;
	}
}  

add_action("wp_ajax_savedsearch_delete", "savedsearch_delete");
add_action("wp_ajax_nopriv_savedsearch_delete", "savedsearch_delete");

function savedsearch_delete() {	
	//print_r($_REQUEST); die; 
	if(!empty($_REQUEST['id']))
	{
		global $wpdb; 
		$wpdb->delete( 'saved_search', array( 'id' => $_REQUEST['id'] ), array( '%d' ) );
		echo json_encode(array("status"=>"ok")); die;
	}else{
		die;
	}	
}

add_action("wp_ajax_savedsearch_status_update", "savedsearch_status_update");
add_action("wp_ajax_nopriv_savedsearch_status_update", "savedsearch_status_update");

function savedsearch_status_update() {	
	if(!empty($_REQUEST['id']))
	{
		global $wpdb; 
		$sql1 = "Update `saved_search` SET `active` = '".$_REQUEST['checked']."' where `id` = '".$_REQUEST['id']."'";
		$wpdb->query($sql1);
		echo json_encode(array("status"=>"ok")); die;
	}else{
		die;
	}
} 


add_action("wp_ajax_add_saved_search", "add_saved_search");
add_action("wp_ajax_nopriv_add_saved_search", "add_saved_search");

function add_saved_search() {
	date_default_timezone_set('America/Anchorage');
    global $wpdb;
    $current_user = wp_get_current_user();
    $url = parse_str(str_replace("#", "", $_GET["search"]), $search);
    $search_arr = array();
    if(!empty($current_user->ID))
    {
    	//print_r(expression)
    	if(!empty($search))
	    {
	      if(!empty($search['baths']) && strpos($_GET['current_urls'], 'baths') !== false)
	      {
	        $search_arr['Baths'] = $search['baths'];
	        
	      }

	      if(!empty($search['beds']) && strpos($_GET['current_urls'], 'beds') !== false)
	      {
	        $search_arr['Beds'] = $search['beds'];
	        
	      } 

	      if(!empty($search['min']) && strpos($_GET['current_urls'], 'min') !== false)
	      {
	        $search_arr['Price From'] = $search['min'];
	      }

	      if(!empty($search['max']) && strpos($_GET['current_urls'], 'max') !== false)
	      {
	        $search_arr['Price To'] = $search['max'];
	      }

	      if(!empty($search['PropertyType']) && strpos($_GET['current_urls'], 'PropertyType') !== false)
	      {
	        $search_arr['Property Type'] = $search['PropertyType'];
	      }

	      if(!empty($search['MinBaths']) && strpos($_GET['current_urls'], 'MinBaths') !== false)
	      {
	        $search_arr['Min Baths'] = $search['MinBaths'];
	      }

	      if(!empty($search['MaxBaths']) && strpos($_GET['current_urls'], 'MaxBaths') !== false)
	      {
	        $search_arr['Max Baths'] = $search['MaxBaths'];
	      }

	      if(!empty($search['MinYear']) && strpos($_GET['current_urls'], 'MinYear') !== false)
	      {
	        $search_arr['Min Year'] = $search['MinYear'];
	      }

	      if(!empty($search['MaxYear']) && strpos($_GET['current_urls'], 'MaxYear') !== false)
	      {
	        $search_arr['Max Year'] = $search['MaxYear'];
	      }

	      if(!empty($search['MinSqFt']) && strpos($_GET['current_urls'], 'MinSqFt') !== false)
	      {
	        $search_arr['Min SqFt'] = $search['MinSqFt'];
	      }

	      if(!empty($search['MaxSqFt']) && strpos($_GET['current_urls'], 'MaxSqFt') !== false)
	      {
	        $search_arr['Max SqFt'] = $search['MaxSqFt'];
	      }

	      if(!empty($search['MinPrice']) && strpos($_GET['current_urls'], 'MinPrice') !== false)
	      {
	        $search_arr['Min Price'] = $search['MinPrice'];
	      }

	      if(!empty($search['MaxPrice']) && strpos($_GET['current_urls'], 'MaxPrice') !== false)
	      {
	        $search_arr['Max Price'] = $search['MaxPrice'];
	      }

	      if(!empty($search['OrderBy']) && strpos($_GET['current_urls'], 'OrderBy') !== false)
	      {
	        if (strpos($search['OrderBy'], '$') == false) {
	            $search_arr['Order By'] = $search['OrderBy'];
	        }        
	      }

	      if(!empty($search['StandardStatus']))
	      {
	        $search_arr['Standard Status'] = $search['StandardStatus'];
	      }else{
	        //$search_arr['Standard Status'] = "Active";
	      }

	      if(!empty($search['GarageSpaces']) && strpos($_GET['current_urls'], 'GarageSpaces') !== false)
	      {
	        $search_arr['Garage Spaces'] = $search['GarageSpaces'];
	      }

	      if(!empty($search['SavedSearch']) && strpos($_GET['current_urls'], 'SavedSearch') !== false)
	      {
	        $search_arr['Saved Search'] = $search['SavedSearch'];
	      }

	      if(!empty($search['MLSAreaMinor']) && strpos($_GET['current_urls'], 'MLSAreaMinor') !== false)
	      {
	        $search_arr['Area'] = $search['MLSAreaMinor'];
	      }   

	      if(!empty($search['current_url']) && strpos($_GET['current_urls'], 'current_url') !== false)
	      {
	        $search_arr['url'] = $search['current_url'];
	      }   

	      if(!empty($search['City']) && strpos($_GET['current_urls'], 'City') !== false)
	      {
	        $search_arr['City'] = $search['City'];
	      } 

		  $name = filter_var($_GET["name"], FILTER_SANITIZE_SPECIAL_CHARS);

		  if(!empty($name))
		  {
			$search_arr['search_name'] = $name;
		  }			 

	    }  	

    	
    	if(!empty($_GET['current_urls']))
		{
			$search_arr['current_urls'] = $_GET['current_urls'];
		} 

		$search_data = json_encode($search_arr);		

		$saved_search = array();
		$saved_search_type = array();

		$saved_search['search_data'] = $search_data;
		array_push($saved_search_type, '%s');

		$saved_search['user_id'] = $current_user->ID;
		array_push($saved_search_type, '%d');

		if(!empty($_GET['emailon_pricechange']))
		{
			$saved_search['price_change'] = $_GET['emailon_pricechange'];
			array_push($saved_search_type, '%d');
		}else{
			$saved_search['price_change'] = 0;
			array_push($saved_search_type, '%d');
		}
		array_push($saved_search_type, '%d');

		if(!empty($_GET['emailon_statuschange']))
		{
			$saved_search['status_change'] = $_GET['emailon_statuschange'];
			array_push($saved_search_type, '%d');
		}else{
			$saved_search['status_change'] = 0;
			array_push($saved_search_type, '%d');
		}
		array_push($saved_search_type, '%d');

		if(!empty($_GET['emailon_newlisting']))
		{
			$saved_search['new_listing'] = $_GET['emailon_newlisting'];
			array_push($saved_search_type, '%d');
		}else{
			$saved_search['new_listing'] = 0;
			array_push($saved_search_type, '%d');
		}	

		

		if(!empty($_GET['frequency_management']))
		{
			$saved_search['email_management'] = $_GET['frequency_management'];
			array_push($saved_search_type, '%d');
		}else{
			$saved_search['email_management'] = 0;
			array_push($saved_search_type, '%d');
		}
		
		$saved_search['active'] = 1;
		array_push($saved_search_type, '%d');

		$saved_search['date'] = date('Y-m-d h:i:s');
		array_push($saved_search_type, '%s');

		//print_r($saved_search);

	    $status = $wpdb->insert( "saved_search", $saved_search);
		if($status){
			echo 1;
	    }else{
	    	echo 0;
	    }
		die();
    }	
}


add_shortcode( 'saved_listing', 'savedListing' );

function savedListing(){
    global $wpdb;
    $check_data = '';
        $current_user = wp_get_current_user();
        if ($current_user->ID > 0) {  

	        $sql = "SELECT * FROM `saved_listing` WHERE `user_id` = '{$current_user->ID}' order by `id` desc";
			$results = $wpdb->get_results($sql);
			
				$FlexMLS_IDX = new FlexMLS_IDX();
				$mob_html =  "";

				//die;
				echo '<table id="mytable" class="table table-bordred table-striped">
				<thead>
				<tr>
				<th>Property Images</th>
				<th>Details</th>
				<th colspan="2">Action</th>
				</tr>
				</thead>
				<tbody>';

				$mob_view_html .=  '<div class="saved_listing_accordian" style="display:none;  float: left;
    width: 100%;"><div class="panel-group" id="accordion_savedlisting">';
				
				if(!empty($results))
				{ 
					foreach( $results as $result ) {			    	
						$listing_data =  json_decode($result->listing_data);						
				       $get_data = $FlexMLS_IDX->get_detail($listing_data->ListingTag,$result->id,$result->user_id,$result->date,'return frontend view',$result->auto_email_status);
				       echo $get_data['desk_html'];
				      // echo "<pre>"; print_r($get_data); die;
				       $mob_view_html .= $get_data['mob_html'];
					}
				 }			

			    
			    echo '</tbody>
				</table>';
				$mob_view_html .=  '</div></div>';
				echo $mob_view_html;
				echo '<div class="modal fade" id="delete" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
                <h4 class="modal-title custom_align" id="Heading">Delete this entry</h4>
            </div>
            <div class="modal-body">

                <div class="alert alert-danger"><span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete this Record?</div>

            </div>
            <div class="modal-footer ">

                <button type="button" class="btn btn-success mysearch" style="margin-bottom: 0"><span class="glyphicon glyphicon-ok-sign"></span> Yes</button>
                    <button type="button" class="btn btn-default mysearch" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> No</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
	</div>
	<!-- /.modal-dialog -->
	<!-- Modal For Email status change  -->
	<div class="modal fade" id="showmodalonsendemailchange">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title response-header"></h4>
	      </div>
	      <div class="modal-body response-body">
	         
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	 
	<!-- end Modal For Email status change  --><!-- Delete Search confirmation  -->
	<div class="modal fade" id="deleteconfirmation">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title" style="margin:0;">Confirm Delete</h4>
	      </div>
	      <div class="modal-body">
	         <p>You are about to delete your saved search.</p>
	         <p>Do you want to proceed?</p>
	      </div>
	      <div class="modal-footer">
	                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	                     <button type="button" class="btn btn-danger searchdelete-listing" data-id="">Delete</button>
	                </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->'; 
                   
        } else {
            //user isn't logged
            //wp_redirect( home_url() );
            wp_redirect( home_url().'/login/' ); //auth_redirect(); // use 'login' for live and 'log-in' for dev server for redirect
        }
}


add_shortcode( 'saved_search', 'savedSearch' );

function savedSearch(){
    global $wpdb;
    $check_data = '';
        $current_user = wp_get_current_user();
        if ($current_user->ID > 0) {  

	       $sql = "SELECT * FROM `saved_search` WHERE `user_id` = '{$current_user->ID}' order by `id` desc";
			$results = $wpdb->get_results($sql);
			
				$FlexMLS_IDX = new FlexMLS_IDX();
				

				//die;

				//echo [idx_slideshow link="ue1x741nzsu" horizontal="7" vertical="8" source="location" property_type="A" display="all" sort="price_low_high" additional_fields="beds,baths,sqft"]
				$mob_html = '<div class="saved_Search_accordian" style="display:none;">';
				echo '<div id="my_search_list_section" class="clearfix"><table id="mytable" class="table table-bordred table-striped saved_Search_table_data" style="margin-top:35px; float:left;">
				<thead>
				<tr><th>Name</th>
				<th>Details</th>
				<th>Notified when</th>
				<th>Email Notifications</th>
				<th>Active</th>
				<th>Delete</th>
				<th>Search</th>
				</tr>
				</thead>
				<tbody>';
				
				if(!empty($results))
				{ 
					foreach( $results as $result ) { 
						$search_data = json_decode($result->search_data);
						$mob_html .= '<div class="panel-group">
						    <div class="panel panel-default">
						      <div class="panel-heading">
						        <h4 class="panel-title">
						          <a class="accordion-toggle collapsed" data-toggle="collapse" href="#collapse'.$result->id.'">';
						          if(empty($search_data->search_name))
						          {
						          	$mob_html .= "Saved Search";
						          }else{
						          	$mob_html .= $search_data->search_name;
						          }
						          
						          $mob_html .= '</a>
						        </h4>
						      </div>
						      <div id="collapse'.$result->id.'" class="panel-collapse collapse">
						      <div class="panel-body">';
						echo '<tr id="savedsearch'.$result->id.'">
 
						<td>'.$search_data->search_name.'</td>
						<td>						
						<ul style="margin:0px;">';
						
						$mob_html .= '<div class="flexmls_connect__ld_detail_table">
						<div class="flexmls_connect__detail_header">Details</div>';				

						

						foreach ($search_data as $key=>$value) {
							if($key != "url" && $key != "search_name" && $key != "SavedSearch" )
							{
								if($key == 'Price From' || $key == 'Price To')
								{	
									$mob_html .= '<div class="flexmls_connect__ld_property_detail_row"><div class="flexmls_connect__ld_property_detail"><b>'.$key.': </b> $'.$value.'</div></div>';
									echo '<li style="word-break: break-word;"><strong>'.$key.': </strong>$'.$value.'</li>';
								}else if($key == "City"){ 
									if (is_array($search_data->City))  
									{
										$mob_html .= '<div class="flexmls_connect__ld_property_detail_row"><div class="flexmls_connect__ld_property_detail"><b>'.$key.': </b> '.implode(' , ', $search_data->City).'</div></div>';
										echo '<li style="word-break: break-word;"><strong>'.$key.': </strong>'.implode(' , ', $search_data->City).'</li>';
									}else{
										$mob_html .= '<div class="flexmls_connect__ld_property_detail_row"><div class="flexmls_connect__ld_property_detail"><b>'.$key.': </b> '.$search_data->City.'</div></div>';
										echo '<li style="word-break: break-word;"><strong>'.$key.': </strong>'.$search_data->City.'</li>';
									}							    				
									
								}else if($key != "current_urls" && $key != "current_url"){
									$mob_html .= '<div class="flexmls_connect__ld_property_detail_row"><div class="flexmls_connect__ld_property_detail"><b>'.$key.': </b> '.$value.'</div></div>';
									echo '<li style="word-break: break-word;"><strong>'.$key.': </strong>'.$value.'</li>';
								}		
							}
							
						}
						
						$mob_html .= '</div>';
						echo '</ul>
						</td>
						<td>
						<div class="lsitingnotification-outer">
						<p class="label-text-for-notification">New Listing</p>
						<p class="electric">';
						$checked = ""; $status = "No";
						if($result->new_listing == 1)
						{
							$checked = "checked='checked'";
							$status = "Yes";
						}
						$mob_html .= '<div class="flexmls_connect__ld_detail_table"><div class="flexmls_connect__detail_header">Notified when</div>';	
						$mob_html .= '<div class="lsitingnotification-outer">
						<p class="label-text-for-notification">New Listing</p>
						<p class="electric"><input id="newlisting'.$result->id.'" class="email_send_on" type="checkbox" name="emailstatus_management1'.$result->id.'" value="1" updateopt="emailon_newlisting" rel="'.$result->id.'" '.$checked.'>
						<label for="newlisting'.$result->id.'"> '.$status.' </label>
						</p>
						</div>';

						echo '<input id="newlisting'.$result->id.'" class="email_send_on" type="checkbox" name="emailstatus_management1'.$result->id.'" value="1" updateopt="emailon_newlisting" rel="'.$result->id.'" '.$checked.'>
						<label for="newlisting'.$result->id.'"> '.$status.' </label>
						</p>
						</div>
						<div class="lsitingnotification-outer">
						<p class="label-text-for-notification">Price Change</p>
						<p class="electric">';
						$checked = ""; $status = "No";
						if($result->price_change == 1)
						{
							$checked = "checked='checked'";
							$status = "Yes";
						}
						$mob_html .= '<div class="lsitingnotification-outer">
						<p class="label-text-for-notification">Price Change</p>
						<p class="electric"><input id="price'.$result->id.'" type="checkbox" class="email_send_on" name="emailstatus_management2'.$result->id.'" value="1" updateopt="emailon_pricechange" rel="'.$result->id.'" '.$checked.'>
						<label for="price'.$result->id.'">'.$status.'</label></p>
						</div>
						<div class="lsitingnotification-outer">
						<p class="label-text-for-notification">Status Change</p>
						<p class="electric">';
						echo '<input id="price'.$result->id.'" type="checkbox" class="email_send_on" name="emailstatus_management2'.$result->id.'" value="1" updateopt="emailon_pricechange" rel="'.$result->id.'" '.$checked.'>
						<label for="price'.$result->id.'">'.$status.'</label></p>
						</div>
						<div class="lsitingnotification-outer">
						<p class="label-text-for-notification">Status Change</p>
						<p class="electric">';
						$checked = ""; $status = "No";
						if($result->status_change == 1)
						{
							$checked = "checked='checked'";
							$status = "Yes";
						}
						$mob_html .= '<input id="status'.$result->id.'" type="checkbox" class="email_send_on" name="emailstatus_management3'.$result->id.'" value="1" updateopt="emailon_statuschange" rel="'.$result->id.'" '.$checked.'>
						<label for="status'.$result->id.'">'.$status.'</label>
						</p>
						</div></div>';

						echo '<input id="status'.$result->id.'" type="checkbox" class="email_send_on" name="emailstatus_management3'.$result->id.'" value="1" updateopt="emailon_statuschange" rel="'.$result->id.'" '.$checked.'>
						<label for="status'.$result->id.'">'.$status.'</label>
						</p>
						</div>
						</td>';

						$mob_html .= '<div class="flexmls_connect__ld_detail_table"><div class="flexmls_connect__detail_header">Email Notifications</div><div class="lsitingnotification-outer seclectmailtime17">
						<p class="label-text-for-notification-time">Once a day </p>
						<p class="electric">';	
						$checked = ""; $status = "No";
						if($result->email_management == 0)
						{
							$checked = "checked='checked'";
							$status = "Yes";
						}	

						$mob_html .= '<input id="onceday'.$result->id.'" rel="'.$result->id.'" class="onetime_emailchange" type="radio" name="frequency_managements'.$result->id.'" value="0" '.$checked.'><label for="onceday'.$result->id.'"> '.$status.' </label></p>						</div><div class="lsitingnotification-outer"><p class="label-text-for-notification-time">As soon as possible</p><p class="electric">';
						$checked = ""; $status = "No";
						if($result->email_management == 1)
						{
							$checked = "checked='checked'";
							$status = "Yes";
						}	

						$mob_html .= '<input id="asap'.$result->id.'" type="radio" rel="'.$result->id.'" class="onetime_emailchange" name="frequency_managements'.$result->id.'" value="1" '.$checked.'><label for="asap'.$result->id.'"> '.$status.' </label></p></div></div>';	


						echo '
						<td>
						<div class="lsitingnotification-outer seclectmailtime17">
						<p class="label-text-for-notification-time">Once a day </p>
						<p class="electric">';
						$checked = ""; $status = "No";
						if($result->email_management == 0)
						{
							$checked = "checked='checked'";
							$status = "Yes";
						}											
						
						echo '<input id="onceday'.$result->id.'" rel="'.$result->id.'" class="onetime_emailchange" type="radio" name="frequency_management'.$result->id.'" value="0" '.$checked.'><label for="onceday'.$result->id.'"> '.$status.' </label>
						</p>
						</div>
						<div class="lsitingnotification-outer">
						<p class="label-text-for-notification-time">As soon as possible</p>
						<p class="electric">';						

						$checked = ""; $status = "No";
						if($result->email_management == 1)
						{
							$checked = "checked='checked'";
							$status = "Yes";
						}									
						
						echo '
						<input id="asap'.$result->id.'" type="radio" rel="'.$result->id.'" class="onetime_emailchange" name="frequency_management'.$result->id.'" value="1" '.$checked.'><label for="asap'.$result->id.'"> '.$status.' </label>
						</p>
						</div>
						</td>';						


						$mob_html .= '<div class="flexmls_connect__ld_detail_table"><div class="flexmls_connect__detail_header">Action</div><div class="lsitingnotification-outer seclectmailtime17">';

						echo '<td>
						<div class="btn-group" data-toggle="buttons">';
						if($result->active == 1)
						{
							$mob_html .= '<div data-toggle="buttons" class="btn-group"><label class="btn btn-success active">';
							$checked = "checked";
							echo '<label class="btn btn-success active">';
							$checked = "checked";
						}else{
							$mob_html .= '<div data-toggle="buttons" class="btn-group"><label class="btn btn-success">';
							$checked = "";
							echo '<label class="btn btn-success">';
							$checked = "";
						}
			$mob_html .= '<input type="checkbox" data-id="'.$result->id.'" class="search_active" '.$checked.'>
			<span class="glyphicon glyphicon-check"></span>
			</label>
			</div>
			</div>
			</div>
			<div class="flexmls_connect__detail_header">Delete</div>
			<p data-placement="top" data-toggle="tooltip" title="" data-original-title="Delete">
			<button class="btn btn-danger btn-xs active searchdelete" data-title="Delete" data-id="'.$result->id.'">
			<span class="glyphicon glyphicon-trash"></span></button>
			</p>
			
			<div class="flexmls_connect__detail_header">Action</div>
			<a href="'.$search_data->current_urls.'">Show</a>
			';
			echo '<input type="checkbox" data-id="'.$result->id.'" class="search_active" '.$checked.'>
			<span class="glyphicon glyphicon-check"></span>
			</label>
			</div>
			</td>
			<td class="td-actions">
			<p data-placement="top" data-toggle="tooltip" title="" data-original-title="Delete">
			<button class="btn btn-danger btn-xs active searchdelete" data-title="Delete" data-id="'.$result->id.'">
			<span class="glyphicon glyphicon-trash"></span></button>
			</p>
			</td>
			<td class="td-actions">
			<a href="'.$search_data->current_urls.'">Show</a>
			</td>
			</tr>';		
			$mob_html .= '</div></div></div> </div> ';	    	
						
		}
		$mob_html .= '</div>';
	}			

			    
	echo '</tbody></table></div>'; 
	echo '<div class="modal fade" id="delete" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
                <h4 class="modal-title custom_align" id="Heading">Delete this entry</h4>
            </div>
            <div class="modal-body">

                <div class="alert alert-danger"><span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete this Record?</div>

            </div>
            <div class="modal-footer ">

                <button type="button" class="btn btn-success mysearch" style="margin-bottom: 0"><span class="glyphicon glyphicon-ok-sign"></span> Yes</button>
                    <button type="button" class="btn btn-default mysearch" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> No</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
	</div>
	<!-- /.modal-dialog -->
	<!-- Modal For Email status change  -->
	<div class="modal fade" id="showmodalonsendemailchange">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title response-header"></h4>
	      </div>
	      <div class="modal-body response-body">
	         
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	 
	<!-- end Modal For Email status change  -->

	<!-- Delete Search confirmation  -->
	<div class="modal fade" id="deleteconfirmation">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title" style="margin:0;">Confirm Delete</h4>
	      </div>
	      <div class="modal-body">
	         <p>You are about to delete your saved search.</p>
	         <p>Do you want to proceed?</p>
	      </div>
	      <div class="modal-footer">
	                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	                     <button type="button" id="for-list" class="btn btn-danger searchdelete-new" data-id="">Delete</button>
	                </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
	</div><!-- /.modal -->';	    
			
        echo $mob_html;    
    } else {
        //user isn't logged
        //wp_redirect( home_url() );
        wp_redirect( home_url().'/login/' ); //auth_redirect(); // use 'login' for live and 'log-in' for dev server for redirect  
    }
}

add_shortcode( 'filter_fields_info', 'filter_fields_info' );
function filter_fields_info()
{

	//echo "<img src='".get_template_directory_uri()."/images/green-arr.png'>";
	$FlexMLS_IDX = new FlexMLS_IDX();
	$FlexMLS_IDX->filter_fields_options();	
}

add_shortcode( 'custom_fields_info', 'custom_fields_info' );
function custom_fields_info()
{
	$FlexMLS_IDX = new FlexMLS_IDX();
	$FlexMLS_IDX->custom_fields_options();
	
}




// add_action( 'wp',  'cron_scheduler');
// function dream_add_weekly_cron_schedule( $schedules ) {
//     $schedules['minutes_10'] = array(
//         'interval' => 60,
//         'display' => 'Once 1 minutes'
//     );
//     return $schedules;
// }
// add_filter( 'cron_schedules', 'dream_add_weekly_cron_schedule' );
 
// // Schedule an action if it's not already scheduled
// function cron_scheduler() {
//     if ( ! wp_next_scheduled( 'dream_my_cron_action' ) ) {
//         wp_schedule_event( time(), 'minutes_10', 'dream_my_cron_action');
//     }
// }

 
// //Hook into that action that'll fire weekly
// add_action( 'dream_my_cron_action', 'saved_search_cron' );
//add_shortcode( 'saved_search_once_day_cron', 'saved_search_once_day_cron' );
function saved_search_once_day_cron()
{
	date_default_timezone_set('America/Anchorage');   
	global $wpdb;
	//include("lib/Mandrill.php");
	$mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
	$FlexMLS_IDX = new FlexMLS_IDX();
	$search_arr = array();

	$today_date = date('Y-m-d');
	$date_before_one_day = date('Y-m-d', strtotime("-1 days"));

	$now_time = date('H:i:s');
	$time_before_one_hour = date('H:i:s', strtotime('-1 hour'));

	$now_hour = explode(":", $now_time);
	$hour_before_one_hour = explode(":", $time_before_one_hour);	

	

	$sql = "SELECT * FROM `saved_search` WHERE `active` = 1 and `email_management` = 0";
	$results = $wpdb->get_results($sql);
	
	if(!empty($results))
	{
		foreach( $results as $result ) 
		{
			$saved_search_status = get_user_meta( $result->user_id, 'saved_search_status', true );
			if($saved_search_status == 1)
			{ 
				$search_arr = array();			
					
				$search_arr['MinMajorChangeTimestamp'] =  $date_before_one_day.'T'.$now_time;
				$search_arr['MaxMajorChangeTimestamp'] =  $today_date.'T'.$now_time;				
				
				
				
				if(!empty($search_arr['MinMajorChangeTimestamp']) && !empty($search_arr['MaxMajorChangeTimestamp']))
				{		
					$user_info = get_user_by('id',$result->user_id);		
					

					
					if(!empty($result->search_data))
					{
						$search_data = json_decode($result->search_data, true);
						if(!empty($search_data['Baths']))
						{
							$search_arr['MaxBaths'] = $search_data['Baths'];
		        			$search_arr['MinBaths'] = $search_data['Baths'];
						}

						if(!empty($search_data['Beds']))
						{
							$search_arr['MaxBeds'] = $search_data['Beds'];
		        			$search_arr['MinBeds'] = $search_data['Beds'];

						} 

						if(!empty($search_data['Price From']))
						{
							$search_arr['MinPrice'] = $search_data['Price From'];
						}

						if(!empty($search_data['Price To']))
						{
							$search_arr['MaxPrice'] = $search_data['Price To'];
						}

						if(!empty($search_data['Property Type']))
						{
							$search_arr['PropertyType'] = $search_data['Property Type'];
						}else{
							$search_arr['PropertyType'] = 'A';
						}

						if(!empty($search_data['Min Baths']))
						{
							$search_arr['MinBaths'] = $search_data['Min Baths'];
						}

						if(!empty($search_data['Max Baths']))
						{
							$search_arr['MaxBaths'] = $search_data['Max Baths'];
						}

						if(!empty($search_data['Min Year']))
						{
							$search_arr['MinYear'] = $search_data['Min Year'];
						}

						if(!empty($search_data['Max Year']))
						{
							$search_arr['Max Year'] = $search_data['Max Year'];
						}

						if(!empty($search_data['Min SqFt']))
						{
							$search_arr['MinSqFt'] = $search_data['Min SqFt'];
						}

						if(!empty($search_data['Max SqFt']))
						{
							$search_arr['MaxSqFt'] = $search_data['Max SqFt'];
						}

						if(!empty($search_data['Min Price']))
						{
							$search_arr['MinPrice'] = $search_data['Min Price'];
						}

						if(!empty($search_data['Max Price']))
						{
							$search_arr['MaxPrice'] = $search_data['Max Price'];
						}

						if(!empty($search_data['Order By']))
						{
							if (strpos($search_data['Order By'], '$') == false) {
							    $search_arr['OrderBy'] = $search_data['Order By'];
							}        
						}

						if(!empty($search_data['Standard Status']))
						{
							$search_arr['StandardStatus'] = $search_data['Standard Status'];
						}

						if(!empty($search_data['Garage Spaces']))
						{
							$search_arr['GarageSpaces'] = $search_data['Garage Spaces'];
						}

						if(!empty($search_data['Saved Search']))
						{
							$search_arr['SavedSearch'] = $search_data['Saved Search'];
						}				

						if(!empty($search_data['City']))
						{
							$search_arr['City'] = $search_data['City'];
						}				

					}	
					//print_r($search_data); die; echo "search<br>";
					$MajorChangeType = array('Status Change','New Listing','Price Reduced','Price Change');
				
					foreach ($MajorChangeType as  $type) {		

						if($result->status_change == 1 && $type == 'Status Change')
						{
							//echo "<pre>"; print_r($result); 
							//echo "1<br>";
							$search_arr['MajorChangeType'] =  'Status Change';
							$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							//echo $get_data; die;
							if(!empty($get_data))
							{  //echo 1;
								$sen_name = "Alaska Dream Makers Info";
								$sen_email = "info@alaskaland.info";
								$rec_name = $user_info->data->display_name;
								$rec_email = $user_info->data->user_email;
										//$rec_email = 'damini.jaiswal@techinfini.com';
								$email_type = "to";
								$email_sub = "Listing Status Changed at alaskadreammakers.info";
								$msg_type = "html";					
								$box_msg = "This property has been found to match your request for Status Change.<br />";
								$box_msg .= $get_data;
								$message = array();
								$to = array();
								$to[] = array(
								'email' => $rec_email,
								'name' => $rec_name,
								'type' => $email_type
								);
								$message['subject'] = $email_sub;
								$message[$msg_type] = $box_msg;
								$message['from_email'] = $sen_email;
								$message['from_name'] = $sen_name;
								$message['to'] = $to;
								
								$email_result = $mandrill->messages->send($message);
								//print_r($email_result); echo "<br>";
							}
							
					          
						}
						else if($result->new_listing == 1 && $type == 'New Listing')
						{
							//echo "<pre>"; print_r($result); 
							//echo "2<br>";
							$search_arr['MajorChangeType'] =  'New Listing';
							$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							
							if(!empty($get_data))
							{ //echo 2;
								$sen_name = "Alaska Dream Makers Info";
								$sen_email = "info@alaskaland.info";
								$rec_name = $user_info->data->display_name;
								$rec_email = $user_info->data->user_email;
										//$rec_email = 'damini.jaiswal@techinfini.com';
								$email_type = "to";
								$email_sub = "New Listing at alaskadreammakers.info";
								$msg_type = "html";					
								$box_msg = "This property has been found to match your request for New Listing.<br />";
								$box_msg .= $get_data;
								$message = array();
								$to = array();
								$to[] = array(
								'email' => $rec_email,
								'name' => $rec_name,
								'type' => $email_type
								);
								$message['subject'] = $email_sub;
								$message[$msg_type] = $box_msg;
								$message['from_email'] = $sen_email;
								$message['from_name'] = $sen_name;
								$message['to'] = $to;
								
								$email_result = $mandrill->messages->send($message);
								//print_r($email_result); echo "<br>";
							}
						}
						else if($result->price_change == 1 && $type == 'Price Reduced')
						{
							//echo "<pre>"; print_r($result);
							// echo "3<br>";
							$search_arr['MajorChangeType'] =  'Price Reduced';
							$get_price_reduced_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							
						}
						else if($result->price_change == 1 && $type == 'Price Change')
						{
							//echo "<pre>"; print_r($result);
							// echo "3<br>"; $get_price_reduced_data
							$search_arr['MajorChangeType'] =  'Price Change';
							if(!empty($get_price_reduced_data))
							{
								$search_arr['price_reduced_data'] =  $get_price_reduced_data;
							}						
							$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							//echo $get_data;
							if(!empty($get_data))
							{	//echo 3;
								$sen_name = "Alaska Dream Makers Info";
								$sen_email = "info@alaskaland.info";
								$rec_name = $user_info->data->display_name;
								$rec_email = $user_info->data->user_email;
										//$rec_email = 'damini.jaiswal@techinfini.com';
								$email_type = "to";
								$email_sub = "Listing Price Changed at alaskadreammakers.info";
								$msg_type = "html";					
								$box_msg = "This property has been found to match your request for Price Change.<br />";
								$box_msg .= $get_data;
								$message = array();
								$to = array();
								$to[] = array(
								'email' => $rec_email,
								'name' => $rec_name,
								'type' => $email_type
								);
								$message['subject'] = $email_sub;
								$message[$msg_type] = $box_msg;
								$message['from_email'] = $sen_email;
								$message['from_name'] = $sen_name;
								$message['to'] = $to;
								
								$email_result = $mandrill->messages->send($message);
								//print_r($email_result); echo "<br>";
							}
						}
					}
				}	
			}
			
		}
	}
		
	
}

//add_shortcode( 'saved_search_hourly_cron', 'saved_search_hourly_cron' );
function saved_search_hourly_cron()
{ 
	date_default_timezone_set('America/Anchorage');
	global $wpdb;
	//include("lib/Mandrill.php");
	$mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');
	$FlexMLS_IDX = new FlexMLS_IDX();
	$search_arr = array();

	$today_date = date('Y-m-d');
	$date_before_one_day = date('Y-m-d', strtotime("-1 days"));

	$now_time = date('H:i:s');
	$time_before_one_hour = date('H:i:s', strtotime('-1 hour'));

	$now_hour = explode(":", $now_time);
	$hour_before_one_hour = explode(":", $time_before_one_hour);	

	

	$sql = "SELECT * FROM `saved_search` WHERE `active` = 1 and `email_management` = 1";
	$results = $wpdb->get_results($sql);
	
	if(!empty($results))
	{
		foreach( $results as $result ) 
		{ 
			$saved_search_status = get_user_meta( $result->user_id, 'saved_search_status', true );
			if($saved_search_status == 1)
			{
				$search_arr = array();
				if($result->active == 1)
				{
					

				if($now_hour[0] == '24')
				{
					$search_arr['MinMajorChangeTimestamp'] =  $date_before_one_day.'T'.$time_before_one_hour;
					$search_arr['MaxMajorChangeTimestamp'] =  $today_date.'T'.$now_time;
				}else{				
					$search_arr['MinMajorChangeTimestamp'] =  $today_date.'T'.$time_before_one_hour;
					$search_arr['MaxMajorChangeTimestamp'] =  $today_date.'T'.$now_time;							
				}

				

				//print_r($search_arr); echo "search<br>";
				
				if(!empty($search_arr['MinMajorChangeTimestamp']) && !empty($search_arr['MaxMajorChangeTimestamp']))
				{		
					$user_info = get_user_by('id',$result->user_id);		
					

					
					if(!empty($result->search_data))
					{
						$search_data = json_decode($result->search_data, true);
						if(!empty($search_data['Baths']))
						{
							$search_arr['MaxBaths'] = $search_data['Baths'];
		        			$search_arr['MinBaths'] = $search_data['Baths'];
						}

						if(!empty($search_data['Beds']))
						{
							$search_arr['MaxBeds'] = $search_data['Beds'];
		        			$search_arr['MinBeds'] = $search_data['Beds'];

						} 

						if(!empty($search_data['Price From']))
						{
							$search_arr['MinPrice'] = $search_data['Price From'];
						}

						if(!empty($search_data['Price To']))
						{
							$search_arr['MaxPrice'] = $search_data['Price To'];
						}

						if(!empty($search_data['Property Type']))
						{
							$search_arr['PropertyType'] = $search_data['Property Type'];
						}else{
							$search_arr['PropertyType'] = 'A';
						}

						if(!empty($search_data['Min Baths']))
						{
							$search_arr['MinBaths'] = $search_data['Min Baths'];
						}

						if(!empty($search_data['Max Baths']))
						{
							$search_arr['MaxBaths'] = $search_data['Max Baths'];
						}

						if(!empty($search_data['Min Year']))
						{
							$search_arr['MinYear'] = $search_data['Min Year'];
						}

						if(!empty($search_data['Max Year']))
						{
							$search_arr['Max Year'] = $search_data['Max Year'];
						}

						if(!empty($search_data['Min SqFt']))
						{
							$search_arr['MinSqFt'] = $search_data['Min SqFt'];
						}

						if(!empty($search_data['Max SqFt']))
						{
							$search_arr['MaxSqFt'] = $search_data['Max SqFt'];
						}

						if(!empty($search_data['Min Price']))
						{
							$search_arr['MinPrice'] = $search_data['Min Price'];
						}

						if(!empty($search_data['Max Price']))
						{
							$search_arr['MaxPrice'] = $search_data['Max Price'];
						}

						if(!empty($search_data['Order By']))
						{
							if (strpos($search_data['Order By'], '$') == false) {
							    $search_arr['OrderBy'] = $search_data['Order By'];
							}        
						}

						if(!empty($search_data['Standard Status']))
						{
							$search_arr['StandardStatus'] = $search_data['Standard Status'];
						}

						if(!empty($search_data['Garage Spaces']))
						{
							$search_arr['GarageSpaces'] = $search_data['Garage Spaces'];
						}

						if(!empty($search_data['Saved Search']))
						{
							$search_arr['SavedSearch'] = $search_data['Saved Search'];
						}				

						if(!empty($search_data['City']))
						{
							$search_arr['City'] = $search_data['City'];
						}				

					}	
					//print_r($search_data); die; echo "search<br>";
					$MajorChangeType = array('Status Change','New Listing','Price Reduced','Price Change');
				
					foreach ($MajorChangeType as  $type) {		

						if($result->status_change == 1 && $type == 'Status Change')
						{
							//echo "<pre>"; print_r($result); 
							//echo "1<br>";
							$search_arr['MajorChangeType'] =  'Status Change';
							$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							//echo $get_data; die;
							if(!empty($get_data))
							{  //echo 1;
								$sen_name = "Alaska Dream Makers Info";
								$sen_email = "info@alaskaland.info";
								$rec_name = $user_info->data->display_name;
								$rec_email = $user_info->data->user_email;
								//$rec_email = 'damini.jaiswal@techinfini.com';
								$email_type = "to";
								$email_sub = "Listing Status Changed at alaskadreammakers.info";
								$msg_type = "html";					
								$box_msg = "This property has been found to match your request for Status Change.<br />";
								$box_msg .= $get_data;
								$message = array();
								$to = array();
								$to[] = array(
								'email' => $rec_email,
								'name' => $rec_name,
								'type' => $email_type
								);
								$message['subject'] = $email_sub;
								$message[$msg_type] = $box_msg;
								$message['from_email'] = $sen_email;
								$message['from_name'] = $sen_name;
								$message['to'] = $to;
								
								$email_result = $mandrill->messages->send($message);
								//print_r($email_result); echo "<br>";
							}
							
					          
						}
						else if($result->new_listing == 1 && $type == 'New Listing')
						{
							//echo "<pre>"; print_r($result); 
							//echo "2<br>";
							$search_arr['MajorChangeType'] =  'New Listing';
							$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							
							if(!empty($get_data))
							{ //echo 2;
								$sen_name = "Alaska Dream Makers Info";
								$sen_email = "info@alaskaland.info";
								$rec_name = $user_info->data->display_name;
								$rec_email = $user_info->data->user_email;
								//$rec_email = 'damini.jaiswal@techinfini.com';
								$email_type = "to";
								$email_sub = "New Listing at alaskadreammakers.info";
								$msg_type = "html";					
								$box_msg = "This property has been found to match your request for New Listing.<br />";
								$box_msg .= $get_data;
								$message = array();
								$to = array();
								$to[] = array(
								'email' => $rec_email,
								'name' => $rec_name,
								'type' => $email_type
								);
								$message['subject'] = $email_sub;
								$message[$msg_type] = $box_msg;
								$message['from_email'] = $sen_email;
								$message['from_name'] = $sen_name;
								$message['to'] = $to;
								
								$email_result = $mandrill->messages->send($message);
								//print_r($email_result); echo "<br>";
							}
						}
						else if($result->price_change == 1 && $type == 'Price Reduced')
						{
							//echo "<pre>"; print_r($result);
							// echo "3<br>";
							$search_arr['MajorChangeType'] =  'Price Reduced';
							$get_price_reduced_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							
						}
						else if($result->price_change == 1 && $type == 'Price Change')
						{
							//echo "<pre>"; print_r($result);
							// echo "3<br>";						
							$search_arr['MajorChangeType'] =  'Price Change';
							if(!empty($get_price_reduced_data))
							{
								$search_arr['price_reduced_data'] =  $get_price_reduced_data;
							}	
							$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_search($search_arr,$type,$search_data['current_urls']);
							//echo $get_data;
							if(!empty($get_data))
							{	//echo 3;
								$sen_name = "Alaska Dream Makers Info";
								$sen_email = "info@alaskaland.info";
								$rec_name = $user_info->data->display_name;
								$rec_email = $user_info->data->user_email;
								//$rec_email = 'damini.jaiswal@techinfini.com';
								$email_type = "to";
								$email_sub = "Listing Price Changed at alaskadreammakers.info";
								$msg_type = "html";					
								$box_msg = "This property has been found to match your request for Price Change.<br />";
								$box_msg .= $get_data;
								$message = array();
								$to = array();
								$to[] = array(
								'email' => $rec_email,
								'name' => $rec_name,
								'type' => $email_type
								);
								$message['subject'] = $email_sub;
								$message[$msg_type] = $box_msg;
								$message['from_email'] = $sen_email;
								$message['from_name'] = $sen_name;
								$message['to'] = $to;
								
								$email_result = $mandrill->messages->send($message);
								//print_r($email_result); echo "<br>";
							}
						}
					}
				}
			}	
			
		}
	}
		
	} 
}

//add_shortcode( 'saved_listing_Cron', 'saved_listing_Cron' );
function saved_listing_Cron(){
	date_default_timezone_set('America/Anchorage');
    global $wpdb;
	//include('lib/Mandrill.php');

	
		$mandrill = new Mandrill('hMuUQAqpVG9JiVjZ8ObXgQ');

		$FlexMLS_IDX = new FlexMLS_IDX();
		$search_arr = array();
		$today_date = date('Y-m-d');
		$date_before_one_day = date('Y-m-d', strtotime("-1 days"));

		$now_time = date('H:i:s');
		$time_before_one_hour = date('H:i:s', strtotime('-1 hour'));

		$now_hour = explode(":", $now_time);
		$hour_before_one_hour = explode(":", $time_before_one_hour);

        $start_date = date('Y-m-d', strtotime("-1 days"));
		$stop_date = date('Y-m-d');	
		     

	        $sql = "SELECT * FROM `saved_listing`";
			$results = $wpdb->get_results($sql);			
				
					
				if(!empty($results))
				{ 
					foreach( $results as $result ) {
					$saved_listing_status = get_user_meta( $result->user_id, 'saved_listing_status', true );
					if($saved_listing_status == 1)
					{
						$search_arr = array();	
						if($now_hour[0] == '24')
						{
							$search_arr['MinMajorChangeTimestamp'] =  $start_date.'T'.$now_time;
							$search_arr['MaxMajorChangeTimestamp'] =  $stop_date.'T'.$now_time;   
						}else{
							break;
						}
						
						if(!empty($search_arr['MinMajorChangeTimestamp']) && !empty($search_arr['MaxMajorChangeTimestamp']))
						{							

						$listing_data =  json_decode($result->listing_data);			
				        $user_info = get_user_by('id',$result->user_id);


				        $MajorChangeType = array('Status Change','New Listing','Price Reduced','Price Change');
						
				        //print_r($search_arr); echo "list<br>";

						foreach ($MajorChangeType as  $type) {		

							if($type == 'Status Change')
							{								
								$search_arr['MajorChangeType'] =  'Status Change';
								
								$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp']);
								
								if(!empty($get_data))
								{  //echo 1;
									$sen_name = "Alaska Dream Makers Info";
									$sen_email = "info@alaskaland.info";
									$rec_name = $user_info->data->display_name;
									$rec_email = $user_info->data->user_email;
									//$rec_email = 'damini.jaiswal@techinfini.com';
									$email_type = "to";
									$email_sub = "Saved Listing Status Changed at alaskadreammakers.info";
									$msg_type = "html";					
									$box_msg = "This property has been found to match your request for Status Change.<br />";
									$box_msg .= $get_data;
									$message = array();
									$to = array();
									$to[] = array(
									'email' => $rec_email,
									'name' => $rec_name,
									'type' => $email_type
									);
									$message['subject'] = $email_sub;
									$message[$msg_type] = $box_msg;
									$message['from_email'] = $sen_email;
									$message['from_name'] = $sen_name;
									$message['to'] = $to;
									
									$email_result = $mandrill->messages->send($message);
									//print_r($email_result); echo "<br>";
								}
								
						          
							}
							else if($type == 'New Listing')
							{
								// $search_arr['MinMajorChangeTimestamp'] =  $start_date.'T12:00:00';
			  					//$search_arr['MaxMajorChangeTimestamp'] =  $stop_date.'T12:00:00';
								// $search_arr['MajorChangeType'] =  'New Listing';
								// $get_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp']);
								
								// if(!empty($get_data))
								// { echo 2;
								// 	$sen_name = "Larry";
								// 	$sen_email = "info@alaskaland.info";
								// 	$rec_name = $user_info->data->display_name;
								// 	$rec_email = $user_info->data->user_email;
								// 	$email_type = "to";
								// 	$email_sub = "Listing Status Changed at alaskadreammakers.info";
								// 	$msg_type = "html";					
								// 	$box_msg = "This property has been found to match your request for New Listing.<br />";
								// 	$box_msg .= $get_data;
								// 	$message = array();
								// 	$to = array();
								// 	$to[] = array(
								// 	'email' => $rec_email,
								// 	'name' => $rec_name,
								// 	'type' => $email_type
								// 	);
								// 	$message['subject'] = $email_sub;
								// 	$message[$msg_type] = $box_msg;
								// 	$message['from_email'] = $sen_email;
								// 	$message['from_name'] = $sen_name;
								// 	$message['to'] = $to;
									
								// 	$email_result = $mandrill->messages->send($message);
								// 	print_r($email_result); echo "<br>";
								//}
							}
							else if($result->price_change == 1 && $type == 'Price Reduced')
							{
								//echo "<pre>"; print_r($result);
								// echo "3<br>";
								$search_arr['MajorChangeType'] =  'Price Reduced';
								$get_price_reduced_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp']);
								
							}
							else if($type == 'Price Change')
							{								
								$search_arr['MajorChangeType'] =  'Price Change';
								if(!empty($get_price_reduced_data))
								{
									$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp'],$get_price_reduced_data);
								}else{
									$get_data = $FlexMLS_IDX->get_MajorChangeType_saved_listing($listing_data->ListingTag,$result->id,$result->user_id,$result->date,$search_arr['MajorChangeType'],$search_arr['MinMajorChangeTimestamp'],$search_arr['MaxMajorChangeTimestamp']);
								}
								
								//echo $get_data; 
								if(!empty($get_data))
								{	//echo "<br>"; echo 3;
									$sen_name = "Alaska Dream Makers Info";
									$sen_email = "info@alaskaland.info";
									$rec_name = $user_info->data->display_name;
									$rec_email = $user_info->data->user_email;
									//$rec_email = 'damini.jaiswal@techinfini.com';
									$email_type = "to";
									$email_sub = "Saved Listing Price Changed at alaskadreammakers.info";
									$msg_type = "html";					
									$box_msg = "This property has been found to match your request for Price Change.<br />";
									$box_msg .= $get_data;
									$message = array();
									$to = array();
									$to[] = array(
									'email' => $rec_email,
									'name' => $rec_name,
									'type' => $email_type
									);
									$message['subject'] = $email_sub;
									$message[$msg_type] = $box_msg;
									$message['from_email'] = $sen_email;
									$message['from_name'] = $sen_name;
									$message['to'] = $to;
									
									$email_result = $mandrill->messages->send($message);
									//print_r($email_result); echo "<br>";
								}
							}
						}	
				}	}
		}
	}
			       
		
}