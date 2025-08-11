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
		$this->plugin->setOriginalRequestURI( $_SERVER['REQUEST_URI'] );

		// definitely no request-mapping in backend.
		if ( is_admin() ) {
			return $do_parse;
		}

		// loop mappings and compare match of mapping against each other.
		$mappings = $this->plugin->getMappings();
		if ( ! empty( $mappings ) && isset( $mappings['mappings'] ) && ! empty( $mappings['mappings'] ) ) {

			foreach ( $mappings['mappings'] as $mapping ) {
				// first use our standard matching function.
				$matchCompare = $this->uri_match( $this->plugin->getCurrentURI(), $mapping, true );
				// then enable custom matching by filtering.
				$matchCompare = apply_filters( 'vontmnt_mdmf_uri_match', $matchCompare, $this->plugin->getCurrentURI(), $mapping, true );

				// if the current mapping fits better, use this instead the previous one.
				if ( false !== $matchCompare && isset( $matchCompare['factor'] ) && $matchCompare['factor'] > $this->plugin->getCurrentMapping()['factor'] ) {
					$this->plugin->setCurrentMapping( $matchCompare );
				}
			}

			// we have a matching mapping -> let the magic happen.
			if ( ! empty( $this->plugin->getCurrentMapping()['match'] ) ) {
				// store original request uri.
				$this->plugin->setOriginalRequestURI( $_SERVER['REQUEST_URI'] );
				// set request uri to our original mapping path AND if we have a longer query, we need to append it.
				$newRequestURI = trailingslashit( $this->plugin->getCurrentMapping()['match']['path'] . substr( str_ireplace( 'www.', '', $this->plugin->getCurrentURI() ), strlen( str_ireplace( 'www.', '', $this->plugin->getCurrentMapping()['match']['domain'] ) ) ) );
				// enable additional filtering on the request_uri.
				$_SERVER['REQUEST_URI'] = apply_filters( 'vontmnt_mdmf_request_uri', $newRequestURI, $this->plugin->getCurrentURI(), $this->plugin->getCurrentMapping() );
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
		if ( false !== $this->plugin->getCurrentMapping()['match'] ) {

			// parse the urls.
			$parsedRedirectUrl  = wp_parse_url( $redirect_url );
			$parsedRequestedUrl = wp_parse_url( $requested_url );

			// if we have a slug in the domain-part of our mapping like test.com/ball <=> /sports/ball.
			$explodedMappingDomain = explode( '/', $this->plugin->getCurrentMapping()['match']['domain'] );
			if ( count( $explodedMappingDomain ) > 1 ) {

				// we need to cut out these slug-parts from the parsedRedirectUrl-path.
				$explodedRedirectUrlPath = explode( '/', $parsedRedirectUrl['path'] );

				// but only as long as they "overlap" (like the "ball"-sequence in the example above).
				$exploded_mapping_domain_count = count( $explodedMappingDomain );
				for ( $i = 1; $i < $exploded_mapping_domain_count; $i++ ) {
					if ( isset( $explodedRedirectUrlPath[ $i ] ) && $explodedRedirectUrlPath[ $i ] === $explodedMappingDomain[ $i ] ) {
						unset( $explodedRedirectUrlPath[ $i ] );
					}
				}

				// stick the path together again.
				$parsedRedirectUrl['path'] = implode( '/', $explodedRedirectUrlPath );
			}

			// now compare if those two urls are the same, and skip this redirect if so.
			if ( trailingslashit( $this->plugin->getCurrentMapping()['match']['path'] . $parsedRedirectUrl['path'] ) === trailingslashit( $parsedRequestedUrl['path'] ) ) {
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
			$arg2               = str_ireplace( 'www.', '', $mapping['domain'] );
			$matchingPosCompare = 0;
		} else {
			$arg2               = $mapping['path'];
			$matchingPosCompare = strlen( str_ireplace( 'http://', '', str_ireplace( 'https://', '', str_ireplace( 'www.', '', get_home_url() ) ) ) );
		}

		// check if arg2 is part of uri and starts where we want to.
		$matchingPos = stripos( trailingslashit( $uri ), trailingslashit( $arg2 ) );
		if ( false !== $matchingPos && $matchingPosCompare === $matchingPos ) {
			// use length of match as factor.
			return array(
				'match'  => $mapping,
				'factor' => strlen( trailingslashit( $arg2 ) ),
			);
		}
		return false;
	}
}
