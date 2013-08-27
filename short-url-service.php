<?php
/*
Plugin Name: Short URL Service
Plugin URI: http://pdclark.com
Description: Provide service for generating URLs with <a href="http://wordpress.org/plugins/shorten-url/">Short URL</a> plugin. <a href="admin-ajax.php?action=shorten&url=http%3A%2F%2Fexample.com" target="_blank">View example query</a>.
Version: 1.0
Author: Brainstorm Media
Author URI: http://brainstormmedia.com
*/

/**
 * Copyright (c) 2013 Brainstorm Media. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

class Storm_Short_URL_Service {

	static public function init() {
		add_action( 'wp_ajax_shorten', array( __CLASS__, 'shorten' ) );
		add_action( 'wp_ajax_nopriv_shorten', array( __CLASS__, 'shorten' ) );
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

}

add_action( 'init', 'Storm_Short_URL_Service::init' );