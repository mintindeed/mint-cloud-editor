<?php
/*
Plugin Name: Mint Cloud Editor
Plugin URI: https://github.com/mintindeed/mint-cloud-editor
Description: Replaces the default WordPress post editor with Google Docs.
Version: 0.1
Author: Gabriel Koen
Text Domain: mint-cloud-editor
Domain Path:  /languages
License: GPLv2
*/

/*
 * Defines
 */
define( 'MCE_BASE_PATH', __FILE__ );

require __DIR__ . '/vendor/autoload.php';

if ( is_admin() ) {
	\Mint\Cloud\Editor\Google\Auth::get_instance();
}

//EOF