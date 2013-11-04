<?php
/*
Plugin Name: Short URL Service
Plugin URI: http://pdclark.com
Description: Provide service for generating URLs with <a href="http://wordpress.org/plugins/shorten-url/">Short URL</a> plugin. <a href="admin-ajax.php?action=shorten&url=http%3A%2F%2Fexample.com" target="_blank">View example query</a>.
Version: 1.0
Author: Brainstorm Media
Author URI: http://brainstormmedia.com
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class Storm_Short_URL_Service {

	static public function init() {
		add_action( 'wp_ajax_shorten', array( __CLASS__, 'shorten' ) );
		add_action( 'wp_ajax_nopriv_shorten', array( __CLASS__, 'shorten' ) );

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 20 );
	}

	static public function check_requirements() {
		if ( !class_exists( 'shorturl' ) ) {
			exit( 'Short URL plugin not active.' );
		}
		if ( !isset( $_GET['url'] ) ) {
			exit( 'No URL set' );
		}
		if ( !filter_var( $_GET['url'], FILTER_VALIDATE_URL ) ) {
			exit( 'Please provide a valid URL. You sent: ' . $_GET['url'] );
		}
		if ( self::get_api_key() ) {
			if ( !isset( $_GET['api_key'] ) || $_GET['api_key'] != self::get_api_key() ) {
				exit( 'Please provide a valid API key.' );
			}
		}
	}

	static public function get_api_key() {
		$api_key = false;

		if ( defined( 'SHORT_URL_API_KEY') ) {
			$api_key = SHORT_URL_API_KEY;
		}

		return apply_filters( 'short_url_api_key', $api_key );
	}

	static public function shorten() {
		self::check_requirements();
		
		global $wpdb;
		
		$short = shorturl::getInstance();
		$long_url = self::sanatize_url( $_GET['url'] );

		// Get URL if exists
		$sql =  "SELECT short_url FROM {$short->table_name} WHERE id_post=0 AND url_externe='$long_url'";
		$short_url = $wpdb->get_var( $sql ); 

		if ( empty( $short_url ) ) {
			// Create URL if doesn't exist
			$short_url = $short->add_external_link( $long_url ); 
		}

		$short_url = trailingslashit( $short->get_home_url() ) . $short_url;

		exit( $short_url );
	}

	static public function sanatize_url( $url ) {
		if ( false === strpos( $url, 'http://') && false === strpos( $url, 'https://' ) ) {
			return 'http://' . $url;
		}else {
			return $url;
		}
	}

	/**
	 * Replace SedLex menu with Short URLs menu
	 */
	static public function admin_menu() {
		$short_url = shorturl::getInstance();

		remove_menu_page( 'sedlex.php' );
		remove_submenu_page( 'sedlex.php', 'sedlex.php' );
		remove_submenu_page( 'sedlex.php', 'shorten-url/shorten-url.php' );

		add_object_page( 'Short URLs', 'Short URLs', 'edit_options', 'shorten-url/shorten-url.php', array( $short_url, 'configuration_page' ) );

	}

}

add_action( 'init', 'Storm_Short_URL_Service::init' );