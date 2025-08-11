<?php
/**
 * Core functionality for Multiple Domain Mapping.
 * Handles request parsing and URI rewriting.
 *
 * @package VONTMNT_mdm
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( '...' );
}

/**
 * Core class for Multiple Domain Mapping.
 * Handles request parsing and URI rewriting functionality.
 */
class VONTMNT_MDM_Core {

	/**
	 * Reference to the main plugin instance.
	 *
	 * @var VONTMNT_MultipleDomainMapping
	 */
	private $plugin;

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
	 * Initialize hooks for core functionality.
	 */
	private function init_hooks() {
		// Process request.
		add_filter( 'do_parse_request', array( $this, 'parse_request' ), 10, 1 );
		add_filter( 'redirect_canonical', array( $this, 'check_canonical_redirect' ), 10, 2 );
	}

	/**
	 * Change the request, check for matching mappings.
	 *
	 * @param bool $do_parse Whether to parse the request.
	 * @return bool
	 */
	public function parse_request( $do_parse ) {
		// store current request uri as fallback for the originalRequestURI variable, no matter if we have a match or not.
		$this->plugin->set_original_request_uri( $_SERVER['REQUEST_URI'] );

		// definitely no request-mapping in backend.
		if ( is_admin() ) {
			return $do_parse;
		}

		// loop mappings and compare match of mapping against each other.
		$mappings = $this->plugin->get_mappings();
		if ( ! empty( $mappings ) && isset( $mappings['mappings'] ) && ! empty( $mappings['mappings'] ) ) {

			foreach ( $mappings['mappings'] as $mapping ) {
				// first use our standard matching function.
				$match_compare = $this->uri_match( $this->plugin->get_current_uri(), $mapping, true );
				// then enable custom matching by filtering.
				$match_compare = apply_filters( 'vontmnt_mdmf_uri_match', $match_compare, $this->plugin->get_current_uri(), $mapping, true );

				// if the current mapping fits better, use this instead the previous one.
				if ( false !== $match_compare && isset( $match_compare['factor'] ) && $match_compare['factor'] > $this->plugin->get_current_mapping()['factor'] ) {
					$this->plugin->set_current_mapping( $match_compare );
				}
			}

			// we have a matching mapping -> let the magic happen.
			if ( ! empty( $this->plugin->get_current_mapping()['match'] ) ) {
				// store original request uri.
				$this->plugin->set_original_request_uri( $_SERVER['REQUEST_URI'] );
				// set request uri to our original mapping path AND if we have a longer query, we need to append it.
				$new_request_uri = trailingslashit( $this->plugin->get_current_mapping()['match']['path'] . substr( str_ireplace( 'www.', '', $this->plugin->get_current_uri() ), strlen( str_ireplace( 'www.', '', $this->plugin->get_current_mapping()['match']['domain'] ) ) ) );
				// enable additional filtering on the request_uri.
				$_SERVER['REQUEST_URI'] = apply_filters( 'vontmnt_mdmf_request_uri', $new_request_uri, $this->plugin->get_current_uri(), $this->plugin->get_current_mapping() );
			}
		}

		return $do_parse;
	}

	/**
	 * Hook into the canonical redirect to avoid infinite redirection loops.
	 * So far we only know that this is necessary for paged posts (nextpage-tag), which result in redirect loops otherwise.
	 *
	 * @param string $redirect_url The redirect URL.
	 * @param string $requested_url The requested URL.
	 * @return string|false
	 */
	public function check_canonical_redirect( $redirect_url, $requested_url ) {

		// are we on a mapped page?.
		if ( false !== $this->plugin->get_current_mapping()['match'] ) {

			// parse the urls.
			$parsed_redirect_url  = wp_parse_url( $redirect_url );
			$parsed_requested_url = wp_parse_url( $requested_url );

			// if we have a slug in the domain-part of our mapping like test.com/ball <=> /sports/ball.
			$exploded_mapping_domain = explode( '/', $this->plugin->get_current_mapping()['match']['domain'] );
			if ( count( $exploded_mapping_domain ) > 1 ) {

				// we need to cut out these slug-parts from the parsedRedirectUrl-path.
				$exploded_redirect_url_path = explode( '/', $parsed_redirect_url['path'] );

				// but only as long as they "overlap" (like the "ball"-sequence in the example above).
				$exploded_mapping_domain_count = count( $exploded_mapping_domain );
				for ( $i = 1; $i < $exploded_mapping_domain_count; $i++ ) {
					if ( isset( $exploded_redirect_url_path[ $i ] ) && $exploded_redirect_url_path[ $i ] === $exploded_mapping_domain[ $i ] ) {
						unset( $exploded_redirect_url_path[ $i ] );
					}
				}

				// stick the path together again.
				$parsed_redirect_url['path'] = implode( '/', $exploded_redirect_url_path );
			}

			// now compare if those two urls are the same, and skip this redirect if so.
			if ( trailingslashit( $this->plugin->get_current_mapping()['match']['path'] . $parsed_redirect_url['path'] ) === trailingslashit( $parsed_requested_url['path'] ) ) {
				return false;
			}
		}

		// standard return value.
		return $redirect_url;
	}

	/**
	 * Standard function to check an uri against a mapping.
	 *
	 * @param string     $uri The URI to check.
	 * @param array      $mapping The mapping to check against.
	 * @param bool|false $reverse Whether to reverse the check.
	 * @return array|false
	 */
	public function uri_match( $uri, $mapping, $reverse = false ) {

		// strip protocol from uri.
		$uri = str_ireplace( 'http://', '', str_ireplace( 'https://', '', $uri ) );

		// strip www-subdomain from uri for matching purpose.
		$uri = str_ireplace( 'www.', '', $uri );

		// do we check match at parsing the site or when replacing uris in the page?.
		if ( $reverse ) {
			$arg2                 = str_ireplace( 'www.', '', $mapping['domain'] );
			$matching_pos_compare = 0;
		} else {
			$arg2                 = $mapping['path'];
			$matching_pos_compare = strlen( str_ireplace( 'http://', '', str_ireplace( 'https://', '', str_ireplace( 'www.', '', get_home_url() ) ) ) );
		}

		// check if arg2 is part of uri and starts where we want to.
		$matching_pos = stripos( trailingslashit( $uri ), trailingslashit( $arg2 ) );
		if ( false !== $matching_pos && $matching_pos_compare === $matching_pos ) {
			// use length of match as factor.
			return array(
				'match'  => $mapping,
				'factor' => strlen( trailingslashit( $arg2 ) ),
			);
		}
		return false;
	}
}
