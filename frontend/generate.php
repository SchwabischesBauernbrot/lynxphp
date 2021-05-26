<?php
include '../common/post_vars.php';
// if executed from CLI, how do we know which config to use?

// TEMP: change change defines here...
//define('BASE_HREF', '/static');
global $BASE_HREF;

define('DEV_MODE', false);

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
include 'setup.php';
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
  ob_start();
  $router->exec('GET', $route);
  // could we place this correctly?
  $routeHTML = '<div style="height: 40px;"></div>'. "\n" . ob_get_clean();
  $cleanHTML = stripHTMLComments($routeHTML);
  echo "writing [$route] to [static/][$path][.html]\n";
  file_put_contents('static/' . $path . '.html', $cleanHTML);
}

function flushRoute($route) {
  $parts = explode('/', $route);
  $file = $parts[count($parts) - 1];
  if (!$route || $route[strlen($route) - 1] === '/') $file = 'index';
  echo "route[$route] file[$file] [", count($parts), "]<br>\n";
  if (count($parts) === 2) {
    routeToDisk($route, $file);
  } else {
    // dirname fails /BOARDURI/
    if ($route[strlen($route) - 1] === '/') {
      if (count($parts) === 3) {
        $dir = trim($route, '/'); // just remove all the slashes
        $file = 'index'; // probably dont' need to do this
      } else {
        echo "generate::flushRoute - count[", count($parts), "] - write me\n";
        return;
      }
    } else {
      $dir = ltrim(dirname($route), '/'); // we don't want the first /
    }
    echo "dir[$dir]\n";
    if (!file_exists('static/'. $dir)) {
      mkdir('static/'. $dir);
    }
    routeToDisk($route, $dir . '/' . $file);
  }
}

// this is important for high scale...
// we need to flush it out, so the core can support it

// build index.html
flushRoute('', 'index');

// when do we expire the homepage?
// - homepage backend route changes? (board listing order? site settings change)
// - templates change

// get a list all boards
// requires web server and backend to be running
$boards = getBoards();
$boardURIs = array_map(function($bData) {
  return $bData['uri'];
}, $boards['data']['boards']);

// can access pkg data
/*
global $packages;
foreach($packages as $n => $pkg) {
  foreach($pkg->frontend_packages as $fe) {
    if (!isset($fe->handlers['GET'])) continue;
    foreach($fe->handlers['GET'] as $r => $hndlr) {
      echo "r: $r\n";
      if (strpos($r, '/:') !== false) {
        foreach($boardURIs as $uri) {
          flushRoute(str_replace(':uri', $uri, $r));
        }
      } else {
        flushRoute($r);
      }
      print_r($hndlr);
    }
  }
}
*/

function shouldPurgeRoute() {
}

function shouldGenerateRoute() {
  return true;
}

// internal routes?
// setup will set up frontend routes only
// and that's what we want
// can access options...
foreach($router->methods['GET'] as $r => $f) {
  if (isset($router->frontendData['GET_' . $r]['route'])) {
    $rData = $router->frontendData['GET_' . $r]['route'];
    if (!empty($rData['loggedIn'])) continue;
    if (!empty($rData['dontGen'])) continue;
  }
  if (strpos($r, 'settings') !== false) continue;
  echo "r: $r\n";
  if (strpos($r, '/:') !== false) {
    if (strpos($r, '/:videoid') !== false) {
      continue;
    } else
    if (strpos($r, '/:id') !== false) {
      continue;
    } else
    if (strpos($r, '/:uri') !== false) {
      foreach($boardURIs as $uri) {
        if (strpos($r, '/:num') !== false) {
          continue;
        } else
        if (strpos($r, '/:page') !== false) {
          continue;
        } else {
          flushRoute(str_replace(':uri', $uri, $r));
        }
      }
    } else {
      echo "unknown vars [$r]\n";
    }
  } else {
    flushRoute($r);
  }
}

$username = posix_getpwuid(posix_geteuid())['name'];
//echo "username[$username]<br>\n";
if ($username !== USER) {
  // we need to fix perms
  recurse_chown_chgrp('static/', USER, USER);
}


?>