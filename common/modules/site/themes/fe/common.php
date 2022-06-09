<?php

//
function theme_getDefault() {
  global $packages;
  // how do we get this?
  //print_r(array_keys($packages));
  $result = $packages['base_settings']->useResource('settings');
  //$userSettings['current_theme'] = $themes[0];
  if (empty($result['site']['default_theme'])) {
    $result['site']['default_theme'] = 'yotsuba-b';
  }
  return $result['site']['default_theme'];
}

return array();

?>
