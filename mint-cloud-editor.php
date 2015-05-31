<?php
/**
 * Plugin Name: Mint Cloud Editor
 * Plugin URI: https://github.com/mintindeed/mint-cloud-editor
 * Description: Replaces the default WordPress post editor with Google Docs.
 * Version: 0.1
 * Author: Gabriel Koen
 * License: GPLv2
 */

if ( is_admin() ) {
	if ( ! class_exists( '\Mint\Singleton' ) ) {
		include __DIR__ . '/class-mint-singleton.php';
	}
	include __DIR__ . '/class-mint-cloud-editor.php';
	\Mint\Editor\Google\Doc::get_instance();
}

//EOF