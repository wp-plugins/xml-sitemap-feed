<?php
/*
Plugin Name: XML Sitemap Feed
Plugin URI: http://4visions.nl/wordpress-plugins/xml-sitemap-feed/
Description: Creates a feed that complies with the XML Sitemap protocol ready for indexing by Google, Yahoo, Bing, Ask and others. Happy with it? Please leave me a <strong><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=XML%20Sitemap%20Feed&item_number=3%2e8&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8">Tip</a></strong> for development and support time. Thanks :)
Version: 3.7.4
Author: RavanH
Author URI: http://4visions.nl/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=XML%20Sitemap%20Feed&item_number=3%2e8&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8
*/

/*  Copyright 2009 RavanH  (http://4visions.nl/ email : ravanhagen@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// ALTERNATIVE to investigate 3.5:
// Use "template_redirect" just like do_robots does and make sitemap.xml available via /?sitemapxml=1
// and make a rewrite to sitemap.xml for it... Nearly works but :( ...
// PROBLEMS so far: 
// * Redirect rules no longer working ?!?

/* --------------------
       CONSTANTS
   -------------------- */

// set version
define('XMLSF_VERSION','3.7.4');

// dir
$xmlsf_dir = dirname(__FILE__);

// check if xml-sitemap.php is moved one dir up like in WPMU's /mu-plugins/
// NOTE: don't use WP_PLUGIN_URL to avoid problems when installed in /mu-plugins/
if (file_exists($xmlsf_dir.'/xml-sitemap-feed')) {
	define('XMLSF_PLUGIN_DIR', $xmlsf_dir.'/xml-sitemap-feed');
	define('XMLSF_PLUGIN_URL', plugins_url('xml-sitemap-feed', __FILE__) );
} else {
	define('XMLSF_PLUGIN_DIR', $xmlsf_dir);
	define('XMLSF_PLUGIN_URL', plugins_url('', __FILE__) );
}

/* --------------------
       FUNCTIONS
   -------------------- */

// FEEDS //
// set up the feed template
function xml_sitemap_load_template() {
	load_template( XMLSF_PLUGIN_DIR . '/feed-sitemap.php' );
}

// REWRITES //
// add sitemap rewrite rules
function xml_sitemap_rewrite($wp_rewrite) {
	$feed_rules = array(
		'sitemap.xml' => $wp_rewrite->index . '?feed=sitemap',
		'feed/sitemap' => $wp_rewrite->index . '?feed=sitemap'
	);
	$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
}
// recreate rewrite rules
function xml_sitemap_flush_rewrite_rules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

// ROBOTSTXT //
// add sitemap location in robots.txt generated by WP
function xml_sitemap_robots() {
	if ( '0' != get_option( 'blog_public' ) ) 
		echo "\nSitemap: ".get_option('home')."/sitemap.xml\n\n";
}

// DE/ACTIVATION
function xml_sitemap_activate() {
	update_option('xml-sitemap-feed-version', XMLSF_VERSION);
	xml_sitemap_flush_rewrite_rules();
}
function xml_sitemap_deactivate() {
	//remove_filter('query_vars', 'xml_sitemap_vars');
	remove_filter('generate_rewrite_rules', 'xml_sitemap_rewrite');
	delete_option('xml-sitemap-feed-version');
	xml_sitemap_flush_rewrite_rules();
}


// MISSING WORDPRESS FUNCTIONS

if( !function_exists('get_firstmodified') ) {
 function get_firstmodified($timezone = 'server') {
	$firstpostmodified = get_firstpostmodified($timezone);
	$firstpagemodified = get_firstpagemodified($timezone);
	if ( mysql2date('U',$firstpostmodified) < mysql2date('U',$firstpagemodified) )
		return $firstpostmodified;
	else
		return $firstpagemodified;
 }
}
if( !function_exists('get_lastmodified') ) {
 function get_lastmodified($timezone = 'server') {
	$lastpostmodified = get_lastpostmodified($timezone);
	$lastpagemodified = get_lastpagemodified($timezone);
	if ( mysql2date('U',$lastpostmodified) > mysql2date('U',$lastpagemodified) )
		return $lastpostmodified;
	else
		return $lastpagemodified;
 }
}

