<?php

//
// loader functions
//

function registerPackages() {
}

$module_base = 'common/modules/';

function registerPackageGroup($group) {
  global $module_base, $packages;
  $dir = '../' . $module_base . $group;
  if (!is_dir($dir)) {
    // does not exists
    return false;
  }
  $dh = opendir($dir);
  if (!$dh) {
    // permissions
    return false;
  }
  $loaded = 0;
  while (($file = readdir($dh)) !== false) {
    if ($file[0] === '.') continue;
    //echo "filename: $file : filetype: " . filetype($dir . $file) . "\n";
    $path = $dir . '/' . $file;
    if (is_dir($path)) {
      $loaded++;
      $pkg = &registerPackage($group . '/' . $file);
      $packages[$pkg->name] = $pkg;
    }
  }
  closedir($dh);
  return $loaded;
}

function &registerPackage($pkg_path) {
  global $module_base;
  $full_pkg_path = '../' . $module_base . $pkg_path . '/';
  $pkg = include $full_pkg_path . 'index.php';
  return $pkg;
}

function getEnabledModules() {
  return array('base');
}

function enableModule($module){
  include '../common/modules/' . $module . '/index.php';
}

function enableModuleType($type, $module){
  $path = '../common/modules/' . $module . '/' . $type . '.php';
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