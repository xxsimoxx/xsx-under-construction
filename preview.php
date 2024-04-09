<?php

// Load CP.
require_once('../../../wp-load.php');

/*
Here we don't check for nonces because this link is intended
to be accessed directly.
*/

// Delete the cookie if there is no key.
if (!isset($_REQUEST['preview'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (isset($_COOKIE['selective_preview'])) {
		setcookie('selective_preview', '', time() - 3600);
	}
	exit;
}

// Sanitize the value of the cookie and prepare.
$key = preg_replace('/[^0-9]/', '', sanitize_key(wp_unslash($_REQUEST['preview']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$url = parse_url(site_url()); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

// Place the cookie
setcookie(
	'selective_preview',
	$key,
	0,
	'/',
	$url['host'],
	true,
	true
);

wp_safe_redirect(site_url());
