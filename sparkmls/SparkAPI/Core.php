<?php
namespace SparkAPI;

defined( 'ABSPATH' ) or die( 'This plugin requires WordPress' );

class Core {

	protected $api_base;
	protected $api_headers;
	protected $api_version;
	protected $location_search_url;
	protected $plugin_version;

	function __construct(){
		$this->api_base = FMC_API_BASE;
		$this->api_version = FMC_API_VERSION;
		$this->location_search_url = FMC_LOCATION_SEARCH_URL;
		$this->plugin_version = FMC_PLUGIN_VERSION;
		$this->api_headers = array(
			'Accept-Encoding' => 'gzip,deflate',
			'Content-Type' => 'application/json',
			'User-Agent' => 'FlexMLS WordPress Plugin/' . $this->api_version,
			'X-SparkApi-User-Agent' => 'flexmls-WordPress-Plugin/' . $this->plugin_version
		);
	}

	function admin_notices_api_connection_error(){
		echo '	<div class="notice notice-error">
					<p>There was an error connecting to the FlexMLS&reg; IDX API. Please check your credentials and try again. If your credentials are correct and you continue to see this error message, please <a href="' . admin_url( 'admin.php?page=fmc_admin_settings&tab=support' ) . '">contact support</a>.</p>
				</div>';
	}

	function clear_cache( $force = false ){
		global $wpdb;
		/*----------------------------------------------------------------------
		  VERSION 3.5.9
		  New caching system implemented using only WordPress transients so we
		  need to delete all old options from previous versions that were
		  clogging up the database. This first query deletes all old options
		  using the fmc_ transient & caching system. We delete these 250 at
		  a time to try not to crash agent servers.
		----------------------------------------------------------------------*/
		$delete_query = "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s LIMIT 250";
		$wpdb->query( $wpdb->prepare(
			$delete_query,
			'_transient_fmc%',
			'_transient_timeout_fmc%'
		) );

		if( true === $force ){
			/*----------------------------------------------------------------------
			  The user has requested that ALL FlexMLS caches be purged so
			  we will bulk delete all newly created FlexMLS caches
			----------------------------------------------------------------------*/
			$wpdb->query( $wpdb->prepare(
				$delete_query,
				'_transient_flexmls_query_%',
				'_transient_timeout_flexmls_query_%'
			) );
			delete_option( 'fmc_db_cache_key' );
		} else {
			/*----------------------------------------------------------------------
			  Just delete expired FlexMLS transients but leave current ones
			  in tact. This is just regular clean-up, not a forced cache clear.
			----------------------------------------------------------------------*/
			$time = time();
			$sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
				AND b.option_value < %d";
			$wpdb->query( $wpdb->prepare(
				$sql, $wpdb->esc_like( '_transient_flexmls_query_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_flexmls_query_' ) . '%',
				$time
			) );
		}
		delete_transient( 'flexmls_auth_token' );
		$this->generate_auth_token();
		return true;
	}

	function generate_auth_token( $retry = true ){
		global $auth_token_failures;
		if( false === ( $auth_token = get_transient( 'flexmls_auth_token' ) ) && $auth_token_failures < 2 ){
			$options = get_option( 'fmc_settings' );
			if( !isset( $options[ 'api_key' ] ) || !isset( $options[ 'api_secret' ] ) ){
				return false;
			}
			$security_string = md5( $options[ 'api_secret' ] . 'ApiKey' . $options[ 'api_key' ] );
			$params = array(
				'ApiKey' => $options[ 'api_key' ],
				'ApiSig' => $security_string
			);
			$query = build_query( $params );
			$url = 'https://' . $this->api_base . '/' . $this->api_version . '/session?' . build_query( $params );
			$args = array(
				'headers' => $this->api_headers
			);
			$response = wp_remote_post( $url, $args );
			if( is_wp_error( $response ) ){
				$auth_token_failures++;
				if( false === $retry ){
					add_action( 'admin_notices', array( $this, 'admin_notices_api_connection_error' ) );
					return false;
				} else {
					$auth_token = $this->generate_auth_token( false );
				}
			} else {
				$json = json_decode( wp_remote_retrieve_body( $response ), true );
				if( array_key_exists( 'D', $json ) && true == $json[ 'D' ][ 'Success' ] ){
					set_transient( 'flexmls_auth_token', $json, 15 * MINUTE_IN_SECONDS );
					$auth_token = $json;
				} else {
					$auth_token_failures++;
				}
			}
		}
		return $auth_token;
	}

