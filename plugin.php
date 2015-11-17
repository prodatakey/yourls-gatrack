<?php
/*
Plugin Name: Google Analytics Clickthrough Tracking
Plugin URI: http://github.com/prodatakey/yourls-gatrack
Description: One line description of your plugin
Version: 1.0
Author: Josh Perry
LICENSE: MIT
*/
namespace pdk\YourlsGATrack;

$gaid = 'UA-33563207-5';

require __DIR__ . '/vendor/autoload.php';

use \TheIconic\Tracking\GoogleAnalytics\Analytics;
use \Ramsey\Uuid\Uuid;

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Create a client id and cookie
function gen_client_id() {
	$uuid = Uuid::uuid4();
	$cid = $uuid.toString();

	// Put it in a cookie to use in the future
	setcookie('gacid', $cid, time()+60*60*24*365*2, '/', '.prodatakey.com');
	
	return $cid;
}

function get_client_id() {
	// We use gacid for our analytics client id cookie
	if (isset($_COOKIE['gacid'])) {
		$cid = $_COOKIE['gacid'];
	} else {
		$cid = gen_client_id();
	}

	return $cid;
}

function set_campaign_props($url, $analytics) {
	// Set campaign params from the URL
	$query = parse_url($url);
	parse_str($query);

	if(isset($utm_name))
		$analytics->setCampaignName($utm_name);

	if(isset($utm_source))
		$analytics->setCampaignSource($utm_source);

	if(isset($utm_medium))
		$analytics->setCampaignMedium($utm_medium);
}

// Before we send the redirect to the client, notify GA of the click
yourls_add_action('redirect_shorturl', function ($url, $keyword) { 
	// Get a client Id
	$cid = get_client_id();
	
	// Instantiate the Analytics object, true means use HTTPS
	$analytics = new Analytics(true);

	// Build the required hit parameters
	$analytics
		->setProtocolVersion('1')
		->setTrackingId($gaid)
		->setCacheBuster(rand(100000000000,999999999999))
		->setClientId($cid);

	set_campaign_props($url, $analytics);

	// Set the referer url
	if(isset($_SERVER['HTTP_REFERER']))
		$analytics->setDocumentReferer($_SERVER['HTTP_REFERER']);

	// Set optional parameters
	$analytics
		->setDataSource('godot');

	// Send it to GA
	$analytics
		->setAsyncRequest(true)
		->sendPageview();
});
