<?php
/**
 * Security-related functionality.
 *
 * @package wikimedia-contest;
 */

namespace Wikimedia_Contest\Security;

/**
 * Attach all required functionality.
*/
function bootstrap() {
	add_action( 'send_headers', 'send_frame_options_header', 10, 0 );
	add_action( 'send_headers', __NAMESPACE__ . '\\enable_strict_transport_security' );
}

/**
 * Send HSTS header. This forces HTTPS once a browser confirms that HTTPS is
 * supported by the site and there are no certificate errors.
 *
 * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security
 */
function enable_strict_transport_security() {
	header( 'Strict-Transport-Security: max-age=31536000' );
}
