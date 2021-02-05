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
      $pkg = registerPackage($group . '/' . $file);
      if ($pkg) {
        $packages[$pkg->name] = $pkg;
      }
    } else {
      // file_exists but not a dir
      if (!is_readable($path)) {
        echo "I can't read [$path] please fix the permissions (set execute flag?)<br>\n";
      }
    }
  }
  closedir($dh);
  return $loaded;
}

function registerPackage($pkg_path) {
  global $module_base;
  $full_pkg_path = '../' . $module_base . $pkg_path . '/';

  $pkg = false;
  if (is_readable($full_pkg_path . 'module.php')) {
    //echo "Loading [$full_pkg_path] module<br>\n";
    // we want to keep these to pure data as much as possible (no calculation to get result)
    $data = include $full_pkg_path . 'module.php';
    // handle empty module.php
    // maybe version should be assumed
    if (
      !empty($data) && (empty($data['name']) || empty($data['version']))
    ) {
      echo "[$full_pkg_path] module.php did not return correct data, make sure name and version are set<br>\n";
      return $pkg;
    }
    $pkg = new package($data['name'], $data['version'], substr($full_pkg_path, 0, -1));
    // not all module.php will have resources
    if (!empty($data['resources'])) {
      foreach($data['resources'] as $rsrcHdr) {
        $pkg->addResource($rsrcHdr['name'], $rsrcHdr['params']);
      }
    }
  } else {
    //echo "module_base[$module_base]<br>\n";
    if (!file_exists($full_pkg_path . 'module.php')) {
      echo "No module.php in [$full_pkg_path]<br>\n";
    } else {
      // not sure these do anything...
      if (!is_readable('../')) {
        echo ".. isn't readable<br>\n";
      }
      if (!is_readable('../' . $module_base)) {
        echo "../$module_base isn't readable<br>\n";
      }
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