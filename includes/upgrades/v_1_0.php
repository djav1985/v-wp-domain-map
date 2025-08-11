<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( '...' );
}

// Upgrade DB from previous versions to v1.0+
$oldSettings = get_option( 'multidomainplugin_tabsettings' );
$oldMappings = get_option( 'multidomainplugin_options' );

if ( $oldSettings !== false || $oldMappings !== false ) {
	if ( $oldSettings !== false ) {
		// Prepare new options array.
		$options = array();
		// Store existing value there.
		if ( isset( $oldSettings['server_variable'] ) ) {
			$options['php_server'] = $oldSettings['server_variable'];
		}
		// Use sanitize function for proper format and content.
		$options = $this->sanitize_settings_group( $options );
		// Save new option to database.
		update_option( 'VONTMNT_mdm_settings', $options );
		// Delete old option, so this will never be executed again.
		delete_option( 'multidomainplugin_tabsettings' );
	}
	if ( $oldMappings !== false ) {
		// Prepare new options array.
		$options = array();
		// Iterate over old options.
		if ( ! empty( $oldMappings ) ) {
			foreach ( $oldMappings as $key => $val ) {
				// Strip last character and create sub-array.
				$arrayIndex = substr( $key, strlen( $key ) - 1 );
				if ( ! isset( $options[ 'cnt_' . $arrayIndex ] ) ) {
					$options[ 'cnt_' . $arrayIndex ] = array();
				}
				// Store values inside this sub-array.
				if ( stripos( $key, 'multidomainplugin_domain' ) !== false ) {
					$options[ 'cnt_' . $arrayIndex ]['domain'] = $val;
				} elseif ( stripos( $key, 'multidomainplugin_destination' ) !== false ) {
					$options[ 'cnt_' . $arrayIndex ]['path'] = $val;
				}
			}
		}
		// Use sanitize function for proper format and content.
		$options = $this->sanitize_mappings_group( $options );
		// Save new option to database.
		update_option( 'VONTMNT_mdm_mappings', $options );
		// Delete old option, so this will never be executed again.
		delete_option( 'multidomainplugin_options' );
	}
}