/**
 * Retrieve last page modified date depending on timezone.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is just when the last post was modified. The
 * 'gmt' is when the last post was modified in GMT time.
 *
 * Adaptation of get_lastpostmodified defined in wp-includes/post.php since 1.2.0
 *
 * @uses $wpdb
 * @uses $blog_id
 * @uses apply_filters() Calls 'get_lastpagemodified' filter
 *
 * @param string $timezone The location to get the time. Can be 'gmt', 'blog', or 'server'.
 * @return string The date the post was last modified.
 */
if( !function_exists('get_lastpagemodified') ) {
 function get_lastpagemodified($timezone = 'server') {
	global $wpdb;

	$add_seconds_server = date('Z');
	$timezone = strtolower( $timezone );

	$lastpagemodified = wp_cache_get( "lastpagemodified:$timezone", 'timeinfo' );
	if ( $lastpagemodified )
		return apply_filters( 'get_lastpagemodified', $lastpagemodified, $timezone );

	switch ( strtolower($timezone) ) {
		case 'gmt':
			$lastpagemodified = $wpdb->get_var("SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_modified_gmt DESC LIMIT 1");
			break;
		case 'blog':
			$lastpagemodified = $wpdb->get_var("SELECT post_modified FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_modified_gmt DESC LIMIT 1");
			break;
		case 'server':
			$lastpagemodified = $wpdb->get_var("SELECT DATE_ADD(post_modified_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_modified_gmt DESC LIMIT 1");
			break;
	}

	$lastpagedate = get_lastpagedate($timezone);
	if ( $lastpagedate > $lastpagemodified )
		$lastpagemodified = $lastpagedate;

	if ( $lastpagemodified )
		wp_cache_set( "lastpagemodified:$timezone", $lastpagemodified, 'timeinfo' );

	return apply_filters( 'get_lastpagemodified', $lastpagemodified, $timezone );
 }
}

/**
 * Retrieve the date that the last page was published.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is the date when the last post was posted. The
 * 'gmt' is when the last post was posted in GMT formatted date.
 *
 * Adaptation of get_lastpostdate defined in wp-includes/post.php since 0.71
 *
 * @uses $wpdb
 * @uses $blog_id
 * @uses apply_filters() Calls 'get_lastpagedate' filter
 *
 * @global mixed $cache_lastpagedate Stores the last post date
 * @global mixed $pagenow The current page being viewed
 *
 * @param string $timezone The location to get the time. Can be 'gmt', 'blog', or 'server'.
 * @return string The date of the last post.
 */
