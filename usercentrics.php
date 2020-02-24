<?php
namespace sv_tracking_manager;

/**
 * @version         1.000
 * @author			straightvisions GmbH
 * @package			sv_tracking_manager
 * @copyright		2019 straightvisions GmbH
 * @link			https://straightvisions.com
 * @since			1.000
 * @license			See license.txt or https://straightvisions.com
 */

class usercentrics extends modules {
	private $consent_IDs		= array(
		// bing
		'sv_tracking_manager_modules_bing_scripts_default'							=> 'Bing Ads',

		// facebook
		'sv_tracking_manager_modules_facebook_scripts_default'						=> 'Facebook Pixel',

		// google_analytics
		'sv_tracking_manager_modules_google_analytics_scripts_ga'					=> 'Google Analytics',
		'sv_tracking_manager_modules_google_analytics_scripts_default'				=> 'Google Analytics',
		'sv_tracking_manager_modules_google_analytics_scripts_events'				=> 'Google Analytics',
		'sv_tracking_manager_modules_google_analytics_scripts_events_scroll'		=> 'Google Analytics',

		// google_optimize
		'sv_tracking_manager_modules_google_optimize_scripts_default'				=> 'Google Optimize',
		'sv_tracking_manager_modules_google_optimize_scripts_anti_flicker'			=> 'Google Optimize',

		// hotjar
		'sv_tracking_manager_modules_hotjar_scripts_default'						=> 'Hotjar',

		// linkedin
		'sv_tracking_manager_modules_linkedin_scripts_default'						=> 'LinkedIn Ads',

		// mouseflow
		'sv_tracking_manager_modules_mouseflow_scripts_default'					=> 'Mouseflow',

		// yahoo
		'sv_tracking_manager_modules_yahoo_scripts_default'						=> 'Yahoo Gemini',
	);

	public function init() {
		// Section Info
		$this->set_section_title( __('Usercentrics', 'sv_tracking_manager' ) )
			->set_section_desc(__( sprintf('%sUsercentrics Login%s', '<a target="_blank" href="https://admin.usercentrics.com/#/">','</a>'), 'sv_tracking_manager' ))
			->set_section_type( 'settings' )
			->load_settings()
			->register_scripts()
			->get_root()->add_section( $this );

		$this->add_service();

		add_action('init', array($this, 'load'));
	}

