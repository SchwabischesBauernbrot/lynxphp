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
      if ($pkg) {
        $packages[$pkg->name] = $pkg;
      }
    }
  }
  closedir($dh);
  return $loaded;
}

function &registerPackage($pkg_path) {
  global $module_base;
  $full_pkg_path = '../' . $module_base . $pkg_path . '/';
  /*
  if (file_exists($full_pkg_path . 'module.json')) {
    $json = file_get_contents($full_pkg_path . 'module.json');
    //$json = preg_replace("/,(?!.*,)/", "", $json);
    //echo "json[$json]<br>\n";
    // json is an ugly format, not native
    // not performant to add json5 support for such a critical task...
    // but is a cacheable process...
    // but php data file is simpler and less complex
    $data = json_decode($json, true);
    //echo "<pre>", print_r($data, 1), "</pre>\n";
    // FIXME: 2 phases, introspect vs use
    // convert data into code...
    $pkg = new package($data['name'], $data['version'], substr($full_pkg_path, 0, -1));
    foreach($data['resources'] as $rsrcHdr) {
      $pkg->addResource($rsrcHdr['name'], $rsrcHdr['params']);
    }
  */
  if (file_exists($full_pkg_path . 'module.php')) {
    $data = include $full_pkg_path . 'module.php';
    $pkg = new package($data['name'], $data['version'], substr($full_pkg_path, 0, -1));
    foreach($data['resources'] as $rsrcHdr) {
      $pkg->addResource($rsrcHdr['name'], $rsrcHdr['params']);
    }
  } else {
    $pkg = false;
    if (file_exists($full_pkg_path . 'index.php')) {
      $pkg = include $full_pkg_path . 'index.php';
    }
  }
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