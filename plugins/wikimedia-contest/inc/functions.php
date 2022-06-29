<?php
/**
 * Useful functions for Wikimedia Contest plugin.
 */

namespace Wikimedia_Contest;

 /**
 * Sanitize phone number.
 *
 * @param string $phone Input phone number.
 * @return string Sanitized phone number.
 */
function wc_sanitize_phone_number( $phone ) : string {
	return preg_replace( '/[^\d+]/', '', $phone );
}
