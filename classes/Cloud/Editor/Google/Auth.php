<?php
namespace Mint\Cloud\Editor\Google;

use \Mint as Mint;

class Auth extends Mint\Singleton {
	const options_page = 'options-general.php?page=mint_cloud_editor';

	/**
	 * Plugin initializer
	 * Called once, the first time the singleton instance is retrieved.
	 * Essentially a constructor for singleton objects, but allows cleaner code by separating out object initialization from singleton pattern cruft.
	 */
	protected function _init() {
		add_action( 'admin_notices', array( $this, 'settings_notice' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Helper for loading the plugin's textdomain
	 * The textdomain is used for translation.
	 */
	public function load_textdomain() {
		$textdomain_path = dirname( plugin_basename( MCE_BASE_PATH ) ) . '/languages/';
		load_plugin_textdomain( 'mint-cloud-editor', false, $textdomain_path );
	}

	/**
	 * Shows admin notice letting users know that MCE needs to be configured
	 */
	public function settings_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_option( 'mce_google_client_id' ) && get_option( 'mce_google_client_secret' ) ) {
			return;
		}

		$settings_notice = sprintf( __( 'Mint Cloud Editor requires you to <a href="%1$s">configure it</a>.', 'mint-cloud-editor' ),
			admin_url( self::options_page )
		);

		echo '<div class="update-nag"><p>' . $settings_notice . '</p></div>';
	}

}

//EOF