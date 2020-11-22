<?php

/*
enableModule
diableModule

getEnabledModules

*/

function getEnabledModules() {
  return array('base');
}

function enableModule($module){
  include 'modules/' . $module . '/index.php';
}

function enableModuleType($type, $module){
  $path = 'modules/' . $module . '/' . $type . '.php';
  if (file_exists($path)) {
    include $path;
  }
}

function enableModules() {
  $modules = getEnabledModules();
  foreach($modules as $module) {
    enableModule($module);
  }
}

function enableModulesType($type) {
  $modules = getEnabledModules();
  foreach($modules as $module) {
    enableModuleType($type, $module);
  }
}

?>