if( !function_exists('get_lastpagedate') ) {
 function get_lastpagedate($timezone = 'server') {
	global $cache_lastpagedate, $wpdb, $blog_id;
	$add_seconds_server = date('Z');
	if ( !isset($cache_lastpagedate[$blog_id][$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$lastpagedate = $wpdb->get_var("SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
			case 'blog':
				$lastpagedate = $wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
			case 'server':
				$lastpagedate = $wpdb->get_var("SELECT DATE_ADD(post_date_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_date_gmt DESC LIMIT 1");
				break;
		}
		$cache_lastpagedate[$blog_id][$timezone] = $lastpagedate;
	} else {
		$lastpagedate = $cache_lastpagedate[$blog_id][$timezone];
	}
	return apply_filters( 'get_lastpagedate', $lastpagedate, $timezone );
 }
}

/**
 * Retrieve first post modified date depending on timezone.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is the date when the last post was posted. The
 * 'gmt' is when the last post was posted in GMT formatted date.
 *
 * Reverse of get_lastpostmodified defined in wp-includes/post.php since WP 1.2.0
 *
 * @uses $wpdb
 * @uses apply_filters() Calls 'get_firstpostmodified' filter
 *
 * @param string $timezone The location to get the time. Can be 'gmt', 'blog', or 'server'.
 * @return string The date of the oldest modified post.
 */
if( !function_exists('get_firstpostmodified') ) {
 function get_firstpostmodified($timezone = 'server') {
	global $wpdb;

	$add_seconds_server = date('Z');
	$timezone = strtolower( $timezone );

	$firstpostmodified = wp_cache_get( "firstpostmodified:$timezone", 'timeinfo' );
	if ( $firstpostmodified )
		return apply_filters( 'get_firstpostmodified', $firstpostmodified, $timezone );

	switch ( strtolower($timezone) ) {
		case 'gmt':
			$firstpostmodified = $wpdb->get_var("SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_modified_gmt ASC LIMIT 1");
			break;
		case 'blog':
			$firstpostmodified = $wpdb->get_var("SELECT post_modified FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_modified_gmt ASC LIMIT 1");
			break;
		case 'server':
			$firstpostmodified = $wpdb->get_var("SELECT DATE_ADD(post_modified_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_modified_gmt ASC LIMIT 1");
			break;
	}

	$firstpostdate = get_firstpostdate($timezone);
	if ( $firstpostdate > $firstpostmodified )
		$firstpostmodified = $firstpostdate;

	if ( $firstpostmodified )
		wp_cache_set( "firstpostmodified:$timezone", $firstpostmodified, 'timeinfo' );

	return apply_filters( 'get_firstpostmodified', $firstpostmodified, $timezone );
 }
}

/**
 * Retrieve first page modified date depending on timezone.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is the date when the last post was posted. The
 * 'gmt' is when the last post was posted in GMT formatted date.
 *
 * Adaptation of get_firstpostmodified defined in this file
 *
 * @uses $wpdb
 * @uses apply_filters() Calls 'get_firstpagemodified' filter
 *
 * @param string $timezone The location to get the time. Can be 'gmt', 'blog', or 'server'.
 * @return string The date of the oldest modified page.
 */
if( !function_exists('get_firstpagemodified') ) {
 function get_firstpagemodified($timezone = 'server') {
	global $wpdb;

	$add_seconds_server = date('Z');
	$timezone = strtolower( $timezone );

	$firstpagemodified = wp_cache_get( "firstpagemodified:$timezone", 'timeinfo' );
	if ( $firstpagemodified )
		return apply_filters( 'get_firstpagemodified', $firstpagemodified, $timezone );

	switch ( strtolower($timezone) ) {
		case 'gmt':
			$firstpagemodified = $wpdb->get_var("SELECT post_modified_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_modified_gmt ASC LIMIT 1");
			break;
		case 'blog':
			$firstpagemodified = $wpdb->get_var("SELECT post_modified FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_modified_gmt ASC LIMIT 1");
			break;
		case 'server':
			$firstpagemodified = $wpdb->get_var("SELECT DATE_ADD(post_modified_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_modified_gmt ASC LIMIT 1");
			break;
	}

	$firstpagedate = get_firstpagedate($timezone);
	if ( $firstpagedate > $firstpagemodified )
		$firstpagemodified = $firstpagedate;

	if ( $firstpagemodified )
		wp_cache_set( "firstpagemodified:$timezone", $firstpagemodified, 'timeinfo' );

	return apply_filters( 'get_firstpagemodified', $firstpagemodified, $timezone );
 }
}

/**
 * Retrieve the date that the first post was published.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is the date when the last post was posted. The
 * 'gmt' is when the last post was posted in GMT formatted date.
 *
 * Reverse of get_lastpostdate defined in wp-includes/post.php since 0.71
 *
 * @uses $wpdb
 * @uses $cache_firstpostdate
 * @uses $blog_id
 * @uses apply_filters() Calls 'get_firstpostdate' filter
 *
 * @param string $timezone The location to get the time. Can be 'gmt', 'blog', or 'server'.
 * @return string The date of the last post.
 */
if( !function_exists('get_firstpostdate') ) {
 function get_firstpostdate($timezone = 'server') {
	global $cache_firstpostdate, $wpdb, $blog_id;
	$add_seconds_server = date('Z');
	if ( !isset($cache_firstpostdate[$blog_id][$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$firstpostdate = $wpdb->get_var("SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date_gmt ASC LIMIT 1");
				break;
			case 'blog':
				$firstpostdate = $wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date_gmt ASC LIMIT 1");
				break;
			case 'server':
				$firstpostdate = $wpdb->get_var("SELECT DATE_ADD(post_date_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post' ORDER BY post_date_gmt ASC LIMIT 1");
				break;
		}
		$cache_firstpostdate[$blog_id][$timezone] = $firstpostdate;
	} else {
		$firstpostdate = $cache_firstpostdate[$blog_id][$timezone];
	}
	return apply_filters( 'get_firstpostdate', $firstpostdate, $timezone );
 }
}

/**
 * Retrieve the date that the first post was published.
 *
 * The server timezone is the default and is the difference between GMT and
 * server time. The 'blog' value is the date when the last post was posted. The
 * 'gmt' is when the last post was posted in GMT formatted date.
 *
 * Adaptation of get_firstpostdate defined in this file
 *
 * @uses $wpdb
 * @uses $cache_firstpagedate
 * @uses $blog_id
 * @uses apply_filters() Calls 'get_firstpagedate' filter
 *
 * @param string $timezone The location to get the time. Can be 'gmt', 'blog', or 'server'.
 * @return string The date of the last post.
 */
if( !function_exists('get_firstpagedate') ) {
 function get_firstpagedate($timezone = 'server') {
	global $cache_firstpagedate, $wpdb, $blog_id;
	$add_seconds_server = date('Z');
	if ( !isset($cache_firstpagedate[$blog_id][$timezone]) ) {
		switch(strtolower($timezone)) {
			case 'gmt':
				$firstpagedate = $wpdb->get_var("SELECT post_date_gmt FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_date_gmt ASC LIMIT 1");
				break;
			case 'blog':
				$firstpagedate = $wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_date_gmt ASC LIMIT 1");
				break;
			case 'server':
				$firstpagedate = $wpdb->get_var("SELECT DATE_ADD(post_date_gmt, INTERVAL '$add_seconds_server' SECOND) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_date_gmt ASC LIMIT 1");
				break;
		}
		$cache_firstpagedate[$blog_id][$timezone] = $firstpagedate;
	} else {
		$firstpagedate = $cache_firstpagedate[$blog_id][$timezone];
	}
	return apply_filters( 'get_firstpagedate', $firstpagedate, $timezone );
 }
}

/* --------------------
       HOOKS 
   -------------------- */

// FLUSH RULES check
// limited to after (site wide) plugin upgrade
if (get_option('xml-sitemap-feed-version') != XMLSF_VERSION) {
	add_action('init', 'xml_sitemap_flush_rewrite_rules' );
	update_option('xml-sitemap-feed-version', XMLSF_VERSION);
}

if ( $wpdb->blogid && function_exists('get_site_option') && get_site_option('tags_blog_id') == $wpdb->blogid ) {
	// we are on wpmu and this is a tags blog!
	// create NO sitemap since it will be full 
	// of links outside the blogs own domain...
	return;
} else {
	// FEEDS
	add_action('do_feed_sitemap', 'xml_sitemap_load_template', 10, 1);

	// REWRITES
	add_filter('generate_rewrite_rules', 'xml_sitemap_rewrite');

	// ROBOTSTXT
	add_action('do_robotstxt', 'xml_sitemap_robots');
}

// DE/ACTIVATION
register_activation_hook( __FILE__, 'xml_sitemap_activate' );
register_deactivation_hook( __FILE__, 'xml_sitemap_deactivate' );

