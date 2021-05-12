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
    $this->cacheSettings = array();
    $this->debug = array();
  }
  // used for attaching routers
  // usually star(/*) routes
  function all($cond, $func) {
    foreach(array_keys($this->methods) as $method) {
      $this->methods[$method][$cond] = $func;
    }
  }
  // only done on the backend...
  function fromResource($name, $res, $moduleDir) {
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

    $func = function($request) use ($res, $moduleDir) {
      // get session
      $user_id = null;
      if (!empty($res['sendSession'])) {
        $user_id = getUserID();
      }
      if (!empty($res['requireSession'])) {
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

      if (is_readable($moduleDir . 'shared.php')) {
        $shared = include $moduleDir . 'shared.php';
      }
      if (is_readable($moduleDir . 'be/common.php')) {
        $common = include $moduleDir . 'be/common.php';
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
  function addMethodRoute($method, $cond, $func, $cacheSettings = false) {
    //echo "Installing [$method][$cond]<br>\n";
    $this->methods[$method][$cond] = $func;
    $this->cacheSettings[$method . '_' . $cond] = $cacheSettings;
  }
  // anything use this? no, it's forward looking
  function getExternal($group, $name, $cond, $file) {
    $key = $group.'_'.$name;
    $this->methods['GET'][$cond] = is_array($key, $file);
  }
  function get($cond, $func, $cacheSettings = false) {
    $this->methods['GET'][$cond] = $func;
    $this->cacheSettings['GET_' . $cond] = $cacheSettings;
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
  function isCached($key, $routeParams) {
    if (!isset($this->cacheSettings[$key])) {
      //echo "No cacheSettings for [$key]";
      return true; // render content
    }
    //echo "key[$key]<br>\n";
    //print_r($this->cacheSettings[$key]);
    $cacheSettings = $this->cacheSettings[$key];
    if (!isset($cacheSettings['databaseTables']) && !isset($cacheSettings['files'])) {
      //echo "No cacheSettings keys", print_r($cacheSettings);
      return true; // render content
    }
    $mtime = 0;
    if (isset($cacheSettings['databaseTables'])) {
      global $db;
      $mtime = $db->getLast($cacheSettings['databaseTables']);
    }
    if (isset($cacheSettings['files'])) {
      //echo "in[$mtime]<br>\n";
      //print_r($routeParams);
      foreach($cacheSettings['files'] as $file) {
        foreach($routeParams as $param => $val) {
          $file = str_replace('{{route.'. $param . '}}', $val, $file);
        }
        if (file_exists($file)) {
          $mtime = max($mtime, filemtime($file));
        } else {
          if (DEV_MODE) {
            echo "router:::isCached - file[$file] does not exist<br>\n";
          }
        }
      }
      //echo "out[$mtime]<br>\n";
    }
    // backend hack
    if (getQueryField('prettyPrint')) {
      $cacheSettings['contentType'] = 'text/html';
    }
    $options = array(
      // not sure application/json makes sense as a default
      // since most endpoints aren't going to be json...
      'contentType' => isset($cacheSettings['contentType']) ? $cacheSettings['contentType'] : 'text/html',
    );
    if (checkCacheHeaders($mtime, $options)) {
      // it's cached!
      // roughly 120ms rn
      // not any faster tbh
      return false;
    }
    return true; // render content
  }
  function exec($method, $path, $level = 0) {
    $isHead = false;
    if ($method === 'HEAD') {
      $method = 'GET';
      $isHead = true;
    }
    $methods = $this->methods[$method];
    // could strip & but that's non-standard
    $segments = explode('/', $path);
    //echo "router::exec[$level] - path[$path] segments[", count($segments), "]<br>\n";

    $params = array();
    $request = array(
      'method' => $method,
      'originalPath' => $path,
      'path' => $path, // * route will truncate off previous router...
      'params' => $params,
    );
    $response = array(
    );
    // will be tough to do in php
    $next = function() {
    };

    //echo "router::exec[$level] - path[$path] rules[", count($methods), "]<br>\n";

    // there should only be one match
    // the one match can have multiple calls...
    $matches = array();
    foreach($methods as $cond => $func) {
      //echo "rule[$cond]<br>\n";
      if ($path === $cond) {
        $func($request);
        return true;
      }
      $csegs = explode('/', $cond);
      //echo "router::exec[$level] - Rule has router[", strpos($cond, '*') !== false, "]<br>\n";
      //echo "router::exec[$level] - Rule[$cond] condCnt[", count($csegs), "] vs reqeustCnt[", count($segments), "]<br>\n";
      // optimization?
      // no * in route and the depth doesn't match
      if (strpos($cond, '*') === false && count($csegs) !== count($segments)) {
        //echo "[$level] Skipping rule[$cond]<br>\n";
        continue;
      }
      $match = true;
      $params = array();
      //echo "Checking [$path] against [$cond]<br>\n";
      foreach($csegs as $i => $c) {
        //echo "[$i] [", $segments[$i], "] vs [$c]<br>\n";
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
            $res = $func->exec($request['method'], $newPath, $level + 1);
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
        /*
        echo "condCnt[", count($csegs), "] vs reqeustCnt[", count($segments), "]<br>\n";
        if (count($csegs) !== count($segments)) {
          $match = false;
          break;
        }
        */
        // is segment is missing or they don't match...
        //echo "[$i] segment[", $segments[$i], "] =? [", $c, "]<br>\n";
        if (!isset($segments[$i]) || $segments[$i] !== $c) {
          //echo "router - path[$path] did not matched[$cond]<br>\n";
          $match = false;
          break; // stop cseg check
        }
      }
      if ($match) {
        //echo "router - path[$path] matched[$cond]<br>\n";
        $matches[] = array(
          'cond' => $cond,
          'params' => $params,
          'func' => $func
        );
      }
    }
    if (count($matches)) {
      //echo "<pre>", print_r($matches, 1), "</pre>\n";
      if (count($matches) === 1) {
        $func = $matches[0]['func'];
        $request['params'] = $matches[0]['params'];
        // if (not cache) && (not head)
        if ($this->isCached($method . '_' . $matches[0]['cond'], $matches[0]['params']) && !$isHead) {
          $func($request);
        }
        return true;
      } else {
        $use = false;
        $minScore = 99;
        foreach($matches as $c => $row) {
          $score = levenshtein($row['cond'], $path);
          if ($score < $minScore) {
            $use = $row;
            $minScore = $score;
          }
          //echo "[$c][", print_r($row, 1), "]=[$score]<br>\n";
        }
        if ($use) {
          $func = $use['func'];
          $request['params'] = $use['params'];
          if ($this->isCached($method . '_' . $use['cond']) && !$isHead) {
            $func($request);
          }
        } else {
          // not sure, just use first
          $func = $matches[0]['func'];
          $request['params'] = $matches[0]['params'];
          if ($this->isCached($method . '_' . $matches[0]['cond']) && !$isHead) {
            $func($request);
          }
        }
        return true;
      }
    }
    // can't handle 404 here because sometimes we return to another router
    return false;
  }
}

return new Router;

?>
