<?php

//
// module loading functions
//

// maybe also functions for dealing with a collection of packages?

$module_base = 'common/modules/';

$_loader_data = array();
function ldr_require($file) {
  $f = realpath($file);
  //echo "loading[$file]=>[$f]<br>\n";
  if (!$f) {
    if (DEV_MODE) {
      echo "Path[$file] isn't great", gettrace(), "<br>\n";
    }
    $f = $file;
  }
  global $_loader_data;
  if ($_loader_data === false && DEV_MODE) {
    echo "loader marked down but [$file]", gettrace(), "<Br>\n";
  }
  // normalize file path / name?
  if (empty($_loader_data[$f])) {
    $res = include $file;
    $_loader_data[$f] = true;
  }
}

ldr_require('../common/lib.packages.php');

function ldr_done() {
  global $_loader_data;
  // can't do this
  // lib.handler wrapContent / lib.packages useResource portal system
  // now needs to make sure
  //$_loader_data = false;
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

function isBackend() {
  global $db;
  return $db ? true : false;
}

global $settingsBlock, $sectionNames, $portalResources;
// level is like security level (perm/role)
// location is where we aggregate the blocks
// location is the section (site, homepage)
// can be english like but shouldn't be...
//
// FIXME: we're going to need a validation stage
// FIXME: a way to order the labels for sections/locations
function initSettingsBlock() {
  global $settingsBlock, $sectionNames;
  $settingsBlock = array(
    'all' => array(),
    'loggedin' => array(),
    'bo' => array(),
    //'bv' => array(),
    //'bj' => array(),
    'global' => array(),
    'admin' => array(),
  );
  $sectionNames = array(
    'all' => array(),
    'loggedin' => array(),
    'bo' => array(),
    //'bv' => array(),
    //'bj' => array(),
    'global' => array(),
    'admin' => array(),
  );
}

function compileSettingsBlock($loc, $block) {
  global $settingsBlock;
  $level = empty($block['level']) ? 'all' : $block['level'];
  // ensure array
  if (!isset($settingsBlock[$level])) $settingsBlock[$level] = array();
  if (!isset($settingsBlock[$level][$loc])) $settingsBlock[$level][$loc] = array();
  //echo "compiling[$level] [", print_r($block, 1), "]<br>\n";
  //$cLevel = $settingsBlock[$level][$loc];
  if (isset($block['locationLabel'])) {
    // english link for nav links...
    global $sectionNames;
    $sectionNames[$level][$loc] = $block['locationLabel'];
  }
  if (isset($block['addFields'])) {
    foreach($block['addFields'] as $f => $d) {
      // what if we add and the field is already there? meh
      // we should exit immediately so a dev never grabs that
      if (isset($settingsBlock[$level][$loc][$f])) {
        $msg = "$f is already used";
        if (isBackend()) {
          echo $msg;
        } else {
          wrapContent($msg);
        }
        exit(1);
      }
      $settingsBlock[$level][$loc][$f] = $d;
    }
  }
  if (isset($block['list'])) {
    if (count($settingsBlock[$level][$loc])) {
      $msg = "$level $loc is already has fields: <pre>" . print_r($settingsBlock[$level][$loc], 1) . "</pre>";
      if (isBackend()) {
        echo $msg;
      } else {
        wrapContent($msg);
      }
      exit(1);
    }
    $settingsBlock[$level][$loc] = json_encode(array(
      'type' => 'form',
      'fields' => array()
    ));
  }
  // modified field
  // remove field
}

// section aka loc
function getCompiledSettingsSectionLabel($level, $section) {
  global $sectionNames;
  return $sectionNames[$level][$section];
}

function getCompiledSettings($level) {
  global $settingsBlock;
  //echo "<pre>", print_r($settingsBlock, 1), "</pre>\n";
  return $settingsBlock[$level];
}

// maybe put into lib.portals
// these are a little simple to warrant their own functions...
// just some scaffolding for the future

// maybe we should be building a pipeline that index can just execute...
// unlike PIPELINE_PORTALS_DATA, it one a pipeline per portal
// how separate? this is fine, maybe less chance of a collision this way
global $_PortalPipelines;
$_PortalPipelines = array();
$portalResources = array();

// has mp been realpath'd?
function compilePortalResource($mp, $n, $o, $pkg) {
  global $portalResources, $_PortalPipelines;

  // each $n can only be registered once
  if (isset($_PortalPipelines[$n])) {
    // be might hate this...
    if (DEV_MODE) {
      echo "compilePortalResource [$n] claimed multiple times<br>\n";
    }
    return;
  }
  // a whole pipeline
  $_PortalPipelines[$n] = new pipeline_registry;
  // for a single module
  // we could just make one registry
  // and attach at different portals on it
  $ppm = new portal_pipeline_module($n);
  $snake = camelToSnake($n);
  // n[boardSettings] snake[board_settings]
  //echo "n[$n] snake[$snake]<br>\n";
  global $db;
  $type = 'fe';
  if ($db) {
    $type = 'be';
    $cpn = 'PIPELINE_BE_' . strtoupper($snake) . '_PORTAL';
  } else {
    $cpn = 'PIPELINE_FE_' . strtoupper($snake) . '_PORTAL';
    //echo "<pre>o[", print_r($o, 1), "]</pre>\n";
    if (isset($o['fePipelines'])) {
      if (is_array($o['fePipelines'])) {
        definePipelines($o['fePipelines']);
      } else {
        echo "portal[$n] fePipelines is not an array<br>\n";
      }
    }
  }

  $ppm->attach($_PortalPipelines[$n], function(&$io) use ($n, $mp, $snake, $pkg, $type) {
    $getModule = function() use ($n) {
      //echo "Set up module for portal pipeline[$n]<br>\n";
      return array();
    };
    // portal don't belong to a be_pkg
    // so we can't use common or shared
    // if they need it, they'll have to include it on their own
    /*
    if (!$ref->ranOnce) {
      if (is_readable($module_path . 'be/common.php')) {
        // ref isn't defined...
        //$ref->common =
        $ref->common = include $module_path . 'be/common.php';
      } else {
        if (file_exists($module_path . 'be/common.php')) {
          echo "perms? [$module_path]be/common.php<br>\n";
        }
      }
      $ref->ranOnce = true;
      if (isset($ref->common)) {
        $common = $ref->common;
      }
    }
    */

    /*
    if (!file_exists($path)) {
      echo "This module [$pipeline_name], [$path] is not found<br>\n";
      return;
    }
    */
    $path = $mp . $type . '/portals/' . $snake . '_module.php';
    //echo "Running path[$path]<br>\n";
    include $path;
  });
  definePipeline($cpn);
  $o['pipeline'] = strtolower($cpn);

  // snakeName, modulePath
  $portalResources[$n] = array_merge($o, array(
    'modulePath' => $mp,
    'snakeName' => $snake,
    //'pipeline' => strtolower($cpn),
  ));
}

// defined the pipeline
// add module that loads it
// io can be defined...
function getCompiledPortalResource($n) {
  global $portalResources;
  // return
  return $portalResources[$n];
}

function registerPackage($pkg_path) {
  //echo "pkg_path[$pkg_path]<br>\n";
  global $module_base;
  $full_pkg_path = '../' . $module_base . $pkg_path . '/';

  // FIXME: could reduce diskio if we had a $pkg_path lock

  $pkg = false;
  if (!is_readable($full_pkg_path . 'module.php')) {
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
    return $pkg;
  }
  global $packages;
  foreach($packages as $pkg) {
    if ($pkg->dir === $full_pkg_path) {
      // already loaded...
      return $pkg;
    }
  }

  // has to happen before module.php is included
  $shared = false;
  if (is_readable($full_pkg_path . 'shared.php')) {
    // make $share available to the next include
    // hack used to load themes into settings
    // this will load it twice, we need to communicate with the yet to be made pkg
    //echo "one<br>\n";
    // maybe we use ldr_require since they can have functions
    // and we can't load them twice...
    $shared = include $full_pkg_path . 'shared.php';
    //echo "has shared[", $shared !== false, "]<br>\n";
  }

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
  //global $packages;
  // handle already loaded
  if (isset($packages[$data['name']])) {
    //echo "already loaded[", $data['name'], "] in [$full_pkg_path]<bR>\n";
    // really no harm here if two objects made, just wastes memory
    // and causes confusion
    // though if a package isn't labeled right, devs need to know
    // and we can't reference a package resources if it's shadowed
    //
    // this makes it so the module has to have a unique name
    // for it's functionality to work
    // a warning seems prudent
    // but because of dependency loading, doesn't really indicate
    // if why it's already loaded...
    //return $packages[$data['name']];
  }
  $pkg = new package($data['name'], $data['version'], substr($full_pkg_path, 0, -1));
  $pkg->shared = $shared;

  if (!empty($data['dependencies'])) {
    //echo "<pre>has depends [", print_r($data['dependencies'], 1), "]</pre>\n";

    // FIXME: now what if this module is disabled
    // well right we only disable on the frontend...
    // because all backendRoutes are basically inert unless called
    // so everything on the backend will be attached and executing
    // even if the frontend does nothing with it

    // probably should be doing this inside buildBackendRoutes
    // but buildBackendRoutes can't access dependencies
    $pkg->dependencies = $data['dependencies'];
    if (isBackend()) { // for now until we need fe
      foreach($data['dependencies'] as $depPkgName) {
        // front or back?
        //if (isBackend()) {
          // make sure we have it

          // we still need this for js.php I think
          //echo "doing dep [$depPkgName]<br>\n";
          $depPkg = registerPackage($depPkgName);
          $depPkg->buildBackendRoutes();
          // register package with $packages global
          $packages[$depPkg->name] = $depPkg;
        //} else {
          // do nothing for now

          // we do need to do make sure pipelines are established
        //}
      }
    }
  }

  // not all module.php will have resources
  if (!empty($data['resources'])) {
    foreach($data['resources'] as $rsrcHdr) {
      if (!isset($rsrcHdr['name'])) {
        echo "<pre>Weird name not set", print_r($rsrcHdr, 1), "in [$full_pkg_path]</pre>\n";
      }
      $cacheSettings = empty($rsrcHdr['cacheSettings']) ? false : $rsrcHdr['cacheSettings'];
      $pkg->addResource($rsrcHdr['name'], $rsrcHdr['params'], $cacheSettings);
    }
  }
  if (!empty($data['portals'])) {
    foreach($data['portals'] as $n => $o) {
      compilePortalResource($full_pkg_path, $n, $o, $pkg);
    }
  }

  if (!empty($data['settings'])) {
    foreach($data['settings'] as $block) {
      // level, actions: addFields
      // location: where this action is happening...
      compileSettingsBlock($block['location'], $block);
      // I don't think this is used
      $pkg->addSettingsBlock($block['location'], $block);
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

  initSettingsBlock();

  // data
  $groups = array(
    'base/base', 'base/board', 'base/user', 'base/site', 'base/thread',
    'board', 'thread', 'post', 'media', 'user', 'admin', 'global', 'site', 'protection');
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