	function get_first_result( $response ){
		if( true == $response[ 'success' ] ){
			if( count( $response[ 'results' ] ) ){
				return $response[ 'results' ][ 0 ];
			} else {
				return null;
			}
		}
		return false;
	}

	function get_all_results( $response = array() ){
		if( isset( $response[ 'success' ] ) && $response[ 'success' ] ){
			return $response[ 'results' ];
		}
		return false;
	}

	// This function is used for calls done from inside this instantiated class
	function get_from_api( $method, $service, $cache_time = 0, $params = array(), $post_data = null, $a_retry = false ){
		$json = $this->make_api_call( $method, $service, $cache_time, $params, $post_data, $a_retry );
		$return = array();

		if( array_key_exists( 'D', $json ) ){
			if( array_key_exists( 'Code', $json[ 'D' ] ) ){
				$this->last_error_code = $json[ 'D' ][ 'Code' ];
				$return[ 'api_code' ] = $json[ 'D' ][ 'Code' ];
			}
			if( array_key_exists( 'Message', $json[ 'D' ] ) ){
				$this->last_error_mess = $json[ 'D' ][ 'Message' ];
				$return[ 'api_message' ] = $json[ 'D' ][ 'Message' ];
			}
			if( array_key_exists( 'Pagination', $json[ 'D' ] ) ){
				$this->last_count = $json[ 'D' ][ 'Pagination' ][ 'TotalRows' ];
				$this->page_size = $json[ 'D' ][ 'Pagination' ][ 'PageSize' ];
				$this->total_pages = $json[ 'D' ][ 'Pagination' ][ 'TotalPages' ];
				$this->current_page = $json[ 'D' ][ 'Pagination' ][ 'CurrentPage' ];
			} else {
				$this->last_count = null;
				$this->page_size = null;
				$this->total_pages = null;
				$this->current_page = null;
			}
			if( true == $json[ 'D' ][ 'Success' ] ){
				$return[ 'success' ] = true;
				$return[ 'results' ] = $json[ 'D' ][ 'Results' ];
			} else {
				$return[ 'success' ] = false;
			}
		}
		return $return;
	}

	function is_not_blank_or_restricted( $val ){
		// Need to find back references and delete them.
		// This temporary placeholder is so nothing breaks in the interim.
		// It should be removed and references should be to the Formatter class.
		return \FlexMLS\Admin\Formatter::is_not_blank_or_restricted( $val );
	}

	function make_api_call( $method, $service, $cache_time = 0, $params = array(), $post_data = null, $a_retry = false ){
		//\FlexMLS_IDX::write_log( debug_backtrace() );
		if( !$this->generate_auth_token() ){
			// $this->get_from_api expects an array. Send one that indicates
			// we're not connected to the API.
			//echo "<span style='display:none;'>3</span>";//die;
			return array( 'D' => array( 'Success' => false ) );
		}else{
			$seconds_to_cache = $this->parse_cache_time( $cache_time );

			$method = sanitize_text_field( $method );

			$request = array(
				'cache_duration' => $seconds_to_cache,
				'method' => $method,
				'params' => $params,
				'post_data' => $post_data,
				'service' => $service
			);

			$request = $this->sign_request( $request );

			$transient_name = 'flexmls_query_' . $request[ 'transient_name' ];

			$is_auth_request = ( 'session' == $request[ 'service' ] ? true : false );

			if( $is_auth_request ){
				return $this->generate_auth_token();
			}

			$return = array();

			if( false === ( $json = get_transient( $transient_name ) ) ){
				$url = 'http://' . $this->api_base . '/' . $this->api_version . '/' . $service . '?' . $request[ 'query_string' ];
				$json = array();
				$args = array(
					'method' => $method,
					'headers' => $this->api_headers,
					'body' => $post_data
				);
				$response = wp_remote_request( $url, $args );

				$return = array(
					'http_code' => wp_remote_retrieve_response_code( $response )
				);

				if( is_wp_error( $response ) ){
					add_action( 'admin_notices', array( $this, 'admin_notices_api_connection_error' ) );
					return $return;
				}
				$json = json_decode( wp_remote_retrieve_body( $response ), true );
				// if( !is_array( $json ) ){
				// 	// The response wasn't JSON as expected so bail out with the original, unparsed body
				// 	$return[ 'body' ] = $json;
				// 	return $return;
				// }
				if( array_key_exists( 'D', $json ) ){
					if( true == $json[ 'D' ][ 'Success' ] && 'GET' == strtoupper( $method ) ){ //echo "<span style='display:none;'>1</span>";
						set_transient( 'flexmls_query_' . $request[ 'transient_name' ], $json, $seconds_to_cache );
					} elseif( isset( $json[ 'D' ][ 'Code' ] ) && 1020 == $json[ 'D' ][ 'Code' ] ){
						delete_transient( 'flexmls_auth_token' );
						if( $this->generate_auth_token() ){ //echo "<span style='display:none;'>2</span>";
							//$json = $this->make_api_call( $method, $service, $cache_time = 0, $params = array(), $post_data = null, $a_retry = false );
							$json = $this->make_api_call( $method, $service, $cache_time = 0, $params);
						}
					}
				}
			}
		}
		return $json;
	}

