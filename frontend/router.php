<?php

include '../common/router.php';

class FrontendRouter extends Router {
  function __construct() {
    parent::__construct();
    //$this->defaultContentType = 'text/html';
    $this->included = array();
    if (DEV_MODE) {
      $this->dev = true;
    }
    $this->debugLog = array();
    /*
    $this->pkg = false;
    $this->fepkg = false;
    $this->handlerData = false;
    */
  }
  /*
  function fromResource($name, $res, $moduleDir) {
    // front doesn't need to set up backend routes
  }
  */
  function import($routes, $module = 'frontend', $dir = '../frontend_lib/handlers') {
    return parent::import($routes, $module, $dir);
  }

  // should extractTokens be a separate function?
  // not even used?
  // parses string, deliminated by /
  function extractParams($route) {
    //echo "route[$route]<br>\n";
    $parts = explode('/', $route);
    $params = array();
    $tokens = array();
    foreach($parts as $section) {
      if ($section && $section[0] === ':') {
        // if param goes into tokens as an array wtthout extension
        $p = substr($section, 1);
        // ignore extensions
        $pos = strpos($p, '.');
        if ($pos !== false) {
          $p = substr($p, 0, $pos);
        }
        $params[] = $p;
        $tokens[] = array($p);
      } else {
        // if static goes into tokens as a string
        // FIXME probably should strip extensions here too?
        $tokens[] = $section;
      }
    }
    //echo "parts[", print_r($parts, 1), "]\n";
    //echo "params[", print_r($params, 1), "]\n";
    return array(
      'params' => $params,
      'tokens' => $tokens,
    );
  }
  // called by getRouteData
  function getMethodRouteData($method, $options = false) {
    extract(ensureOptions(array(
      'skipLoggedIn' => false,
      'skipDontGen' => false,
    ), $options));
    //echo "skipLoggedIn[$skipLoggedIn]\n";
    //echo "skipDontGen[$skipDontGen]\n";
    $res = array();
    // for all routes in this method
    foreach($this->methods[$method] as $r => $f) {
      $rOpts = array();
      if (isset($this->routeOptions[$method . '_' . $r])) {
        $rOpts = $this->routeOptions[$method . '_' . $r];
        //print_r($rOpts);
        // for modules
        if ($skipLoggedIn && !empty($rOpts['loggedIn'])) continue;
        if ($skipDontGen && !empty($rOpts['dontGen'])) continue;
      }
      // param parsing
      $res[$r] = array(
        'f' => $f,
        //'fe' => $rData,
        'options'  => $rOpts,
        // can be individually called if needed
        //'params' => $this->extractParams($r),
      );
    }
    return $res;
  }
  // generate.php calls this
  function getRouteData($options = false) {
    extract(ensureOptions(array(
      'method' => 'ALL',
    ), $options));
    $res = array();
    if ($method === 'ALL') {
      foreach(array_keys($this->methods) as $m) {
        $res[$m] = $this->getMethodRouteData($m, $options);
      }
    } else {
      $res[$method] = $this->getMethodRouteData($method, $options);
    }
    return $res;
  }

/*
  function setPkg($pkg, $ref, $row) {
    $this->pkg = $pkg;
    $this->fepkg = $ref;
    $this->handlerData = $row;
  }

  function modifyResource(&$rsrc, &$params) {
    // get router state
    // insert portal params
  }

  function getHandlerOptions() {
    // get router state
  }
*/

  /*
  function debug($method = false) {
    $data = parent::debug($method);
    if (DEV_MODE) {
      router_log_report();
    }
    return $data;
  }
  */
}

function router_log_report() {
  global $router;
  //echo "<h2>router dump</h2>";
  // methods, debug, defaultContentType, max_length, dev, headersSent, included
  // routeOptions
  //echo "<pre>", print_r($router->debug($_SERVER['REQUEST_METHOD']), 1), "</pre>\n";
  //echo "<pre>", print_r($router->debugLog, 1), "</pre>\n";
  if (!count($router->debugLog)) {
    echo "Router has no debug<br>\n";
    echo '<div style="height: 50px;" id="bump"></div>', "\n"; flush();
    return;
  }
  echo '<details>
    <summary>matched route</summary>
  <ul>', "\n";
  foreach($router->debugLog['matching'] as $m) {
    echo '<li>', $m['cond'], '<pre>', print_r($m['params'], 1), '</pre>', "\n";
  }
  echo '</ul></details>', "\n";
  echo '<h3>Choosen route</h3>';
  // match: cond, params, func
  // isHead: bool?
  // request: method, originalPath, path, params
  // routeOptions: cacheSettings, loggedIn, portals, module, address
  //echo "<pre>match", print_r(array_keys($router->debugLog['match']['match']), 1), "</pre>\n";
  //echo "<pre>isHead", print_r(array_keys($router->debugLog['match']['isHead']), 1), "</pre>\n";
  //echo "<pre>request", print_r(array_keys($router->debugLog['match']['request']), 1), "</pre>\n";
  //echo "<pre>request", print_r($router->debugLog['match']['request'], 1), "</pre>\n";
  $name = $router->debugLog['match']['routeOptions']['module'];
  echo "Module: ", $name, "<br>\n";
  echo "File: ", $router->debugLog['match']['routeOptions']['address'], "<br>\n";
  global $packages;
  if ($packages[$name]) {
    echo $packages[$name]->toString();
  } else {
    echo "Unknown module<br>\n";
  }
  //echo "<pre>routeOptions", print_r(($router->debugLog['match']['routeOptions']), 1), "</pre>\n";
  // user should hook into the footer system
  echo '<div style="height: 50px;" id="bump"></div>', "\n"; flush();
}

return new FrontendRouter;
?>