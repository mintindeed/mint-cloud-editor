<?php
namespace Mint\Cloud\Editor\Google;

use \Mint as Mint;

class Auth extends Mint\Singleton {
	const version = '1.0';
	const menu_slug = 'mint_cloud_editor';
	const option_name = 'mce_settings';
	public $options_page = null;
	public $options = array();
	protected $default_options = array(
		'google_client_id' => '',
		'google_client_secret' => '',
		'google_enable' => 0,
	);

	/**
	 * Plugin initializer
	 * Called once, the first time the singleton instance is retrieved.
	 * Essentially a constructor for singleton objects, but allows cleaner code by separating out object initialization from singleton pattern cruft.
	 */
	protected function _init() {
		$this->options_page = admin_url( 'options-general.php?page=' . self::menu_slug );
		$options = get_option( self::option_name );
		$this->options = wp_parse_args( $options, $this->default_options );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices', array( $this, 'settings_notice' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'load-settings_page_mint_cloud_editor', array( $this, 'authorize') );
	}

	public function authorize() {
		if ( ( ! isset($_REQUEST['mce-authorize']) || $_REQUEST['mce-authorize'] !== 'google' ) || ( ! isset($_GET['code']) || ! isset($_GET['state']) ) ) {
			return;
		}

		$provider = new \League\OAuth2\Client\Provider\Google([
			'clientId'      => $this->options['google_client_id'],
			'clientSecret'  => $this->options['google_client_secret'],
			'redirectUri'   => $this->options_page,
			'scopes'        => [ 'https://www.googleapis.com/auth/drive.file', 'https://www.googleapis.com/auth/drive.install' ],
		]);

		if (!isset($_GET['code'])) {

			// If we don't have an authorization code then get one
			$authUrl = $provider->getAuthorizationUrl();
			$_SESSION['oauth2state'] = $provider->state;
			header('Location: '.$authUrl);
			exit;

		// Check given state against previously stored one to mitigate CSRF attack
		} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

			unset($_SESSION['oauth2state']);
			exit('Invalid state');

		} else {

			// Try to get an access token (using the authorization code grant)
			$token = $provider->getAccessToken('authorization_code', [
				'code' => $_GET['code']
			]);

			// Optional: Now you have a token you can look up a users profile data
			try {

				// We got an access token, let's now get the user's details
				$userDetails = $provider->getUserDetails($token);

				// Use these details to create a new profile
				printf('Hello %s!', $userDetails->firstName);

			} catch (Exception $e) {

				// Failed to get user details
				exit('Oh dear...');
			}

			// Use this to interact with an API on the users behalf
			echo $token->accessToken;

			// Use this to get a new access token if the old one expires
			echo $token->refreshToken;

			// Number of seconds until the access token will expire, and need refreshing
			echo $token->expires;
		}

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

		if ( $this->options['google_client_id'] && $this->options['google_client_secret'] ) {
			return;
		}

		// Don't show the nag on the settings screen
		$current_screen = get_current_screen();
		if ( 'settings_page_' . self::menu_slug == $current_screen->id ) {
			return;
		}

		$settings_notice = sprintf( __( 'Mint Cloud Editor requires you to <a href="%1$s">configure it</a>.', 'mint-cloud-editor' ),
			$this->options_page
		);

		echo '<div class="update-nag"><p>' . $settings_notice . '</p></div>';
	}

	public function add_admin_menu() {
		$hook = add_options_page( 'Mint Cloud Editor', 'Mint Cloud Editor', 'manage_options', self::menu_slug, array( $this, 'options_page' ) );
	}

	public function settings_init() {

		register_setting( 'mce-settings-group', self::option_name, array( $this, 'validate_settings' ) );

		add_settings_section(
			'mce-google-section',
			__( 'Google Drive', 'mint-cloud-editor' ),
			array( $this, 'google_section_callback' ),
			'mce-settings-group'
		);

		add_settings_field(
			'mce_text_field_google_client_id',
			__( 'Client ID', 'mint-cloud-editor' ),
			array( $this, 'text_field_google_client_id_render' ),
			'mce-settings-group',
			'mce-google-section'
		);

		add_settings_field(
			'mce_text_field_google_client_secret',
			__( 'Client Secret', 'mint-cloud-editor' ),
			array( $this, 'text_field_google_client_secret_render' ),
			'mce-settings-group',
			'mce-google-section'
		);

		add_settings_field(
			'radio_field_google_enable',
			__( 'Turn on Google Docs editor', 'mint-cloud-editor' ),
			array( $this, 'radio_field_google_enable_render' ),
			'mce-settings-group',
			'mce-google-section'
		);

		add_settings_field(
			'button_field_google_authorize',
			__( 'Authorize', 'mint-cloud-editor' ),
			array( $this, 'button_field_google_authorize' ),
			'mce-settings-group',
			'mce-google-section'
		);

	}

	public function validate_settings( $options ) {
		$options = array_map( 'trim', $options );
		$original_options = $options;

		$options['google_client_id'] = filter_var( trim( $options['google_client_id'] ), FILTER_SANITIZE_STRING );

		$options['google_client_secret'] = filter_var( trim( $options['google_client_secret'] ), FILTER_SANITIZE_STRING );

		$options['google_enable'] = (int)$options['google_enable'];

		if ( 1 == $this->options['google_enable'] && 0 == $options['google_enable'] ) {
			add_settings_error( self::option_name, 'google_enable', 'Google Docs editor was turned off.', 'updated' );
		}
		else if ( 0 == $this->options['google_enable'] && 1 == $options['google_enable'] ) {
			add_settings_error( self::option_name, 'google_enable', 'Google Docs editor was turned on.', 'updated' );
		}

		if ( 1 == $this->options['google_enable'] && ( $options['google_client_id'] != $original_options['google_client_id'] || $options['google_client_id'] != $original_options['google_client_id'] ) ) {
			$_REQUEST['mce-authorize'] = 'google';
			$this->authorize();
		}

		return $options;
	}


	public function text_field_google_client_id_render() {

		?>
		<input type='text' name='mce_settings[google_client_id]' value='<?php echo $this->options['google_client_id']; ?>'>
		<?php

	}


	public function text_field_google_client_secret_render() {

		?>
		<input type='text' name='mce_settings[google_client_secret]' value='<?php echo $this->options['google_client_secret']; ?>'>
		<?php

	}


	public function radio_field_google_enable_render() {

		?>
		<label><input type='radio' name='mce_settings[google_enable]' <?php checked( $this->options['google_enable'], 1 ); ?> value='1'>&nbsp;On</label><br />
		<label><input type='radio' name='mce_settings[google_enable]' <?php checked( $this->options['google_enable'], 0 ); ?> value='0'>&nbsp;Off</label>
		<?php

	}

	public function button_field_google_authorize() {
		$auth_url = add_query_arg( array( 'mce-authorize' => 'google' ), $this->options_page );
		?>
		<a id="mce-google-oauth-authorize" class="add-new-h2" href="<?php echo esc_url( $auth_url ); ?>">Authorize</a>
		<?php

	}

	public function google_section_callback() {

		echo __( 'Use Google Drive to power your editor.', 'mint-cloud-editor' );

	}


	public function options_page() {

		?>
		<form action='<?php echo admin_url( 'options.php' ); ?>' method='post'>

			<h2>Mint Cloud Editor</h2>

			<?php
			settings_fields( 'mce-settings-group' );
			do_settings_sections( 'mce-settings-group' );
			submit_button();
			?>

		</form>
		<?php

	}

}

//EOF