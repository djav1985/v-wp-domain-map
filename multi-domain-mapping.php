<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Multiple Domain Mapping
 * Plugin URI:  https://wordpress.org/plugins/multiple--on-single-site/
 * Description: Show specific posts, pages, ... within their own, additional domains. Useful for SEO: different domains for landingpages.
 * Version:     1.1.1
 * Author:      Matthias Wagner - VONTMNTmedia
 * Author URI:  https://www.matthias-wagner.at
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: VONTMNT_mdm
 * Domain Path: /languages
 *
 * @package VONTMNT_mdm
 *
 * Multiple Domain Mapping  is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Multiple Domain Mapping  is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Multiple Domain Mapping . If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( '...' );
}
// Support for older php versions.
if ( ! defined( 'PHP_INT_MIN' ) ) {
	define( 'PHP_INT_MIN', -2147483648 );
}

// Load our classes.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vontmnt-mdm-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vontmnt-mdm-core.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-vontmnt-mdm-replacer.php';

if ( ! class_exists( 'VONTMNT_MultipleDomainMapping' ) ) {
	/**
	 * Multiple Domain Mapping plugin main class.
	 */
	class VONTMNT_MultipleDomainMapping {

		/**
		 * The unique instance of the plugin.
		 *
		 * @var VONTMNT_MultipleDomainMapping
		 */
		private static $instance;

		/**
		 * Core functionality instance.
		 *
		 * @var VONTMNT_MDM_Core
		 */
		private $core;

		/**
		 * Admin functionality instance.
		 *
		 * @var VONTMNT_MDM_Admin
		 */
		private $admin;

		/**
		 * Replacer functionality instance.
		 *
		 * @var VONTMNT_MDM_Replacer
		 */
		private $replacer;

		/**
		 * Gets an instance of our plugin.
		 *
		 * @return VONTMNT_MultipleDomainMapping
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		// Variables.
		/**
		 * Mappings array.
		 *
		 * @var array|false
		 */
		private $mappings = false;
		/**
		 * Settings array.
		 *
		 * @var array|false
		 */
		private $settings = false;
		/**
		 * Original request URI.
		 *
		 * @var string|false
		 */
		private $original_request_uri = false;
		/**
		 * Current URI.
		 *
		 * @var string|false
		 */
		private $current_uri = false;
		/**
		 * Current mapping configuration.
		 *
		 * @var array
		 */
		private $current_mapping = array(
			'match'  => false,
			'factor' => PHP_INT_MIN,
		);
		/**
		 * Whether save mappings button is disabled.
		 *
		 * @var bool
		 */
		private $save_mappings_button_disabled = false;
		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		private $plugin_version = '1.1.1';

		/**
		 * Constructor.
		 */
		private function __construct() {

			// Perform database update check.
			require_once plugin_dir_path( __FILE__ ) . 'includes/upgrades/v-1-0.php';

			// Retrieve options.
			$this->set_mappings( get_option( 'VONTMNT_mdm_mappings' ) );
			$this->set_settings( get_option( 'VONTMNT_mdm_settings' ) );

			// Initialize components.
			$this->core     = new VONTMNT_MDM_Core( $this );
			$this->admin    = new VONTMNT_MDM_Admin( $this );
			$this->replacer = new VONTMNT_MDM_Replacer( $this );

			// Backend.
			add_action( 'plugins_loaded', array( $this, 'set_textdomain' ) );

			// Set current uri.
			$this->set_current_uri( $_SERVER[ ( ! empty( $this->get_settings() ) && isset( $this->get_settings()['php_server'] ) ) ? $this->get_settings()['php_server'] : 'SERVER_NAME' ] . $_SERVER['REQUEST_URI'] );

			// Hook some stuff into our own actions.
			add_action( 'plugins_loaded', array( $this, 'hook_mdm_action' ), 20 );

			// HTML head.
			add_action( 'wp_head', array( $this, 'output_custom_head_code' ), 20 );
		}

		/**
		 * Setters/getters.
		 */

		/**
		 * Set mappings.
		 *
		 * @param array|false $mappings The mappings array.
		 */
		public function set_mappings( $mappings ) {
			$this->mappings = $mappings;
		}

		/**
		 * Get mappings.
		 *
		 * @return array|false
		 */
		public function get_mappings() {
			return $this->mappings;
		}

		/**
		 * Set settings.
		 *
		 * @param array|false $settings The settings array.
		 */
		public function set_settings( $settings ) {
			$this->settings = $settings;
		}

		/**
		 * Get settings.
		 *
		 * @return array|false
		 */
		public function get_settings() {
			return $this->settings;
		}

		/**
		 * Set current URI.
		 *
		 * @param string $uri The URI to set.
		 */
		public function set_current_uri( $uri ) {
			$this->current_uri = trailingslashit( $uri );
		}

		/**
		 * Get current URI.
		 *
		 * @return string|false
		 */
		public function get_current_uri() {
			return $this->current_uri;
		}

		/**
		 * Set current mapping.
		 *
		 * @param array $mapping The mapping array.
		 */
		public function set_current_mapping( $mapping ) {
			$this->current_mapping = $mapping;
		}

		/**
		 * Get current mapping.
		 *
		 * @return array
		 */
		public function get_current_mapping() {
			return $this->current_mapping;
		}

		/**
		 * Set original request URI.
		 *
		 * @param string $uri The URI to set.
		 */
		public function set_original_request_uri( $uri ) {
			$this->original_request_uri = $uri;
		}

		/**
		 * Get original request URI.
		 *
		 * @return string|false
		 */
		public function get_original_request_uri() {
			return $this->original_request_uri;
		}

		/**
		 * Get original URI.
		 *
		 * @return string
		 */
		public function get_original_uri() {
			global $wp;
			return home_url( $wp->request );
		}

		/**
		 * Get plugin version.
		 *
		 * @return string
		 */
		public function get_plugin_version() {
			return $this->plugin_version;
		}

		/**
		 * Get core instance.
		 *
		 * @return VONTMNT_MDM_Core
		 */
		public function get_core() {
			return $this->core;
		}

		/**
		 * Get admin instance.
		 *
		 * @return VONTMNT_MDM_Admin
		 */
		public function get_admin() {
			return $this->admin;
		}

		/**
		 * Get replacer instance.
		 *
		 * @return VONTMNT_MDM_Replacer
		 */
		public function get_replacer() {
			return $this->replacer;
		}

		/**
		 * Set textdomain.
		 */
		public function set_textdomain() {
			load_plugin_textdomain( 'VONTMNT_mdm', false, dirname( plugin_basename( plugin_basename( __FILE__ ) ) ) . '/languages/' );
		}

		/**
		 * Sanitize options fields input.
		 *
		 * @param array $options The options array.
		 * @return array
		 */
		public function sanitize_settings_group( $options ) {
			if ( empty( $options ) ) {
				return $options;
			}

			// be sure that only a correct server-value will be saved.
			$options['php_server'] = ( isset( $options['php_server'] ) && ( 'SERVER_NAME' === $options['php_server'] || 'HTTP_HOST' === $options['php_server'] ) ) ? $options['php_server'] : 'SERVER_NAME';

			return apply_filters( 'vontmnt_mdmf_save_settings', $options );
		}

		/**
		 * Sanitize mappings options before saving.
		 *
		 * @param array $options Raw options from the screen.
		 * @return array Sanitized options.
		 */
		public function sanitize_mappings_group( $options ) {
			// do nothing on empty input.
			if ( empty( $options ) ) {
				return $options;
			}

			// prepare mappings array.
			$mappings = array();

			foreach ( $options as $key => $val ) {
				// search for mappings and prepare them for database.
				if ( false !== stripos( $key, 'cnt_' ) ) {

					// only save not empty inputs.
					$domain = str_ireplace( ']', '', str_ireplace( '[', '', trim( trim( $val['domain'] ), '/' ) ) );
					$path   = trim( trim( isset( $val['path'] ) ? $val['path'] : '' ), '/' );
					if ( '' !== $domain ) {

						// validate inputs.
						$parsed_domain = wp_parse_url( $domain );
						$parsed_path   = wp_parse_url( $path );
						if ( false !== $parsed_domain && false !== $parsed_path ) {

							// if we get only the host-representation we temporary add a protocol, so we can use the benefit from parse_url to strip the query.
							// note: this will also be run for each already saved mapping, since we strip the protocol on save...
							if ( ! isset( $parsed_domain['host'] ) ) {
								$parsed_domain = wp_parse_url( 'dummyprotocol://' . $domain );
							}

							// save only host name (and path, if provided) with stripped slashes.
							$trimmed_domain_path = trim( trim( ( isset( $parsed_domain['path'] ) ? $parsed_domain['path'] : '' ) ), '/' );
							$val['domain']       = trim( trim( isset( $parsed_domain['host'] ) ? $parsed_domain['host'] : '' ), '/' ) . ( ! empty( $trimmed_domain_path ) ? '/' . $trimmed_domain_path : '' );

							// save path with leading slash.
							$val['path'] = '/' . $path;

							// iterate over existing mappings and check, if this path has already been used.
							$save_mapping = true;
							foreach ( $mappings as $existing_mapping ) {
								if ( $existing_mapping['path'] === $val['path'] ) {
									$save_mapping = false;
								}
								if ( str_ireplace( 'www.', '', $existing_mapping['domain'] ) === str_ireplace( 'www.', '', $val['domain'] ) ) {
									$save_mapping = false;
								}
							}

							// save html-head-code encoded.
							if ( ! empty( $val['customheadcode'] ) ) {
								$val['customheadcode'] = htmlentities( $val['customheadcode'] );
							}

							// only allow integers (statuscode) for redirection.
							if ( ! empty( $val['redirection'] ) ) {
								$val['redirection'] = intval( $val['redirection'] );
							}

							if ( $save_mapping ) {
								// mapping should be saved and is filtered before.
								// use domain as index, so we do not have any duplicates -> this index will never be used or stored, but we convert it to md5 so it can not be confusing later.
								$mappings[ md5( $val['domain'] ) ] = apply_filters( 'vontmnt_mdmf_save_mapping', $val );
							} elseif ( function_exists( 'add_settings_error' ) ) {
								// check for existence, since this may be called in an upgrade process earlier, when this is not available yet.
								add_settings_error( 'VONTMNT_mdm_messages', 'VONTMNT_mdm_error_code', esc_html__( 'At least one mapping with duplicate domain or path has been dropped.', 'VONTMNT_mdm' ), 'error' );
							}
						} elseif ( function_exists( 'add_settings_error' ) ) {
							// check for existence, since this may be called in an upgrade process earlier, when this is not available yet.
							add_settings_error( 'VONTMNT_mdm_messages', 'VONTMNT_mdm_error_code', esc_html__( 'At least one mapping with bad URL format has been dropped.', 'VONTMNT_mdm' ), 'error' );
						}
						// if we have only one input filled.
					} elseif ( ! ( '' === $val['domain'] && '' === $val['path'] ) && function_exists( 'add_settings_error' ) ) {
						// check for existence, since this may be called in an upgrade process earlier, when this is not available yet.
						add_settings_error( 'VONTMNT_mdm_messages', 'VONTMNT_mdm_error_code', esc_html__( 'At least one mapping with only one input filled out has been dropped.', 'VONTMNT_mdm' ), 'error' );
					}
					// remove original mapping (cnt_) from options array.
					unset( $options[ $key ] );
				}
			}

			// sort mappings so they are ordered nicely after each change.
			usort( $mappings, array( $this, 'mappings_sort_helper' ) );

			// add filtered and sorted mappings to options array.
			if ( ! empty( $mappings ) ) {
				$options['mappings'] = $mappings;
			}

			return apply_filters( 'vontmnt_mdmf_save_mappings', $options );
		}

		/**
		 * Sort helper for mappings.
		 *
		 * @param array $a First item to compare.
		 * @param array $b Second item to compare.
		 * @return int
		 */
		private function mappings_sort_helper( $a, $b ) {
			return strcmp( $a[ apply_filters( 'vontmnt_mdmf_mapping_sort', 'domain' ) ], $b[ apply_filters( 'vontmnt_mdmf_mapping_sort', 'domain' ) ] );
		}

		/**
		 * Hook into some of our own defined actions.
		 */
		public function hook_mdm_action() {
			add_action( 'vontmnt_mdma_after_mapping_body', array( $this->admin, 'render_advanced_mapping_inputs' ), 10, 2 );
		}

		/**
		 * Check if custom head code is defined for this mapping and output it with html entities decoded, if so.
		 */
		public function output_custom_head_code() {
			if ( ! empty( $this->get_current_mapping()['match'] ) ) {
				if ( ! empty( $this->get_current_mapping()['match']['customheadcode'] ) ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped.
					echo wp_kses_post( html_entity_decode( $this->get_current_mapping()['match']['customheadcode'] ) );
				}
			}
		}
	}

	$vontmnt_multiple_domain_mapping = VONTMNT_MultipleDomainMapping::get_instance();
}
