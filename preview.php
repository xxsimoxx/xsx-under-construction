<?php

// Check that everything is in place.
if (!isset($_REQUEST['preview']) || !isset($_REQUEST['redirect']) || !isset($_SERVER['REQUEST_SCHEME']) || !isset($_SERVER['SERVER_NAME'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (isset($_COOKIE['selective_preview'])) {
		setcookie('selective_preview', '', time() - 3600);
	}
	exit;
}

// Sanitize the value og the cookie.
$value = preg_replace('/[^0-9]/', '', $_REQUEST['preview']); //phpcs:ignore

// Get where to redirect after the cookie is set and validate it.
if (!isset($_REQUEST['redirect'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	exit;
}
if (preg_match('/^'.stripslashes($_SERVER['REQUEST_SCHEME']).$_SERVER['SERVER_NAME'].'/', $_REQUEST['redirect'])) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	exit;
}

setcookie(
	'selective_preview',
	$value,
	0,
	'/',
	$_SERVER['SERVER_NAME'], //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	true,
	true
);

header('location: '.$_REQUEST['redirect'], true, 302); //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

