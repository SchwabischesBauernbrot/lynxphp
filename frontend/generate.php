<?php
require '../common/lib.loader.php';
ldr_require('../common/common.php'); // ensureOptions
ldr_require('../common/lib.http.server.php');
// if executed from CLI, how do we know which config to use?

// TEMP: change change defines here...
//define('BASE_HREF', '/static');
global $BASE_HREF;

define('DEV_MODE', false);
define('IN_GENERATE', true);

// without BACKEND_BASE_URL being correct, images aren't going to load
// so we have to pass the right domain in...
// or suggest config_.php

$host = getenv('USE_CONFIG');
// argument overrides environment

// php generate.php host
if (isset($GLOBALS['argv'][1])) {
  $host = $GLOBALS['argv'][1];
}

if ($host) {
  echo "Setting host[$host]\n";
  $_SERVER['HTTP_HOST'] = $host;
}

$req_method = 'GET';
require 'setup.php';
require 'setup.router.php';
// it's always auto-detected correctly
// only if it's manually hardcoded when it's wrong...
// well we should normalize
// we need BASE_HREF to be where the frontend webroot is...
// hrm we get ./
//$BASE_HREF = preg_replace('~static/$~', '', $BASE_HREF);

// it's not set, so guess....
if ($BASE_HREF === './') {
  $BASE_HREF = '/';
}

function stripHTMLComments($html) {
  return preg_replace('~<!--(?!<!)[^\[>].*?-->~s', '', $html);
}

function routeToDisk($route, $path) {
  global $router;
  // what should we set as REQUEST_URI?
  $_SERVER['REQUEST_URI'] = $route;
  ob_start();
  $router->exec('GET', $route);
  // could we place this correctly?
  $routeHTML = '<div style="height: 40px;"></div>'. "\n" . ob_get_clean();
  $cleanHTML = stripHTMLComments($routeHTML);
  echo "writing [$route] to [static/][$path]\n";
  file_put_contents('static/' . $path, $cleanHTML);
}

function routeToFile($route) {
  $parts = explode('/', $route);
  $file = $parts[count($parts) - 1];
  if (!$route || $route[strlen($route) - 1] === '/') $file = 'index.html';
  $dir = '';
  //echo "parts[", count($parts), "] file[$file] route[$route]<br>\n";
  if (count($parts) !== 2) {
    // dirname fails /BOARDURI/
    if (!$route || $route[strlen($route) - 1] === '/') {
      $dir = trim($route, '/'); // just remove all the slashes
      if (count($parts) === 3) {
        $file = 'index.html'; // probably dont' need to do this
      }
    } else {
      $dir = ltrim(dirname($route), '/'); // we don't want the first /
    }
    //echo "dir[$dir]\n";
    if (!file_exists('static/'. $dir)) {
      mkdir('static/'. $dir, 0777, true);
    }
    $dir .= '/';
  }
  return $dir . $file;
}

// get a list all boards
// requires web server and backend to be running
$boards = getBoards();
$boardURIs = array_map(function($bData) {
  return $bData['uri'];
}, $boards['data']['boards']);

// this only makes sense if we have 404 generation...
function shouldPurgeRoute() {
}

function shouldGenerateRoute($path, $cacheSettings, $params) {
  global $router;
  $fullpath = 'static/' . $path;
  if (!file_exists($fullpath)) return true;
  $ourtime = filemtime($fullpath);
  $mtime = $router->getMaxMtime($cacheSettings, $params);
  return $mtime > $ourtime;
}

// setup will set up frontend routes only
// and that's what we want
$res = $router->getRouteData(array(
  'method' => 'GET', 'skipLoggedIn' => true, 'skipDontGen' => true
));
foreach($res['GET'] as $r => $arr) {
  if (strpos($r, 'settings') !== false && strpos($r, 'themedemo') === false) continue; // reduce noise
  // FIXME: allow .json and .jpg...
  // allow .html and directories that become index.html
  if (strpos($r, '.jpg') === false && strpos($r, '.html') === false && $r[strlen($r) -1] !== '/') {
    echo "Skipping [$r] because no html/jpg extension\n";
    continue;
  }
  $data = $router->extractParams($r);
  $params = $data['params'];
  $skip = array ('id', 'videoid');
  if (count(array_intersect($params, $skip))) {
    continue;
  }
  if (empty($arr['options']['cacheSettings'])) {
    echo "r[$r] has no cacheSettings, skipping...\n";
    continue;
  }
  $cacheSettings = $arr['options']['cacheSettings'];
  //echo "r[$r] params[", print_r($params, 1), "]\n";
  if (in_array('uri', $params)) {
    foreach($boardURIs as $uri) {
      $p = array('uri' => $uri);
      if (in_array('num', $params)) {
        // 4chan/test/threads.json
        $catData = getBoardCatalog($uri);
        foreach($catData['pages'] as $pageData) {
          foreach($pageData['threads'] as $t) {
            $no = $t['no'];
            $p['num'] = $no;
            $route = str_replace(':uri', $uri, $r);
            $route = str_replace(':num', $no, $route);
            $path = routeToFile($route);
            if (shouldGenerateRoute($path, $cacheSettings, $p)) routeToDisk($route, $path);
          }
        }
        //print_r($catData);
        //print_r($catData['pages']);
        // get a list of thread numbers for the board
        //echo "Skipping [$r] because :num\n";
        continue;
      } else
      if (in_array('page', $params)) {
        // get a list of pages for board
        $catData = getBoardCatalog($uri);
        //print_r($catData['pages']);
        foreach($catData['pages'] as $pageData) {
          //echo "page[", $p['page'], "]\n";
          $pageNum = $pageData['page'];
          $p['page'] = $pageNum;
          $route = str_replace(':uri', $uri, $r);
          $route = str_replace(':page', $pageNum, $route);
          $path = routeToFile($route);
          if (shouldGenerateRoute($path, $cacheSettings, $p)) routeToDisk($route, $path);
        }
        //echo "Skipping [$r] because :page\n";
        continue;
      }
      $route = str_replace(':uri', $uri, $r);
      $path = routeToFile($route);
      if (shouldGenerateRoute($path, $cacheSettings, $p)) routeToDisk($route, $path);
    }
  } else
  if (in_array('theme', $params)) {
    // get a list of themes
    //echo "Skipping [$r] because :theme\n";
    $themeData = include '../common/modules/site/themes/shared.php';
    foreach($themeData['themes'] as $theme => $label) {
      $p = array('theme', $theme);
      $route = str_replace(':theme', $theme, $r);
      $path = routeToFile($route);
      //echo "route[$route] r[$r]\n";
      if (shouldGenerateRoute($path, $cacheSettings, $p)) routeToDisk($route, $path);
    }
    continue;
  } else {
    if (count($params)) {
      echo "r[$r] Unknown param[", join(',', $params), "]<br>\n";
      continue;
    }
    $path = routeToFile($r);
    if (shouldGenerateRoute($path, $cacheSettings, array())) routeToDisk($r, $path);
  }
  //echo "r[$r] [", join(',', $params), "]<br>\n";
}

// does it matter who owns these?
$username = posix_getpwuid(posix_geteuid())['name'];
if ($username !== USER) {
  // we need to fix perms
  recurse_chown_chgrp('static/', USER, USER);
}

?>