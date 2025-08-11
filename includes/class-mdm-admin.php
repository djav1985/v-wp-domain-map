<?php
/**
 * Admin functionality for Multiple Domain Mapping.
 * Handles all admin-facing functionality.
 *
 * @package VONTMNT_mdm
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( '...' );
}

/**
 * Admin class for Multiple Domain Mapping.
 * Handles all admin-facing functionality.
 */
class VONTMNT_MDM_Admin {

	/**
	 * Reference to the main plugin instance.
	 *
	 * @var VONTMNT_MultipleDomainMapping
	 */
	private $plugin;

	/**
	 * Whether save mappings button is disabled.
	 *
	 * @var bool
	 */
	private $saveMappingsButtonDisabled = false;

	/**
	 * Constructor.
	 *
	 * @param VONTMNT_MultipleDomainMapping $plugin The main plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->init_hooks();
	}

	/**
	 * Initialize hooks for admin functionality.
	 */
	private function init_hooks() {
		// Backend.
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles in admin.
	 */
	public function admin_scripts() {
		// custom assets.
		wp_enqueue_style( 'VONTMNT_mdm_adminstyle', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin.css', array(), $this->plugin->getPluginVersion() );
		wp_register_script( 'VONTMNT_mdm_adminscript', plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-accordion' ), $this->plugin->getPluginVersion(), true );
		wp_localize_script(
			'VONTMNT_mdm_adminscript',
			'localizedObj',
			array(
				'removedMessage' => sprintf( '%s "%s"', esc_html__( 'Mapping will be removed permanently as soon as you click', 'VONTMNT_mdm' ), esc_html__( 'Save Mappings', 'VONTMNT_mdm' ) ),
				'undoMessage'    => esc_html__( 'Undo unsaved changes', 'VONTMNT_mdm' ),
				'dismissMessage' => __( 'Dismiss this notice.' ),
			)
		);
		wp_enqueue_script( 'VONTMNT_mdm_adminscript' );
	}

	/**
	 * Generate menu entry.
	 */
	public function add_menu_page() {
		// check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page( 'tools.php', esc_html__( 'Multiple Domain Mapping on single site', 'VONTMNT_mdm' ), esc_html__( 'Multidomain', 'VONTMNT_mdm' ), 'manage_options', 'vontmnt-multidomain-mapping', array( $this, 'output_menu_page' ) );
		$this->register_settings();
	}

	/**
	 * Generate menu page output.
	 */
	public function output_menu_page() {
		// check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Find out active tab.
		$active_tab      = ( isset( $_GET['tab'] ) && 'settings' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'mappings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_tab_name = ( isset( $_GET['tab'] ) && 'settings' === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) ? ucfirst( sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) : esc_html__( 'Mappings', 'VONTMNT_mdm' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		echo '<div class="wrap VONTMNT_mdm_wrap">';

			// page title.
			echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

			// updated notices.
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_settings_error( 'VONTMNT_mdm_messages', 'VONTMNT_mdm_message', sprintf( esc_html__( '%s saved', 'VONTMNT_mdm' ), $active_tab_name ), 'updated' );

			// flush rewrite rules on each update of our settings/mappings, just to be sure...
			flush_rewrite_rules();
		}
			settings_errors( 'VONTMNT_mdm_messages' );

			// page intro.
			printf( '<p>%s <a title="%s" target="_blank" href="https://de.wordpress.org/plugins/multiple--on-single-site/">%s</a> %s</p>', esc_html__( 'With this plugin you can use additional domains and/or subdomains to show specific pages, posts, archives, ... of your site, which is very useful for landingpages. It requires some important settings in your domains DNS entries and your hosting environment, and will not work "out-of-the-box". Please see the', 'VONTMNT_mdm' ), esc_html__( 'WordPress Plugin Repository', 'VONTMNT_mdm' ), esc_html__( 'description in the plugin repository', 'VONTMNT_mdm' ), esc_html__( 'for further information on how to set it up.', 'VONTMNT_mdm' ) );
			printf( '<p>%s <a title="%s" target="_blank" href="https://www.matthias-wagner.at/">%s</a>.</p>', esc_html__( 'If you enjoy this plugin and especially if you use it for commercial projects, please help us maintain support and development with', 'VONTMNT_mdm' ), esc_html__( 'Donations', 'VONTMNT_mdm' ), esc_html__( 'your donation', 'VONTMNT_mdm' ) );

			// tabs.
			echo '<h2 class="nav-tab-wrapper">';
				echo '<a href="?page=vontmnt-multidomain-mapping&amp;tab=mappings" class="nav-tab ' . ( $active_tab === 'mappings' ? 'nav-tab-active ' : '' ) . '">' . esc_html__( 'Mappings', 'VONTMNT_mdm' ) . '</a>';
				echo '<a href="?page=vontmnt-multidomain-mapping&amp;tab=settings" class="nav-tab ' . ( $active_tab === 'settings' ? 'nav-tab-active ' : '' ) . '">' . esc_html__( 'Settings', 'VONTMNT_mdm' ) . '</a>';
			echo '</h2>';

			// main form.
			echo '<form action="options.php" method="post">';

				// inputs based on current tab.
		switch ( $active_tab ) {
			case 'settings':
				{
				add_settings_section(
					'VONTMNT_mdm_section_settings',
					esc_html__( 'Domain mapping settings', 'VONTMNT_mdm' ),
					array( $this, 'section_settings_callback' ),
					plugin_basename( dirname( dirname( __FILE__ ) ) . '/multi-domain-mapping.php' )
				);

				add_settings_field(
					'VONTMNT_mdm_field_settings_phpserver',
					esc_html__( 'PHP Server-Variable:', 'VONTMNT_mdm' ),
					array( $this, 'field_settings_phpserver_callback' ),
					plugin_basename( dirname( dirname( __FILE__ ) ) . '/multi-domain-mapping.php' ),
					'VONTMNT_mdm_section_settings'
				);

				add_settings_field(
					'VONTMNT_mdm_field_settings_compatibilitymode',
					esc_html__( 'Enhanced compatibility mode:', 'VONTMNT_mdm' ),
					array( $this, 'field_settings_compatibilitymode_callback' ),
					plugin_basename( dirname( dirname( __FILE__ ) ) . '/multi-domain-mapping.php' ),
					'VONTMNT_mdm_section_settings'
				);

				do_action( 'VONTMNT_mdma_settings_tab' );

				settings_fields( 'VONTMNT_mdm_settings_group' );
				do_settings_sections( plugin_basename( dirname( dirname( __FILE__ ) ) . '/multi-domain-mapping.php' ) );
				break;
			}
			default:
				{ // default is our mappings tab.

				add_settings_section(
					'VONTMNT_mdm_section_mappings',
					esc_html__( 'Domain mappings', 'VONTMNT_mdm' ),
					array( $this, 'section_mappings_callback' ),
					plugin_basename( dirname( dirname( __FILE__ ) ) . '/multi-domain-mapping.php' )
				);

				add_settings_field(
					'VONTMNT_mdm_field_mappings_uris',
					esc_html__( 'Define your mappings here:', 'VONTMNT_mdm' ),
					array( $this, 'field_mappings_uris_callback' ),
					plugin_basename( dirname( dirname( __FILE__ ) ) . '/multi-domain-mapping.php' ),
					'VONTMNT_mdm_section_mappings'
				);
				settings_fields( 'VONTMNT_mdm_mappings_group' );
				do_settings_sections( plugin_basename( dirname( dirname( __FILE__ ) ) . '/multi-domain-mapping.php' ) );

				break;
			}
		}

				// dynamic submit button.
		if ( $active_tab !== 'mappings' || $this->saveMappingsButtonDisabled === false ) {
			submit_button( sprintf( esc_html__( 'Save %s', 'VONTMNT_mdm' ), $active_tab_name ) );
		}

			echo '</form>';
		echo '</div>';
	}

	/**
	 * Register settings.
	 */
	private function register_settings() {
		register_setting(
			'VONTMNT_mdm_settings_group',
			'VONTMNT_mdm_settings',
			array(
				'sanitize_callback' => array( $this->plugin, 'sanitize_settings_group' ),
				'show_in_rest'      => true,
			)
		);
		register_setting(
			'VONTMNT_mdm_mappings_group',
			'VONTMNT_mdm_mappings',
			array(
				'sanitize_callback' => array( $this->plugin, 'sanitize_mappings_group' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Generate options fields output for the settings tab.
	 */
	public function section_settings_callback() {
		echo esc_html__( 'Here you find some additional settings which should not be necessary to change in most use cases.', 'VONTMNT_mdm' );
	}

	/**
	 * Field callback for PHP server setting.
	 */
	public function field_settings_phpserver_callback() {
		$options = $this->plugin->getSettings();
		if ( empty( $options ) ) {
			$options = array();
		}

		$options['php_server'] = isset( $options['php_server'] ) ? $options['php_server'] : 'SERVER_NAME';

		printf(
			'<p>%s <a target="_blank" href="https://wordpress.org/support/topic/server_name-instead-of-http_host/">%s</a>.</p>',
			esc_html__( 'In some cases it is necessary to change the used variable, like reported', 'VONTMNT_mdm' ),
			esc_html__( 'in this support-thread', 'VONTMNT_mdm' )
		);
		echo '<p><label><input type="radio" name="VONTMNT_mdm_settings[php_server]" value="SERVER_NAME" ' . checked( 'SERVER_NAME', $options['php_server'], false ) . ' />$_SERVER["SERVER_NAME"] (' . esc_html__( 'Default', 'VONTMNT_mdm' ) . ')</label></p>';
		echo '<p><label><input type="radio" name="VONTMNT_mdm_settings[php_server]" value="HTTP_HOST" ' . checked( 'HTTP_HOST', $options['php_server'], false ) . ' />$_SERVER["HTTP_HOST"] (' . esc_html__( 'recommended for nginx', 'VONTMNT_mdm' ) . ')</label></p>';
	}

	/**
	 * Field callback for compatibility mode setting.
	 */
	public function field_settings_compatibilitymode_callback() {
		$options = $this->plugin->getSettings();
		if ( empty( $options ) ) {
			$options = array();
		}

		$options['compatibilitymode'] = isset( $options['compatibilitymode'] ) ? $options['compatibilitymode'] : 0;

		printf(
			'<p>%s</p>',
			esc_html__( 'This will disable the replacement of URIs inside wp-admin. This can be useful if, for example, your page builder fails to load mapped pages.', 'VONTMNT_mdm' )
		);
		echo '<p><label><input type="radio" name="VONTMNT_mdm_settings[compatibilitymode]" value="0" ' . checked( '0', $options['compatibilitymode'], false ) . ' />Off (' . esc_html__( 'Default', 'VONTMNT_mdm' ) . ')</label></p>';
		echo '<p><label><input type="radio" name="VONTMNT_mdm_settings[compatibilitymode]" value="1" ' . checked( '1', $options['compatibilitymode'], false ) . ' />On</label></p>';
	}

	/**
	 * Generate options fields output for the mappings tab.
	 */
	public function section_mappings_callback() {
		echo wp_kses_post( __( '<b>In the first (left) field</b>, enter your additional (sub-)domain which should show the content from now on. http/s and www/non-www will be detected automatically, so only one line per domain is necessary.<br /><b>In the second (right) field</b>, enter the path to this page, post, archive, ... Please note that all descendant URIs will be mapped as well.', 'VONTMNT_mdm' ) );
	}

	/**
	 * Field callback for mappings URIs.
	 */
	public function field_mappings_uris_callback() {
		$options = $this->plugin->getMappings();
		if ( empty( $options ) ) {
			$options = array();
		}

		echo '<section class="VONTMNT_mdm_mappings">';
		if ( isset( $options['mappings'] ) && ! empty( $options['mappings'] ) ) {
			$cnt = 0;
			foreach ( $options['mappings'] as $mapping ) {
				echo '<article class="' . esc_attr( apply_filters( 'VONTMNT_mdmf_mapping_class', 'VONTMNT_mdm_mapping' ) ) . '">';
					echo '<div class="VONTMNT_mdm_mapping_header">';
						echo '<div><div class="VONTMNT_mdm_input_wrap"><span class="VONTMNT_mdm_input_prefix">http[s]://</span><input type="text" name="VONTMNT_mdm_mappings[cnt_' . esc_attr( $cnt ) . '][domain]" value="' . esc_attr( $mapping['domain'] ) . '" /></div></div>';
						echo '<div class="VONTMNT_mdm_mapping_arrow">&raquo;</div>';
						echo '<div><div class="VONTMNT_mdm_input_wrap"><span class="VONTMNT_mdm_input_prefix">' . esc_html( get_home_url() ) . '</span><input type="text" name="VONTMNT_mdm_mappings[cnt_' . esc_attr( $cnt ) . '][path]" value="' . esc_attr( $mapping['path'] ) . '" /></div></div>';
					echo '</div>';
					echo '<div class="VONTMNT_mdm_mapping_body">';
						echo '<span class="VONTMNT_mdm_mapping_body_icon VONTMNT_mdm_delete_mapping"><a href="#" title="' . esc_attr__( 'Remove mapping', 'VONTMNT_mdm' ) . '">' . esc_html__( 'Remove mapping', 'VONTMNT_mdm' ) . ' <i>&cross;</i></a></span>';
						echo wp_kses_post( do_action( 'VONTMNT_mdma_after_mapping_body', $cnt, $mapping ) );
					echo '</div>';
				echo '</article>';
				++$cnt;
			}
		}
		echo '</section>';

		echo '<section class="VONTMNT_mdm_new_mapping">';
			echo '<article class="' . esc_attr( apply_filters( 'VONTMNT_mdmf_mapping_class', 'VONTMNT_mdm_mapping VONTMNT_mdm_mapping_new' ) ) . '">';
				echo '<div class="VONTMNT_mdm_mapping_header">';
					echo '<div><div class="VONTMNT_mdm_input_wrap"><span class="VONTMNT_mdm_input_prefix">http[s]://</span><input type="text" name="VONTMNT_mdm_mappings[cnt_new][domain]" placeholder="[www.]newdomain.com" /></div><div class="VONTMNT_mdm_input_hint">' . esc_html__( 'Enter the domain you want to map.', 'VONTMNT_mdm' ) . '</div></div>';
					echo '<div class="VONTMNT_mdm_mapping_arrow">&raquo;</div>';
					echo '<div><div class="VONTMNT_mdm_input_wrap"><span class="VONTMNT_mdm_input_prefix">' . esc_html( get_home_url() ) . '</span><input type="text" name="VONTMNT_mdm_mappings[cnt_new][path]" placeholder="/mappedpage" /></div><div class="VONTMNT_mdm_input_hint">' . esc_html__( 'Enter the path to the desired root for this mapping', 'VONTMNT_mdm' ) . '</div></div>';
				echo '</div>';
				echo '<div class="VONTMNT_mdm_mapping_body">';
					echo wp_kses_post( do_action( 'VONTMNT_mdma_after_mapping_body', 'new', false ) );
				echo '</div>';
			echo '</article>';
		echo '</section>';

		// calculate and maybe show warning for higher max_input_vars needed.
		$numberOfSettings = 3; // this must be changed when additional input fields emerge.
		if ( $cnt >= ( intval( ini_get( 'max_input_vars' ) ) / $numberOfSettings - 100 ) ) {
			$this->saveMappingsButtonDisabled = true;
			echo '<section class="notice notice-error">';
				echo '<p>';
					printf( esc_html__( 'WATCH OUT! Your server is configured to allow a maximum number of %1$s as %2$s. Each of the currently defined %3$s mapping(s) requires %4$s of these input vars when saving this site (%5$s). Depending on your other plugins, some dozens of these input vars will also be used by WordPress itself. If you want to save more mappings, you will need to configure your server for a higher value of %6$s.', 'VONTMNT_mdm' ), esc_html( ini_get( 'max_input_vars' ) ), '<em>max_input_vars</em>', esc_html( $cnt ), esc_html( $numberOfSettings ), esc_html( $cnt . ' x ' . $numberOfSettings . ' = ' . ( $cnt * $numberOfSettings ) ), '<em>max_input_vars</em>' );
					echo ' <a href="https://duckduckgo.com/?q=php+increase+max_input_vars" target="_blank">' . esc_html__( 'Find out how to fix this issue (external link)', 'VONTMNT_mdm' ) . '</a>';
				echo '</p>';
				echo '<p>';
					echo esc_html__( 'Therefore the button to save mappings has been removed as it could happen that you lose some of your mappings when saving with this current configuration.', 'VONTMNT_mdm' );
				echo '</p>';
			echo '</section>';
		}
	}

	/**
	 * Function to show additional input fields in mapping body.
	 *
	 * @param int|string $cnt The mapping counter.
	 * @param array|bool $mapping The mapping array.
	 */
	public function render_advanced_mapping_inputs( $cnt, $mapping ) {
		if ( $cnt === 'new' ) {
			return;
		}

		echo '<div class="VONTMNT_mdm_mapping_additional_input">';
			echo '<p class="VONTMNT_mdm_mapping_additional_input_header">' . esc_html__( 'Custom html &lt;head&gt;-Code to display only on this mapped domain', 'VONTMNT_mdm' ) . '</p>';
			echo '<textarea name="VONTMNT_mdm_mappings[cnt_' . esc_attr( $cnt ) . '][customheadcode]" placeholder="' . esc_attr__( 'e.g. &lt;meta name=&#34;google-site-verification&#34; content=&#34;…&#34; /&gt;', 'VONTMNT_mdm' ) . '">' . esc_textarea( $mapping['customheadcode'] ) . '</textarea>';
		echo '</div>';
	}
}