<?php
// If this file is called directly, abort.
if( !defined( 'ABSPATH' ) ){
	die('...');
}

//Upgrade DB from previous versions to v1.0+
$oldSettings = get_option('multidomainplugin_tabsettings');
$oldMappings = get_option('multidomainplugin_options');

if($oldSettings !== false || $oldMappings !== false){

  //prepare admin notice
  $upgradeAdminNotice = esc_html__( 'Multiple Domain Mapping on single site has performed a database upgrade.', 'VONTMNT_mdm' );

  if($oldSettings !== false){
    //prepare new options array
    $options = array();
    //store existing value there
    if(isset($oldSettings['server_variable'])) $options['php_server'] = $oldSettings['server_variable'];
    //use sanitize function for proper format and content
    $options = $this->sanitize_settings_group($options);
    //save new option to database
    update_option('VONTMNT_mdm_settings', $options);
    //delete old option, so this will never be executed again
    delete_option('multidomainplugin_tabsettings');

    //prepare admin notice
    $upgradeAdminNotice .= ' ' . esc_html__( 'Settings have been updated.', 'VONTMNT_mdm' );
  }
  if($oldMappings !== false){
    //prepare new options array
    $options = array();
    //iterate over old options
    if(!empty($oldMappings)){
      foreach($oldMappings as $key => $val){
        //strip last character and create sub-array
        $arrayIndex = substr($key, strlen($key)-1);
        if(!isset($options['cnt_' . $arrayIndex])) $options['cnt_' . $arrayIndex] = array();
        //store values inside this sub-array
        if(stripos( $key, 'multidomainplugin_domain' ) !== false){
          $options['cnt_' . $arrayIndex]['domain'] = $val;
        }else if(stripos( $key, 'multidomainplugin_destination' ) !== false){
          $options['cnt_' . $arrayIndex]['path'] = $val;
        }
      }
    }

    //use sanitize function for proper format and content
    $options = $this->sanitize_mappings_group($options);
    //save new option to database
    update_option('VONTMNT_mdm_mappings', $options);
    //delete old option, so this will never be executed again
    delete_option('multidomainplugin_options');

    //prepare admin notice
    $upgradeAdminNotice .= ' ' . esc_html__( 'Mappings have been updated.', 'VONTMNT_mdm' );
  }

  //finalize admin notice
  $upgradeAdminNotice .= ' ' . sprintf('%s <a href="'.admin_url( 'tools.php?page=' . plugin_basename('multiple--on-single-site/multidomainmapping.php') ).'" title="%s">%s</a> %s.', esc_html__( 'Please head over to the', 'VONTMNT_mdm' ), esc_html__('Multidomain-Settings', 'VONTMNT_mdm'), esc_html__('Multidomain-Settings'), esc_html__('and check if everything is fine', 'VONTMNT_mdm'));
  $upgradeAdminNotice = '<p>' . $upgradeAdminNotice . '</p>';
  $upgradeAdminScreenNotice = '<p><strong>'.esc_html__('Please note that some mappings may have been combined, since http/https and www/non-www are now handled in one line. You will need to check if the mappings are still working as you expect them to - do not forget to clear website and browser caches for that purpose.', 'VONTMNT_mdm').'</strong></p>';
}
if(isset($upgradeAdminNotice) && !empty($upgradeAdminNotice)){
  update_option('VONTMNT_mdm_notice', array(
    'class' => 'notice notice-info',
    'text' => $upgradeAdminNotice,
    'onScreenText' => $upgradeAdminScreenNotice
  ));
}
