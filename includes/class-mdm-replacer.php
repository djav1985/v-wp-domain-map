<?php
/**
 * Replacer functionality for Multiple Domain Mapping.
 * Handles replacing URIs in content, links, and assets.
 *
 * @package VONTMNT_mdm
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( '...' );
}

/**
 * Replacer class for Multiple Domain Mapping.
 * Handles replacing URIs in content, links, and assets.
 */
class VONTMNT_MDM_Replacer {

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
	 * Initialize hooks for replacer functionality.
	 */
	private function init_hooks() {
		// Some hooks to change occurences of orignal domain to mapped domain.
		$this->replace_uris();
	}

	/**
	 * Aggregation of all filters to replace the uri in the current page.
	 */
	private function replace_uris() {
		// retrieve settings for compatibility mode.
		$options = $this->plugin->getSettings();
		if ( empty( $options ) ) {
			$options = array();
		}
		$options['compatibilitymode'] = isset( $options['compatibilitymode'] ) ? $options['compatibilitymode'] : 0;

		// single views.
		if ( ! ( $options['compatibilitymode'] && is_admin() ) ) {
			add_filter( 'page_link', array( $this, 'replace_uri' ), 20 );
			add_filter( 'post_link', array( $this, 'replace_uri' ), 20 );
			add_filter( 'post_type_link', array( $this, 'replace_uri' ), 20 );
			add_filter( 'attachment_link', array( $this, 'replace_uri' ), 20 );
			// get_comment_author_link ... not necessary (seems to use the "author_link").
			// get_comment_author_uri_link ... this is the url the author can fill out - should not be touched.
			// comment_reply_link ... leave this out until we manage to keep user logged in on addon-domains.
			// remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0); ... guess we should not add this....
		}

		// revoke mapping for the preview-button.
		add_filter( 'preview_post_link', array( $this, 'unreplace_uri' ) );

		// archive views.
		add_filter( 'paginate_links', array( $this, 'replace_uri' ), 10 );
		add_filter( 'day_link', array( $this, 'replace_uri' ), 20 );
		add_filter( 'month_link', array( $this, 'replace_uri' ), 20 );
		add_filter( 'year_link', array( $this, 'replace_uri' ), 20 );
		add_filter( 'author_link', array( $this, 'replace_uri' ), 10 );
		add_filter( 'term_link', array( $this, 'replace_uri' ), 10 );

		// feed url (if someone matches a domain to a feed...).
		add_filter( 'feed_link', array( $this, 'replace_uri' ), 10 );
		add_filter( 'self_link', array( $this, 'replace_uri' ), 10 );
		add_filter( 'author_feed_link', array( $this, 'replace_uri' ), 10 );

		// nav menu objects that do not use the standard link builders (like custom hrefs in the menu).
		add_filter( 'wp_nav_menu_objects', array( $this, 'replace_menu_uri' ) );

		// content elements - do not map in wp-admin.
		if ( ! is_admin() ) {
			add_filter( 'script_loader_src', array( $this, 'replace_domain' ), 10 );
			add_filter( 'style_loader_src', array( $this, 'replace_domain' ), 10 );
			add_filter( 'stylesheet_directory_uri', array( $this, 'replace_domain' ), 10 );
			add_filter( 'template_directory_uri', array( $this, 'replace_domain' ), 10 );
			add_filter( 'the_content', array( $this, 'replace_domain' ), 10 );
			add_filter( 'get_header_image_tag', array( $this, 'replace_domain' ), 10 );
			add_filter( 'wp_get_attachment_image_src', array( $this, 'replace_src_domain' ), 10 );
			add_filter( 'wp_calculate_image_srcset', array( $this, 'replace_srcset_domain' ), 10 );
		}

		// yoast sitemaps.
		add_filter( 'wpseo_xml_sitemap_post_url', array( $this, 'replace_yoast_xml_sitemap_post_url' ), 0, 1 );
		add_filter( 'wpseo_sitemap_entry', array( $this, 'replace_yoast_sitemap_entry' ), 10, 2 );

		// elementor preview url.
		add_filter( 'elementor/document/urls/preview', array( $this, 'replace_elementor_preview_url' ) );
	}