	protected function load_settings(): usercentrics {
		$this->get_setting('activate')
			->set_title( __( 'Activate', 'sv_tracking_manager' ) )
			->set_description(__('Enable Usercentrics support','sv_tracking_manager'))
			->load_type( 'checkbox' );

		$this->get_setting('id')
			->set_title( __( 'Settings ID', 'sv_tracking_manager' ) )
			->set_description(__('The Usercentrics Settings ID for this site.','sv_tracking_manager'))
			->load_type( 'text' );

		$this->get_setting('activate_shield')
			->set_title( __( 'Activate Privacy Shield', 'sv_tracking_manager' ) )
			->set_description(
				sprintf(
					__('Enable %1$sUsercentrics Privacy Shield%2$s support to ask user before loading embeded content like Youtube or Google Maps.','sv_tracking_manager'),
				'<a href="' . esc_url( 'https://docs.usercentrics.com/#/privacy-shield' ) . '" target="_blank">',
				'</a>'
				)
			)
			->load_type( 'checkbox' );


		return $this;
	}
	public function is_active(): bool{
		// activate not set
		if(!$this->get_setting('activate')->run_type()->get_data()){
			return false;
		}
		// activate not true
		if($this->get_setting('activate')->run_type()->get_data() !== '1'){
			return false;
		}
		// Setting ID not set
		if(!$this->get_setting('id')->run_type()->get_data()){
			return false;
		}
		// Setting ID empty
		if(strlen(trim($this->get_setting('id')->run_type()->get_data())) === 0){
			return false;
		}
		return true;
	}
	public function is_activate_shield(): bool{
		// Setting ID not set
		if(!$this->get_setting('activate_shield')->run_type()->get_data()){
			return false;
		}
		// Setting ID empty
		if(strlen(trim($this->get_setting('activate_shield')->run_type()->get_data())) === 0){
			return false;
		}
		return true;
	}
	public function get_consent_IDs(): array{
		return $this->consent_IDs;
	}
	public function get_consent_ID(string $ID): string{
		return $this->consent_IDs[$ID];
	}
	public function has_consent_ID(string $ID): bool{
		return isset($this->consent_IDs[$ID]) ? true : false;
	}
	public function load(): usercentrics{
		if(!$this->is_active()) {
			return $this;
		}

		add_action( 'wp_head', array($this,'load_cookie_banner'));
		add_filter( 'rocket_minify_excluded_external_js', array($this,'rocket_minify_excluded_external_js') );

		if(!$this->is_activate_shield()){
			return $this;
		}

		$this->get_script('usercentrics_styles')->set_is_enqueued();

		add_action( 'wp_head', array($this,'load_privacy_shield'));
		add_filter( 'embed_oembed_html', array($this,'embed_oembed_html'), 99);

		return $this;
	}
	public function load_cookie_banner(){
		echo '<script src="https://app.usercentrics.eu/latest/main.js" id="pw66MUEa"></script>';

		/* // @todo: allow insert scripts into header
$this->get_script('usercentrics')
	->set_type('js')
	->set_is_enqueued()
	->set_path('https://app.usercentrics.eu/latest/main.js')
	->set_custom_attributes(' id="'.$this->get_setting('id')->run_type()->get_data().'"');
*/
	}
	public function load_privacy_shield(){
		echo '<meta data-privacy-proxy-server="https://privacy-proxy-server.usercentrics.eu">';
		echo '<script src="https://privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js"></script>';

		// @todo: check why 403-response when loading this, seems to not have any style effect
		echo '<script defer src="https://privacy-proxy.usercentrics.eu/latest/uc-block-ui.bundle.js"></script>';


		/* // @todo: allow insert scripts into header
$this->get_script('usercentrics_block')
	->set_type('js')
	->set_is_enqueued()
	->set_path('https://privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js')
	->set_deps(array($this->get_script('usercentrics')->get_handle()));

$this->get_script('usercentrics_block_ui')
	->set_type('js')
	->set_is_enqueued()
	->set_path('https://privacy-proxy.usercentrics.eu/latest/uc-block-ui.bundle.js')
	->set_deps(array($this->get_script('usercentrics_block')->get_handle()));
*/
	}
	protected function register_scripts(): usercentrics{
		// Activate Consent Management in Tracking Manager
		if($this->is_active()) {
			add_filter('sv_tracking_manager_consent_management', function (bool $active) {
				return true;
			});

			add_filter('sv_tracking_manager_data_attributes', function (string $attributes, \sv_core\scripts $script) {
				if($this->has_consent_ID($script->get_handle())){
					return $attributes . ' data-usercentrics="'.$this->get_consent_ID($script->get_handle()).'"';
				}else{
					return $attributes;
				}
			}, 10, 2);
		}

		if($this->is_activate_shield()){
			$this->get_script('usercentrics_styles')
				->set_is_enqueued()
				->set_path('lib/frontend/css/default.css')
				->set_custom_attributes(' id="'.$this->get_setting('id')->run_type()->get_data().'"');
		}

		return $this;
	}
	public function embed_oembed_html(string $output){
		// make sure that oembed content has uc-src as default e.g. when client has noscript addon activated
		$output = str_replace(
			'src',
			'uc-src',
			$output);

		// twitter is cached, but make sure to no load twitter js without consent
		$output = str_replace(
			'async uc-src',
			'type="text/plain" data-usercentrics="Twitter Plugin" src',
			$output
		);

		return $output;
	}
	// never combine external JS
	public function rocket_minify_excluded_external_js($pattern){
		$pattern[] = 'usercentrics';

		return $pattern;
	}
}