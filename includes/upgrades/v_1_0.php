<?php
// If this file is called directly, abort.
if( !defined( 'ABSPATH' ) ){
	die('...');
}

//Upgrade DB from previous versions to v1.0+
$oldSettings = get_option('multidomainplugin_tabsettings');
$oldMappings = get_option('multidomainplugin_options');

if($oldSettings !== false || $oldMappings !== false){

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
  }

}
