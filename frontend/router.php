<?php

include '../common/router.php';

class FrontendRouter extends Router {
  function __construct() {
    parent::__construct();
    //$this->defaultContentType = 'text/html';
    $this->included = array();
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
  function extractParams($route) {
    //echo "route[$route]<br>\n";
    $parts = explode('/', $route);
    $params = array();
    $tokens = array();
    foreach($parts as $section) {
      if ($section && $section[0] === ':') {
        $p = substr($section, 1);
        // ignore extensions
        $pos = strpos($p, '.');
        if ($pos !== false) {
          $p = substr($p, 0, $pos);
        }
        $params[] = $p;
        $tokens[] = array($p);
      } else {
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
  function getMethodRouteData($method, $options = false) {
    extract(ensureOptions(array(
      'skipLoggedIn' => false,
      'skipDontGen' => false,
    ), $options));
    //echo "skipLoggedIn[$skipLoggedIn]\n";
    //echo "skipDontGen[$skipDontGen]\n";
    $res = array();
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
}

return new FrontendRouter;
?>