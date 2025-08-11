<?php
// If this file is called directly, abort.
if( !defined( 'ABSPATH' ) ){
	die('...');
}

//update hints from previous versions to v1.1+
$oldVersion = get_option('VONTMNT_mdm_versionhint');

//check if a previous version has been stored (started with v1.1) or the stored version is older than the current version
if($oldVersion === false || version_compare($oldVersion, $this->pluginVersion, '<')){
	update_option('VONTMNT_mdm_upgrade_notice', array(
		'class' => 'notice notice-info',
		'text' => '<p>' . __('Multiple Domain Mapping on single site has a great new feature: Add custom html code per mapped domain! We also have an <strong>exclusive coupon code</strong> for the advanced Domain Mapping System.', 'VONTMNT_mdm') . ' <a href="'.admin_url( 'tools.php?page=' . plugin_basename('multiple--on-single-site/multidomainmapping.php') ).'" title="'. __('Multidomain-settings', 'VONTMNT_mdm') .'">'. __('Go ahead to multidomain-settings (and remove this notice...)', 'VONTMNT_mdm') .'</a>' . '</p>',
		'onScreenText' => '<p>' . __('You can now add custom html code per mapped domain.', 'VONTMNT_mdm') . ' <a href="?page='. plugin_basename('multiple--on-single-site/multidomainmapping.php') .'&amp;tab=advanced"><strong>Find out more about premium plans and your exclusive coupon code!</strong></a>' . '</p>'
	));
}
