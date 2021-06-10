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
  // dynamic vs static (captcha/banners)
  // expiration: backend routes, files
  function import($routes) {
    foreach($routes as $group => $groupData) {
      // groupData: file, routes
      foreach($groupData['routes'] as $routeData) {
        // routeData: func, options, loggedin(, method, route)
        $method = empty($routeData['method']) ? 'GET' : $routeData['method'];
        //echo "method[$method][", $routeData['route'], "]<br>\n";
        if (isset($this->methods[$method][$routeData['route']])) {
          echo "frontend_router::import - Warning, route already defined<br>\n";
        }
        $route = $routeData['route'];
        $this->methods[$method][$route] = function($request) use ($routeData, $groupData) {
          $file = empty($groupData['file']) ? false : $groupData['file'];
          if ($file && empty($this->included[$file])) {
            include '../frontend_lib/handlers/' . $file . '.php';
            $this->included[$file] = true;
          }
          if ($routeData['func']) {
            $func = $routeData['func'];
            $func($request);
          } else {
            echo "No function defined<br>\n";
          }
        };
        if (isset($routeData['options'])) {
          $this->routeOptions[$method . '_' . $routeData['route']] = $routeData['options'];
        }
        // trade some cpu for memory
        if (isset($routeData['route'])) unset($routeData['route']);
        if (isset($routeData['method'])) unset($routeData['method']);
        // normalize some options
        //if (!isset($routeData['options'])) $routeData['options'] = array();
        //if (!isset($routeData['loggedIn'])) $routeData['loggedIn'] = false;

        $methodRoute = $method . '_' . $route;

        // move loggedIn and cacheSettings into routeOptions?
        if (isset($routeData['cacheSettings'])) {
          $this->routeOptions[$methodRoute]['cacheSettings'] = $routeData['cacheSettings'];
          unset($routeData['cacheSettings']);
        }
        if (isset($routeData['loggedIn'])) {
          $this->routeOptions[$methodRoute]['loggedin'] = $routeData['loggedIn'];
          unset($routeData['loggedIn']);
        }
        $this->routeOptions[$methodRoute]['module'] = 'frontend';
        $this->routeOptions[$methodRoute]['name'] = $group;
        $this->routeOptions[$methodRoute]['address'] = $routeData['func'] . '@' . $groupData['file'];
      }
    }
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
      if (isset($this->frontendData[$method . '_' . $r]['route'])) {
        $rData = $this->frontendData[$method . '_' . $r]['route'];
        //print_r($rData);
        if ($skipLoggedIn && !empty($rData['loggedIn'])) continue;
        if ($skipDontGen && !empty($rData['dontGen'])) continue;
      }
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