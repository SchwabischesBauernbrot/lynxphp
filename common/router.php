<?php

ldr_require('lib.units.php');
ldr_require('lib.http.response.php');

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
    $this->routeOptions = array();
    $this->debug = array();
    // save time on the backend or frontend?
    // frontend gets more hits... backend may have mobile to deal with
    $this->defaultContentType = 'text/html';
    $this->max_length = 0;
    $this->headersSent = false;
  }

  // FIXME: we need a file version for all
  // that only import the routes we need on demand

  // used for attaching subrouters
  // usually star(/*) routes
  function all($cond, $func) {
    //echo "INSTALLING [$cond] in ALL<br>\n";
    //print_r($this->methods);
    foreach(array_keys($this->methods) as $method) {
      //echo "INSTALLING [$cond] in [$method]<br>\n";
      $this->methods[$method][$cond] = $func;
    }
  }


  // we should know the method if we're using this route
  // and the method should be in the correct case
  // no need for defaults
  // I'd like to standardize around a file
  // but func is just more flexible
  // context can be set up in a func before the include
  // used by frontend packages
  function addMethodRoute($method, $cond, $func, $options = false) {
    //echo "Installing [$method][$cond]<br>\n";
    $this->methods[$method][$cond] = $func;
    //echo "options[", print_r($options, 1), "]\n";
    $this->routeOptions[$method . '_' . $cond] = $options;
  }
  /*
  function get($cond, $func, $options = false) {
    $this->methods['GET'][$cond] = $func;
    //echo "Installing GET_[$cond] options[", print_r($options, 1), "]<br>\n";
    $this->routeOptions['GET_' . $cond] = $options;
  }
  function post($cond, $func) {
    $this->methods['POST'][$cond] = $func;
  }
  */

  // FIXME: routes need unique names...

  // dynamic vs static (captcha/banners)
  // expiration: backend routes, files
  //
  // could be a factory
  // we could gather all _GET, _POST, params for us...
  //   no _POST without method being POST
  //   params are include
  //   we'd just need to define the querystring
  // also cleaning off .html or .json can be useful
  // providing db, models, tpp
  function import($routes, $module = 'unknown', $dir = 'handlers') {
    foreach($routes as $group => $groupData) {
      $adjDir = $dir; // reset
      if (isset($groupData['dir'])) {
        $adjDir = $dir . '/' . $groupData['dir'];
      }
      // groupData: file, routes
      foreach($groupData['routes'] as $routeData) {
        // routeData: func, options, loggedin(, method, route)
        $method = empty($routeData['method']) ? 'GET' : $routeData['method'];
        //echo "method[$method][", $routeData['route'], "]<br>\n";
        if (isset($this->methods[$method][$routeData['route']])) {
          echo "router::import - Warning, route already defined<br>\n";
        }
        $route = $routeData['route'];
        $this->methods[$method][$route] = function($request) use ($routeData, $groupData, $adjDir) {
          if (isset($routeData['file'])) {
            include $adjDir . '/' . $routeData['file'] . '.php';
            return;
          }
          $file = empty($groupData['file']) ? false : $groupData['file'];
          if ($file && empty($this->included[$file])) {
            include $adjDir . '/' . $file . '.php';
            $this->included[$file] = true;
          }
          if ($routeData['func']) {
            $func = $routeData['func'];
            $func($request);
          } else {
            echo "router::import - No function defined<br>\n";
          }
        };
        // promote options from routeData into the router internal structure
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
        // allow these outside of the options sub
        if (isset($routeData['cacheSettings'])) {
          $this->routeOptions[$methodRoute]['cacheSettings'] = $routeData['cacheSettings'];
          unset($routeData['cacheSettings']);
        }
        if (isset($routeData['loggedIn'])) {
          $this->routeOptions[$methodRoute]['loggedin'] = $routeData['loggedIn'];
          unset($routeData['loggedIn']);
        }
        $this->routeOptions[$methodRoute]['module'] = $module;
        $this->routeOptions[$methodRoute]['name'] = $group;
        if (isset($routeData['file'])) {
          $this->routeOptions[$methodRoute]['address'] = $routeData['file'];
        } else {
          $this->routeOptions[$methodRoute]['address'] = $routeData['func'] . '@' . $groupData['file'];
        }
      }
    }
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
  function isTooBig() {
    $this->max_length = min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
    // nginx always sets CONTENT_LENGTH, apache only passes when browser sets it
    $method = getServerField('REQUEST_METHOD', 'GET');
    if ($method !== 'POST') {
      return false; // not too big
    }
    return $_SERVER['CONTENT_LENGTH'] > $this->max_length;
  }
  function getMaxMtime($cacheSettings, $routeParams) {
    $mtime = 0;
    if (isset($cacheSettings['databaseTables'])) {
      global $db;
      $mtime = $db->getLast($cacheSettings['databaseTables']);
    }
    // could be promoted in the frontend router...
    if (isset($cacheSettings['backend'])) {
      $params = array();
      foreach($routeParams as $k => $v) {
        $params[':' . $k] = $v;
      }
      if (empty($params[':page'])) $params[':page'] = 1;
      foreach($cacheSettings['backend'] as $be) {
        //echo "checking[", print_r($be, 1), "] [", print_r($params, 1), "]\n";
        // interpolate
        $endpoint = str_replace(array_keys($params), array_values($params), $be['route']);
        $result = request(array(
          //'url' => 'http://localhost/backend/' . str_replace(array_keys($params), array_values($params), $be['route']),
          'url' => BACKEND_BASE_URL . $endpoint,
          'method' => 'HEAD',
        ));
        $headers = parseHeaders($result);
        if (!isset($headers['last-modified'])) {
          // if we don't have an anchor no way...
          echo "No way to cache backend[", $be['route'], "], no cacheSettings\n";
          return PHP_INT_MAX;
          continue;
        }
        $ts = strtotime($headers['last-modified']);
        $mtime = max($mtime, $ts);
      }
    }
    if (isset($cacheSettings['files'])) {
      //echo "in[$mtime]<br>\n";
      //print_r($routeParams);
      foreach($cacheSettings['files'] as $file) {
        foreach($routeParams as $param => $val) {
          if (is_array($val)) {
            echo "router::getMaxMitme error - params[$param] val[", print_r($val, 1), "] file[$file]\n";
          } else {
           $val = str_replace('.html', '', $val);
          }
          $file = str_replace('{{route.'. $param . '}}', $val, $file);
        }
        if (file_exists($file)) {
          $mtime = max($mtime, filemtime($file));
        } else {
          if (DEV_MODE) {
            echo "router:::getMaxMtime - file[$file] does not exist<br>\n";
          }
        }
      }
      //echo "out[$mtime]<br>\n";
    }
    return $mtime;
  }

  // do we have a cached copy
  function isUncached($key, $routeParams) {
    // no caching
    if (!isset($this->routeOptions[$key]['cacheSettings'])) {
      //echo "No cacheSettings for [$key]";
      //print_r($this->routeOptions);
      return true; // render content
    }
    //echo "key[$key]<br>\n";
    //print_r($this->routeOptions['cacheSettings'][$key]);
    $cacheSettings = $this->routeOptions[$key]['cacheSettings'];
    // need dbtables or files
    if (!isset($cacheSettings['databaseTables']) && !isset($cacheSettings['files'])) {
      //echo "No cacheSettings keys", print_r($cacheSettings);
      return true; // render content
    }
    // backend hack
    if (getQueryField('prettyPrint')) {
      $cacheSettings['contentType'] = 'text/html';
    }
    $options = array(
      // not sure application/json makes sense as a default
      // since most endpoints aren't going to be json...
      'contentType' => isset($cacheSettings['contentType']) ? $cacheSettings['contentType'] : $this->defaultContentType,
    );
    $mtime = $this->getMaxMtime($cacheSettings, $routeParams);
    global $now;
    $diff = $now - $mtime;
    //echo "last change[$diff]<br>\n";
    if (checkCacheHeaders($mtime, $options)) {
      // it's cached!
      // roughly 120ms rn
      // not any faster tbh
      return false;
    }
    return true; // render content
  }

  // call handler func
  function callHandler($match, $request, $isHead) {
    $params = $match['params'];
    // if (not cache) && (not head)
    // could just pass route
    if ($this->isUncached($request['method'] . '_' . $match['cond'], $params)) {
      if ($isHead) {
        //header('connection: close');
        return;
      }
      // move match into request
      $request['params'] = $params;
      $func = $match['func'];
      $func($request);
    }
  }

  // is path supposed to start with /? seems to be yes
  // determineRoute? run
  function findRoute($method, $path, $level = 0) {
    $isHead = false;
    if ($method === 'HEAD') {
      $method = 'GET';
      $isHead = true;
    }
    $methods = $this->methods[$method];
    // could strip & but that's non-standard
    $segments = explode('/', $path);
    //echo "router::exec[$level] - method[$method] path[$path] segments[", count($segments), "]<br>\n";

    $params = array();
    $request = array(
      // could pass isHead here and clear it later
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
        return array(
          'match' => array(
            'cond' => $cond, 'params' => array(), 'func' => $func,
           ),
          'isHead' => $isHead,
          'request' => $request
        );
      }
      $csegs = explode('/', $cond);
      //echo "router::exec[$level] - Rule has router[", strpos($cond, '*') !== false, "]<br>\n";
      //echo "router::exec[$level] - Rule[$cond] condCnt[", count($csegs), "] vs reqeustCnt[", count($segments), "]<br>\n";
      // optimization?
      // no * in route and the depth doesn't match
      //echo "cond[$cond] slashes[", count($csegs), "] request[", count($segments), "]<br>\n";
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
            //$request['params'] = $params;
            $tsegs = array();
            for($j = 0; $j < $i; $j++) {
              $tsegs[] = $segments[$j];
            }
            $usedPath = join('/', $tsegs) . '/'; // + 1 for the current
            // make sure it starts with /
            // even tho we just stripped it
            // since we can't remove / from /*
            $newPath = '/' . substr($path, strlen($usedPath));
            //$request['path'] = $newPath;
            //echo "newPath[$newPath]<br>\n";
            $res = $func->findRoute($request['method'], $newPath, $level + 1);
            return $res;
          }
          break;
        } else
        if (strlen($c) && $c[0] === ':') {
          $paramName = substr($c, 1);
          // ignore extensions
          $pos = strpos($paramName, '.');
          if ($pos !== false) {
            $ext = substr($paramName, $pos);
            $paramName = substr($paramName, 0, $pos);
            // remove extension from value too
            $segments[$i] = str_replace($ext, '', $segments[$i]);
          }
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
      if (0) {
        echo '<ul>', "\n";
        foreach($matches as $m) {
          echo '<li>', $m['cond'], '<pre>', print_r($m['params'], 1), '</pre>', "\n";
        }
        echo '</ul>', "\n";
      }
      if (count($matches) === 1) {
        return array(
          'match' => $matches[0],
          'isHead' => $isHead,
          'request' => $request
        );
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
          return array(
            'match' => $use,
            'isHead' => $isHead,
            'request' => $request
          );

        } else {
          // not sure, just use first
          return array(
            'match' => $matches[0],
            'isHead' => $isHead,
            'request' => $request
          );
        }
      }
    }
    // can't handle 404 here because sometimes we return to another router
    return false;
  }

  function sendHeaders($method, $path) {
    $res = $this->findRoute($method, $path);
    if ($res === false) return false; // 404 passthru
    $key = $res['request']['method'] . '_' . $res['match']['cond'];
    $uncached = $this->isUncached($key, $res['match']['params']);
    if ($res['isHead']) {
      //header('connection: close');
      return true;
    }
    $this->headersSent = true;
    return !$uncached;
  }

  // primary function of the router
  function exec($method, $path) {
    $res = $this->findRoute($method, $path);
    if ($res === false) return false; // 404 passthru
    if ($this->headersSent) {
      $request = $res['request'];
      // move match into request
      $request['params'] = $res['match']['params'];
      $func = $res['match']['func'];
      $func($request);
    } else {
      $this->callHandler($res['match'], $res['request'], $res['isHead']);
    }
    return true;
  }

}

?>