	/**
	 * All the helpers for the above filters.
	 *
	 * @param string $originalURI The original URI.
	 * @return string
	 */
	public function replace_uri( $originalURI ) {

		// loop mappings and compare match of mapping against each other.
		$mappings = $this->plugin->getMappings();
		if ( ! empty( $mappings ) && isset( $mappings['mappings'] ) && ! empty( $mappings['mappings'] ) ) {

			$bestMatch = array(
				'match'  => false,
				'factor' => PHP_INT_MIN,
			);

			foreach ( $mappings['mappings'] as $mapping ) {
				// first use our standard matching function.
				$matchCompare = $this->plugin->getCore()->uri_match( $originalURI, $mapping, false );
				// then enable custom matching by filtering.
				$matchCompare = apply_filters( 'VONTMNT_mdmf_uri_match', $matchCompare, $originalURI, $mapping, false );

				// if the current mapping fits better, use this instead the previous one.
				if ( $matchCompare !== false && isset( $matchCompare['factor'] ) && $matchCompare['factor'] > $bestMatch['factor'] ) {
					$bestMatch = $matchCompare;
				}
			}

			// we have a matching mapping -> let the magic happen.
			if ( ! empty( $bestMatch['match'] ) ) {
				$uriParsed = wp_parse_url( $originalURI );
				$newURI    = str_ireplace( trailingslashit( $uriParsed['host'] . $bestMatch['match']['path'] ), trailingslashit( $bestMatch['match']['domain'] ), $originalURI );
				return apply_filters( 'VONTMNT_mdmf_filtered_uri', $newURI, $originalURI, $bestMatch );
			}
		}

		return $originalURI;
	}

	/**
	 * Unreplace URI for preview links.
	 *
	 * @param string $mapped_uri The mapped URI.
	 * @return string
	 */
	public function unreplace_uri( $mapped_uri ) {

		// loop mappings and compare match of mapping against each other.
		$mappings = $this->plugin->getMappings();
		if ( ! empty( $mappings ) && isset( $mappings['mappings'] ) && ! empty( $mappings['mappings'] ) ) {

			$bestMatch = array(
				'match'  => false,
				'factor' => PHP_INT_MIN,
			);

			foreach ( $mappings['mappings'] as $mapping ) {
				// first use our standard matching function.
				$matchCompare = $this->plugin->getCore()->uri_match( $mapped_uri, $mapping, true );

				// then enable custom matching by filtering.
				$matchCompare = apply_filters( 'VONTMNT_mdmf_uri_match', $matchCompare, $mapped_uri, $mapping, true );

				// if the current mapping fits better, use this instead the previous one.
				if ( $matchCompare !== false && isset( $matchCompare['factor'] ) && $matchCompare['factor'] > $bestMatch['factor'] ) {
					$bestMatch = $matchCompare;
				}
			}

			// we have a matching mapping -> let the magic happen.
			if ( ! empty( $bestMatch['match'] ) ) {
				$uriParsed = wp_parse_url( $mapped_uri );
				$newURI    = str_ireplace( $uriParsed['host'], wp_parse_url( get_home_url() )['host'] . $bestMatch['match']['path'], $mapped_uri );
				return apply_filters( 'VONTMNT_mdmf_filtered_uri', $newURI, $mapped_uri, $bestMatch );
			}
		}

		return $mapped_uri;
	}

	/**
	 * Replace menu URIs.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public function replace_menu_uri( $items ) {
		// loop menu items and replace uri.
		foreach ( $items as $item ) {
			$item->url = $this->replace_uri( $item->url );
		}
		return $items;
	}

	/**
	 * Replace source domain in image arrays.
	 *
	 * @param array $src Source array.
	 * @return array
	 */
	public function replace_src_domain( $src ) {
		// url is in the 0-index of the src-array.
		if ( ! empty( $src ) ) {
			$src[0] = $this->replace_domain( $src[0] );
		}
		return $src;
	}

