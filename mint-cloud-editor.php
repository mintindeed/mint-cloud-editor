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

if ( is_admin() ) {
	if ( ! class_exists( '\Mint\Singleton' ) ) {
		include __DIR__ . '/class-mint-singleton.php';
	}
	include __DIR__ . '/class-mint-cloud-editor.php';
	\Mint\Cloud\Editor\Google\Doc::get_instance();
}

//EOF