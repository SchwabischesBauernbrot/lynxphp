<?php

// route names - they're named by cond tbh (exec can locate it)
// route lastMod options - can be done in func
// decode security options into meaningful easy settings

// response abstraction wrapper
// might consider a class for this...
function make_text_response($text) {
  return array(
    'textResponse' => $text,
  );
}
function responseToText($response) {
  return $response['textResponse'];
}

class Router {
  function __construct() {
    $this->methods = array();
    $this->methods['GET']  = array();
    $this->methods['POST'] = array();
    $this->methods['HEAD'] = array();
    $this->methods['PUT'] = array();
    $this->methods['DELETE'] = array();
    $this->debug = array();
  }
  // used for attaching routers
  // usually star(/*) routes
  function all($cond, $func) {
    foreach(array_keys($this->methods) as $method) {
      $this->methods[$method][$cond] = $func;
    }
  }
  function fromResource($name, $res) {
    if (!isset($res['handlerFile'])) {
      return 'handlerFile is not set';
    }
    // endpoint could detect router...
    $cond = $res['endpoint'];
    $method = empty($res['method']) ? 'GET' : $res['method'];
    if ($method === 'AUTO') {
      if ($res['formData']) {
        $method = 'POST';
      } else {
        $method = 'GET';
      }
    }

    $func = function($request) use ($res) {
      // get session
      $user_id = null;
      if (!empty($res['sendSession'])) {
        $user_id = loggedIn();
        if (!$user_id) {
          return;
        }
      }
      // get ip
      $ip = null;
      if (!empty($res['sendIP'])) {
        $ip = getip();
      }

      // make pass a callback to handle response
      $sendResponse = function($request, $response, $next) use ($res) {
        $respText = responseToText($response);
        if ($res['unwrapData']) {
          sendResponse($respText);
        } else
        if ($res['expectJson']) {
          echo json_encode($respText);
        }
      };
      // create a single closure this file API can depend on
      $get = function() use ($user_id, $ip, $sendResponse) {
        // request?
        return array(
          'sendResponse' => $sendResponse,
          'userid' => $user_id,
          'ip' => $ip,
        );
      };
      // we could global $db, $models here too
      $intFunc = include $res['handlerFile'];
    };
    //echo "Installing [$method][$cond]<br>\n";
    switch($method) {
      case 'POST':
        $this->methods['POST'][$cond] = $func;
      break;
      case 'GET':
      default:
        $this->methods['GET'][$cond] = $func;
      break;
    }
    return true;
  }
  // we should know the method if we're using this route
  // and the method should be in the correct case
  // no need for defaults
  // I'd like to standardize around a file
  // but func is just more flexible
  // context can be set up in a func before the include
  function addMethodRoute($method, $cond, $func) {
    //echo "Installing [$method][$cond]<br>\n";
    $this->methods[$method][$cond] = $func;
  }
  // anything use this?
  function getExternal($group, $name, $cond, $file) {
    $key = $group.'_'.$name;
    $this->methods['GET'][$cond] = is_array($key, $file);
  }
  function get($cond, $func) {
    $this->methods['GET'][$cond] = $func;
  }
  function post($cond, $func) {
    $this->methods['POST'][$cond] = $func;
  }
  function debug($method = false) {
    if (!$method) {
      return print_r($this->methods, 1);
    }
    // did we match any routers?
    if (!empty($this->debug['router'])) {
      $router = $this->methods[$method][$this->debug['router']];
      return array(
        $method . '_routes' => $router,
        'router' => $this->debug['router']
      );
    }
    return array(
      $method . '_routes' => array_keys($this->methods[$method]),
    );
  }
  function exec($method, $path) {
    $methods = $this->methods[$method];
    $segments = explode('/', $path);

    $params = array();
    $request = array(
      'method' => $method,
      'originalPath' => $path,
      'path' => $path,
      'params' => $params,
    );
    $response = array(
    );
    // will be tough to do in php
    $next = function() {
    };

    //echo "path[$path] rules[", count($methods), "]<br>\n";

    // there should only be one match
    // the one match can have multiple calls...
    foreach($methods as $cond => $func) {
      //echo "rule[$cond]<br>\n";
      if ($path === $cond) {
        $func($request);
        return true;
      } else {
        $csegs = explode('/', $cond);
        //echo "AdvRule[$cond] pDirs[", count($segments), "] rDirs[", count($csegs), "]<br>\n";
        //echo "Rule has router[", strpos($cond, '*') !== false, "]<br>\n";
        // optimization?
        if (strpos($cond, '*') === false && count($csegs) !== count($segments)) {
          //echo "Skipping rule[$cond]<br>\n";
          continue;
        }
        $match = true;
        $params = array();
        //echo "Checking [$path] against [$cond]<br>\n";
        foreach($csegs as $i => $c) {
          //echo "[$i] [$c] vs [", $segments[$i], "]<br>\n";
          if (strlen($c) && $c === '*') {
            //echo "Router check<br>\n";
            $this->debug['router'] = $cond;
            // auto match the rest
            // could treat $func as a router and exec it here
            if (is_object($func)) {
              $request['params'] = $params;
              $tsegs = array();
              for($j = 0; $j < $i; $j++) {
                $tsegs[] = $segments[$j];
              }
              $usedPath = join('/', $tsegs) . '/'; // + 1 for the current
              // make sure it starts with /
              // even tho we just stripped it
              // since we can't remove / from /*
              $newPath = '/' . substr($path, strlen($usedPath));
              $request['path'] = $newPath;
              $res = $func->exec($request['method'], $newPath);
              return $res;
            }
            break;
          } else
          if (strlen($c) && $c[0] === ':') {
            $paramName = substr($c, 1);
            //print_r($segments);
            //echo "[$i] Building[$paramName] c[$c] seg[", $segments[$i], "]<br>\n";
            $params[$paramName] = $segments[$i];
            continue;
          }
          if (!isset($segments[$i]) || $segments[$i] !== $c) {
            $match = false;
            break;
          }
        }
        if ($match) {
          $request['params'] = $params;
          $func($request);
          return true;
        //} else {
          //echo "failed path[$path] cond[$cond]<Br>\n";
        }
      }
    }
    // can't handle 404 here because sometimes we return to another router
    return false;
  }
}

return new Router;

?>
