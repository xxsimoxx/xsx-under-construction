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

// Sanitize the value of the cookie.
$key = preg_replace('/[^0-9]/', '', sanitize_key(wp_unslash($_REQUEST['preview']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Place the cookie
$cookie_domain = preg_replace('{^https?://}', '', site_url());
setcookie(
	'selective_preview',
	$key,
	0,
	'/',
	$cookie_domain,
	true,
	true
);

wp_safe_redirect(site_url()); 