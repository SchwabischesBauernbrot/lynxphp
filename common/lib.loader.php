<?php

//
// module loading functions
//

$module_base = 'common/modules/';

$_loader_data = array();
function ldr_require($file) {
  global $_loader_data;
  // normalize file path / name?
  if (empty($_loader_data[$file])) {
    include $file;
    $_loader_data[$file] = true;
  }
}

function ldr_done() {
  global $_loader_data;
  $_loader_data = false;
}

// uid and gid can be string (doesn't have be numeric)
function recurse_chown_chgrp($basepath, $uid, $gid) {
  if (is_dir($basepath)) {
    $d = opendir($basepath);
    if (!$d) {
      echo "file::recurse_chown_chgrp [$basepath] error<br>\n";
      return;
    }
    while(($file = readdir($d)) !== false) {
      if ($file !== '.' && $file !== '..') {
        $path = $basepath . '/' . $file ;

        //print $typepath. " : " . filetype ($typepath). "<BR>" ;
        if (filetype($path) === 'dir') {
          recurse_chown_chgrp($path, $uid, $gid);
        }
        // if we can't change it, then we're probably not root and nothing we can do
        // likely the webserver that will have the correct perms in the first place
        @chown($path, $uid);
        @chgrp($path, $gid);
      }
    }
  }
  @chown($basepath, $uid);
  @chgrp($basepath, $gid);
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
        if (!isset($rsrcHdr['name'])) {
          echo "<pre>Weird name not set", print_r($rsrcHdr, 1), "in [$full_pkg_path]</pre>\n";
        }
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
      // usually fe/be dir
      $loaded++;
      //echo "loading [$group/$file]<br>\n";
      $groupfile = $group . '/' . $file;
      if (in_array($groupfile, DISABLE_MODULES)) {
        continue;
      }
      $pkg = registerPackage($groupfile);
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

// FE and BE call this
function registerPackages() {
  global $packages;
  $packages = array();
  //$packages['base'] = registerPackage('base');

  // data
  $groups = array(
    'base/base', 'base/board', 'base/user', 'base/site', 'base/thread',
    'board', 'thread', 'post', 'user', 'admin', 'global', 'site', 'protection');
  foreach($groups as $group) {
    registerPackageGroup($group);
  }
  // code optimization?
  /*
  registerPackageGroup('board');
  registerPackageGroup('post');
  registerPackageGroup('user');
  registerPackageGroup('admin');
  registerPackageGroup('global');
  registerPackageGroup('site');
  registerPackageGroup('protection');
  */
}

//
// backend uses this
//

function getEnabledModules() {
  return array('base');
}

/*
function enableModule($module){
  include '../common/modules/' . $module . '/index.php';
}
function enableModules() {
  $modules = getEnabledModules();
  foreach($modules as $module) {
    enableModule($module);
  }
}
*/

function enableModuleType($type, $module){
  $path = '../common/modules/' . $module . '/' . $type . '.php';
  if (file_exists($path)) {
    include $path;
  }
}

function enableModulesType($type) {
  $modules = getEnabledModules();
  foreach($modules as $module) {
    enableModuleType($type, $module);
  }
}

?>