	function make_sendable_body( $data ){
		return json_encode( array( 'D' => $data ) );
	}

	function parse_cache_time( $time_value = 0 ){
		// Need to find back references and delete them.
		// This temporary placeholder is so nothing breaks in the interim.
		// It should be removed and references should be to the Formatter class.
		return \FlexMLS\Admin\Formatter::parse_cache_time( $time_value );
	}

	function parse_location_search_string( $location ){
		// Need to find back references and delete them.
		// This temporary placeholder is so nothing breaks in the interim.
		// It should be removed and references should be to the Formatter class.
		return \FlexMLS\Admin\Formatter::parse_location_search_string( $location );
	}

	function return_all_results( $response = array() ){
		return $this->get_all_results( $response );
	}

	function sign_request( $request ){
		$options = get_option( 'fmc_settings' );
		$security_string = $options[ 'api_secret' ] . 'ApiKey' . $options[ 'api_key' ];

		$request[ 'cacheable_query_string' ] = build_query( $request[ 'params' ] );
		$params = $request[ 'params' ];

		$post_body = '';
		if( 'POST' == $request[ 'method' ] && !empty( $request[ 'post_data' ] ) ){
			// the request is to post some JSON data back to the API (like adding a contact)
			$post_body = $request[ 'post_data' ];
		}

		$is_auth_request = ( 'session' == $request[ 'service' ] ? true : false );

		if( $is_auth_request ){
			$params[ 'ApiKey' ] = $options[ 'api_key' ];
		} else {
			$params[ 'AuthToken' ] = '';
			$auth_token = get_transient( 'flexmls_auth_token' );
			if( $auth_token ){
				$params[ 'AuthToken' ] = $auth_token[ 'D' ][ 'Results' ][ 0 ][ 'AuthToken' ];
			}

			$security_string .= 'ServicePath' . rawurldecode( '/' . $this->api_version . '/' . $request[ 'service' ] );

			ksort( $params );

			$params_encoded = array();

			foreach( $params as $key => $value ){
				$security_string .= $key . $value;
				$params_encoded[ $key ] = urlencode( $value );
			}
		}
		if( !empty( $post_body ) ){
			// add the post data to the end of the security string if it exists
			$security_string .= $post_body;
		}

		$params_encoded[ 'ApiSig' ] = md5( $security_string );

		$request[ 'params' ] = $params_encoded;

		$request[ 'query_string' ] = build_query( $params_encoded );

		if( isset( $params_encoded[ 'AuthToken' ] ) ){
			unset( $params_encoded[ 'AuthToken' ] );
		}
		unset( $params_encoded[ 'ApiSig' ] );

		$params_encoded[ $request[ 'method' ] ] = $request[ 'service' ];

		$request[ 'transient_name' ] = sha1( build_query( $params_encoded ) );

		return $request;
	}
}