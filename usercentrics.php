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
		'sv_tracking_manager_bing_scripts_default'							=> 'Bing Ads',

		// facebook
		'sv_tracking_manager_facebook_scripts_default'						=> 'Facebook Pixel',

		// google_analytics
		'sv_tracking_manager_google_analytics_scripts_ga'					=> 'Google Analytics',
		'sv_tracking_manager_google_analytics_scripts_default'				=> 'Google Analytics',
		'sv_tracking_manager_google_analytics_scripts_events'				=> 'Google Analytics',
		'sv_tracking_manager_google_analytics_scripts_events_scroll'		=> 'Google Analytics',

		// google_tag_manager
		'sv_tracking_manager_google_tag_manager_scripts_default'			=> 'Google Tag Manager',

		// google_optimize
		'sv_tracking_manager_google_optimize_scripts_default'				=> 'Google Optimize',
		'sv_tracking_manager_google_optimize_scripts_anti_flicker'			=> 'Google Optimize',

		// hotjar
		'sv_tracking_manager_hotjar_scripts_default'						=> 'Hotjar',

		// linkedin
		'sv_tracking_manager_linkedin_scripts_default'						=> 'LinkedIn Ads',

		// mouseflow
		'sv_tracking_manager_mouseflow_scripts_default'					=> 'Mouseflow',

		// yahoo
		'sv_tracking_manager_yahoo_scripts_default'						=> 'Yahoo Gemini',
	);

	public function init() {
		// Section Info
		$this->set_section_title( __('Usercentrics', 'sv_tracking_manager' ) )
			->set_section_desc(__( sprintf('%sUsercentrics Login%s', '<a target="_blank" href="https://admin.usercentrics.com/#/">','</a>'), 'sv_tracking_manager' ))
			->set_section_type( 'settings' )
			->set_section_template_path( $this->get_path( '/lib/backend/tpl/settings.php' ) )
			->load_settings()
			->get_root()->add_section( $this );

		add_action('init', array($this, 'load'));
		add_action('init', array($this, 'register_scripts'));
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
			->set_title( __( 'Activate Smart Data Protector', 'sv_tracking_manager' ) )
			->set_description(
				sprintf(
					__('Enable %1$sUsercentrics Smart Data Protector%2$s support to ask user before loading embeded content like Youtube or Google Maps.','sv_tracking_manager'),
				'<a href="' . esc_url( 'https://usercentrics.com/de/smart-data-protector/' ) . '" target="_blank">',
				'</a>'
				)
			)
			->load_type( 'checkbox' );

		$this->get_setting('api_version')
			->set_title( __( 'API Version', 'sv_tracking_manager' ) )
			->set_description(__('Choose between CMP 1 (stable default), CMP 2 (beta) or CMP 2 + legacy (beta + legacy browser support)','sv_tracking_manager'))
			->set_options(array(
				'cmp_v1'			=> 'CMP v1',
				'cmp_v2'			=> 'CMP v2',
				'cmp_v2_legacy'		=> 'CMP v2 + legacy browser support'
			))
			->set_default_value('cmp_v1')
			->load_type( 'select' );

		// roles are not available before init hook
		add_action('init', function() {
			global $wp_roles;
			$all_roles			= $wp_roles->roles;
			$editable_roles		= apply_filters('editable_roles', $all_roles);
			$roles				= array();

			foreach($editable_roles as $name => $role){
				$roles[$name]		= $role['name'];
			}

			$this->get_setting('roles')
				->set_title( __( 'User Roles', 'sv_tracking_manager' ) )
				->set_description(__('Disable UserCentrics for the User Roles selected','sv_tracking_manager'))
				->load_type( 'checkbox' )
				->set_options($roles);
		});

		return $this;
	}
	public function is_active(): bool{
		// activate not set
		if(!$this->get_setting('activate')->get_data()){
			return false;
		}
		// activate not true
		if($this->get_setting('activate')->get_data() !== '1'){
			return false;
		}
		// Setting ID not set
		if(!$this->get_setting('id')->get_data()){
			return false;
		}
		// Setting ID empty
		if(strlen(trim($this->get_setting('id')->get_data())) === 0){
			return false;
		}
		// maybe roles disabled
		if(!is_admin() && $this->get_setting('roles')->get_data() && is_array($this->get_setting('roles')->get_data())){
			$user = wp_get_current_user();

			foreach($this->get_setting('roles')->get_data() as $name => $status) {
				if($status == '1') {
					if (in_array($name, (array)$user->roles)) {
						return false;
					}
				}
			}
		}
		// not compatible with Divi Builder
		if(isset($_GET['et_fb']) && $_GET['et_fb'] == '1'){
			return false;
		}

		return true;
	}
	public function is_activate_shield(): bool{
		// Setting ID not set
		if(!$this->get_setting('activate_shield')->get_data()){
			return false;
		}
		// Setting ID empty
		if(strlen(trim($this->get_setting('activate_shield')->get_data())) === 0){
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

		add_action( 'wp_head', array($this,'load_cookie_banner'), 1);
		add_filter( 'rocket_minify_excluded_external_js', array($this,'rocket_minify_excluded_external_js') );
		add_filter( 'rocket_exclude_defer_js', array($this,'rocket_exclude_files_defer') );

		if(!$this->is_activate_shield()){
			return $this;
		}

		$this->get_script('usercentrics_styles')->set_is_enqueued();

		add_action( 'wp_head', array($this,'load_privacy_shield'), 2);
		add_filter( 'embed_oembed_html', array($this,'embed_oembed_html'), 99);
		//add_filter( 'the_content', array($this,'the_content'),99 );

		return $this;
	}
	public function load_cookie_banner(){
		if($this->get_setting('api_version')->get_data() == 'cmp_v2'){
			echo '<script id="usercentrics-cmp"  data-settings-id="'.$this->get_setting('id')->get_data().'" src="'.apply_filters('usercentrics-cmp','https://app.usercentrics.eu/browser-ui/latest/bundle.js').'" defer></script>';
		}elseif($this->get_setting('api_version')->get_data() == 'cmp_v2_legacy'){
			echo '<script id="usercentrics-cmp"  data-settings-id="'.$this->get_setting('id')->get_data().'" src="'.apply_filters('usercentrics-cmp','https://app.usercentrics.eu/browser-ui/latest/bundle_legacy.js').'" defer></script>';
		}else{
			echo '<script type="application/javascript" src="'.apply_filters('usercentrics-cmp','https://app.usercentrics.eu/latest/main.js').'" id="'.$this->get_setting('id')->get_data().'"></script>';
		}
	}
	public function load_privacy_shield(){
		echo '<meta data-privacy-proxy-server="https://privacy-proxy-server.usercentrics.eu">';
		echo '<script type="application/javascript" src="'.apply_filters('usercentrics-privacy-shield','https://privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js').'"></script>';
	}
	public function register_scripts(): usercentrics{
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
				->set_custom_attributes(' id="'.$this->get_setting('id')->get_data().'"');
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
	public function the_content(string $output){
		// make sure that oembed content has uc-src as default e.g. when client has noscript addon activated
		$output = str_replace(
			'<iframe src=',
			'<iframe uc-src=',
			$output);

		return $output;
	}
	// never combine external JS
	public function rocket_minify_excluded_external_js($pattern){
		$pattern[] = 'usercentrics';

		return $pattern;
	}
	function rocket_exclude_files_defer( $excluded_files = array() ) {
		$excluded_files[] = 'https://app.usercentrics.eu/latest/main.js';
		$excluded_files[] = 'https://privacy-proxy.usercentrics.eu/latest/uc-block.bundle.js';

		return $excluded_files;
	}
}