	/**
	 * Replace source domain in srcset arrays.
	 *
	 * @param array $srcset Srcset array.
	 * @return array
	 */
	public function replace_srcset_domain( $srcset ) {
		// iterate through srcset and change uri on all sources.
		if ( ! empty( $srcset ) ) {
			foreach ( $srcset as $key => $val ) {
				$srcset[ $key ]['url'] = $this->replace_domain( $val['url'] );
			}
		}
		return $srcset;
	}

	/**
	 * Replace domain in content.
	 *
	 * @param string $input Input content.
	 * @return string
	 */
	public function replace_domain( $input ) {
		// check if we are on a mapped page and replace original domain with mapped domain.
		if ( ! empty( $this->plugin->getCurrentMapping()['match'] ) ) {
			// we need to make sure that we only replace right at the beginning (after the protocol), so we do not destroy subdomains (like img.mydomain.com). that is why we add the :// to the strings.
			// and we also need to be sure that we do not replace it in a hyperlink which leads to any page on our original domain or to the home page itelsf. so we add a pregex which needs to have any character, a dot and again any character before the next ". that should do the trick....
			$preg_host = preg_quote( wp_parse_url( get_site_url() )['host'], '/' );
			// to understand the regex, use https://regexr.com/ :).
			$input = preg_replace_callback( '/:\/\/' . $preg_host . '([^\", ]*(\.)+[^\", ]*)([\"\']|$)/', array( $this, 'replace_domain_in_url' ), $input );
		}
		return $input;
	}

	/**
	 * Replace domain in URL callback.
	 *
	 * @param string|array $input Input to replace.
	 * @return string
	 */
	private function replace_domain_in_url( $input ) {
		// if this is called from preg_replace_callback we will receive an array. we only need the first index, so we can generalize this to be used by other functions as well.
		if ( is_array( $input ) ) {
			$input = $input[0];
		}

		// check if we are on a mapped page and replace original domain with mapped domain.
		if ( ! empty( $this->plugin->getCurrentMapping()['match'] ) ) {
			// we need to make sure that we only replace right at the beginning (after the protocol), so we do not destroy subdomains (like img.mydomain.com). that is why we add the :// to the strings.
			return str_ireplace( '://' . wp_parse_url( get_site_url() )['host'], '://' . wp_parse_url( 'dummyprotocol://' . $this->plugin->getCurrentMapping()['match']['domain'] )['host'], $input );
		}

		return $input;
	}

	/**
	 * Replace Yoast XML sitemap post URL.
	 *
	 * @param string $url The URL.
	 * @return string
	 */
	public function replace_yoast_xml_sitemap_post_url( $url ) {
		// add home url to the posturl, so YOAST will not handle the post like an external url.
		// this is stripped again in the next filter.
		if ( trailingslashit( get_home_url() ) !== trailingslashit( $url ) ) {
			$url = get_home_url() . '/\\' . $this->replace_uri( $url );
		}
		return $url;
	}

	/**
	 * Replace Yoast sitemap entry.
	 *
	 * @param array  $url The URL array.
	 * @param string $type The type.
	 * @return array
	 */
	public function replace_yoast_sitemap_entry( $url, $type ) {
		// true for all post types.
		if ( $type === 'post' ) {
			if ( false !== strpos( $url['loc'], '\\' ) ) {
				$tmp        = explode( '\\', $url['loc'] );
				$url['loc'] = $tmp[1];
			}
		}
		return $url;
	}

	/**
	 * Replace Elementor preview URL.
	 *
	 * @param string $preview_url The preview URL.
	 * @return string
	 */
	public function replace_elementor_preview_url( $preview_url ) {
		// elementor saves the uri in some escaped format.
		$unescaped_preview_url = str_replace( '\/', '/', $preview_url );
		return $this->unreplace_uri( $unescaped_preview_url );
	}
}