<?php
/**
 * Internatialization-related functionality for Wikimedia Contest Plugin.
 *
 * @package wikimedia-contest
 */

namespace Wikimedia_Contest\Languages;

/**
 * Bootstrap all internationalization functionality.
 */
function bootstrap() {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\languages_setup' );
}

/**
 * Set up the Wikimedia Contest languages.
 *
 * @return void
 */
function languages_setup() {
	load_plugin_textdomain( 'wikimedia-contest' );
	load_plugin_textdomain( 'wikimedia-contest-admin' );
}
