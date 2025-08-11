<?php
/**
 * Uninstall script for Multiple Domain Mapping on single site plugin.
 * Removes plugin options from the database.
 *
 * @package   VONTMNT_mdm
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'VONTMNT_mdm_mappings' );
delete_option( 'VONTMNT_mdm_settings' );
delete_option( 'VONTMNT_mdm_notice' );
delete_option( 'VONTMNT_mdm_upgrade_notice' );
delete_option( 'VONTMNT_mdm_versionhint' );
