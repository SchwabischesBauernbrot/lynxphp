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

// backend support
if (!defined('DEV_MODE')) define('DEV_MODE', false);

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

  // used for attaching subrouters
  // usually star(/*) routes
  function all($cond, $router) {
    //echo "INSTALLING [$cond] in ALL<br>\n";
    //print_r($this->methods);
    foreach(array_keys($this->methods) as $method) {
      //echo "INSTALLING [$cond] in [$method]<br>\n";
      $this->methods[$method][$cond] = $router;
    }
    /*
    // copy over options
    foreach($router->routeOptions as $methodCond => $opt) {
      // FIXME: we need to stitch together /opt and /boards
      // so we know the difference between /4chan and /boards..
      $this->routeOptions[$methodCond] = $opt;
    }
    */
  }

  // only import the routes we need on demand
  // could have a file per method
  // though we need a function that list them all

  // we should know the method if we're using this route
  // and the method should be in the correct case
  // no need for defaults

  // we need a file version for all
  // but func is just more flexible

  // context can be set up in a func before the include
  // used by frontend packages
  function addMethodRoute($method, $cond, $func, $options = false) {
    /*
    if (isset($this->methods[$method][$cond])) {
      echo "router::addMethodRoute - Warning, route already defined<br>\n";
    }
    */
    //echo "Installing [$method][$cond]<br>\n";
    $this->methods[$method][$cond] = $func;
    //echo "options[", print_r($options, 1), "]\n";
    /*
    if (isset($this->routeOptions[$method . '_' . $cond])) {
      echo "router::addMethodRoute - Warning, routeOptions defined<br>\n";
    }
    */
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
    // foreach group
    foreach($routes as $group => $groupData) {
      $adjDir = $dir; // reset
      if (isset($groupData['dir'])) {
        $adjDir = $dir . '/' . $groupData['dir'];
      }
      // groupData: file, routes, portal
      // foreach route in group
      foreach($groupData['routes'] as $routeData) {
        // routeData: func, options, loggedin(, method, route)
        $method = empty($routeData['method']) ? 'GET' : $routeData['method'];
        $route = $routeData['route'];
        if (0) {
          echo "method[$method][", $route, "]";
          if (isset($routeData['file'])) {
            echo "file[", $routeData['file'], "]\n";
          }
          echo "<br>\n";
        }
        if (isset($this->methods[$method][$route])) {
          echo "router::import - Warning, route already defined<br>\n";
        }
        $methodRoute = $method . '_' . $route;
        // create a function that loads and calls the route func from the group file
        // capture state
        $this->methods[$method][$route] = function($request) use ($routeData, $groupData, $adjDir, $methodRoute) {
          //echo "request[", print_r($request, 1), "]<br>\n";
          //echo gettrace();
          if (isset($routeData['file'])) {
            include $adjDir . '/' . $routeData['file'] . '.php';
            return;
          }
          $file = empty($groupData['file']) ? false : $groupData['file'];
          if ($file && empty($this->included[$file])) {
            include $adjDir . '/' . $file . '.php';
            $this->included[$file] = true;
          }
          // inject portal data
          $request['portals'] = isset($groupData['portals']) ? $groupData['portals'] : array();
          // future conduit for passing more information...
          // probably should unset cacheSettings
          // module, name, address
          $request['routeOptions'] = $this->routeOptions[$methodRoute];
          if ($routeData['func']) {
            $func = $routeData['func'];
            $func($request);
          } else {
            echo "router::import - No function defined<br>\n";
          }
        };
        // trade some cpu for memory
        if (isset($routeData['route'])) unset($routeData['route']);
        if (isset($routeData['method'])) unset($routeData['method']);

        // promote options from routeData into the router internal structure
        if (isset($routeData['options'])) {
          $this->routeOptions[$methodRoute] = $routeData['options'];
        }
        // normalize some options
        //if (!isset($routeData['options'])) $routeData['options'] = array();
        //if (!isset($routeData['loggedIn'])) $routeData['loggedIn'] = false;


        // move loggedIn and cacheSettings into routeOptions?
        // allow these outside of the options sub
        if (isset($routeData['cacheSettings'])) {
          //echo "<pre>Promoting [$methodRoute] cacheSettings[", print_r($routeData['cacheSettings'], 1), "]</pre>\n";
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
        /*
        if (isset($groupData['portal'])) {
          $this->routeOptions[$methodRoute]['portal'] = $groupData['portal'];
        }
        */
      }
    }
    //echo "done import<br>\n";
    //echo "<pre>routeOption keys[", print_r(array_keys($this->routeOptions), 1), "]</pre>\n";
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
  // maybe should be a handler we set
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
/*
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
          // should only show if in DEV_MODE but backend doesn't have a dev mode...
          echo "No way to cache backend[", $be['route'], "], no cacheSettings\n";
          return PHP_INT_MAX;
          continue;
        }
        $ts = strtotime($headers['last-modified']);
        $mtime = max($mtime, $ts);
      }
    }
*/
    //print_r($cacheSettings);
    if (isset($cacheSettings['files'])) {
      //echo "in[$mtime]<br>\n";
      //print_r($routeParams);
      foreach($cacheSettings['files'] as $file) {
        //echo "file[$file]<br>\n";
        foreach($routeParams as $param => $val) {
          if (is_array($val)) {
            echo "router::getMaxMitme error - params[$param] val[", print_r($val, 1), "] file[$file]\n";
          } else {
            $val = str_replace('.html', '', $val);
          }
          $file = str_replace('{{route.'. $param . '}}', $val, $file);
        }
        //echo "adjFile[$file]<br>\n";
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
  // , $alwaysSetHeaders = false
  function isUncached($key, $routeParams, $routeOptions) {
    //if (DEV_MODE) {
    $cacheable = isset($routeOptions['cacheSettings']);
    //header('X-Debug-isUncached: ' . $key . '-' . ($cacheable ? 'cacheable' : 'not'));
    //}
    // no caching
    if (!$cacheable) {
      //echo "No cacheSettings for [$key]";
      //echo "<pre>cacheSettings", print_r($cacheSettings, 1), "</pre>";
      //echo "<pre>this->routeOptions", print_r($this->routeOptions, 1), "</pre>";
      //print_r($this->routeOptions);
      header('X-Debug-isUncached: ' . $key . '-no_cacheSettings');
      return true; // render content
    }
    //echo "<pre>routeOptions", print_r($routeOptions, 1), "</pre><br>\n";

    //echo "key[$key]<br>\n";
    //print_r($this->routeOptions['cacheSettings'][$key]);
    //$cacheSettings = $this->routeOptions[$key]['cacheSettings'];
    $cacheSettings = $routeOptions['cacheSettings'];
    //echo "<pre>cacheSettings", print_r($cacheSettings, 1), "</pre><br>\n";

    // have something useable...
    if (!isset($cacheSettings['databaseTables']) && !isset($cacheSettings['files'])
       && !isset($cacheSettings['backend'])) {
      //echo "No cacheSettings keys", print_r($cacheSettings);
      header('X-Debug-isUncached: ' . $key . '-no_useable_cacheSettings');
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

    // frontend only thing
    // lets move the HEAD requests upfront here
    // so we can pass the result to etag engine too

    // plan for the worst
    // FIXME: rename check to canUse
    $checkMtime = false;
    $checkEtag = false;
    // accumulators
    // should this be PHP_INT_MAX?
    // zero means we don't need to check backend
    // for BE, we need this to be 0
    $maxMtime = 0;
    $compoundEtags = array();

    if (BACKEND_HEAD_SUPPORT && isset($cacheSettings['resource'])) {
      // upgrade resources to backend...
      //print_r($cacheSettings);
      foreach($cacheSettings['resource'] as $r) {
        //$r = 'homepage'
        // key GET_ params is []
        //echo "key[$key] params[", print_r($routeParams, 1), "]<br>\n";
        //echo "<pre>", htmlspecialchars(print_r($routeOptions, 1)), "</pre>\n";
        // $routeOptions
        // .module = site_homepage
        // .address = homepage
        global $packages;
        foreach($packages[$routeOptions['module']]->resources as $name => $data) {
          // is this the right resource in the package
          if ($name === $r) {
            // there will only be one
            //print_r($data);
            if (!isset($cacheSettings['backend'])) $cacheSettings['backend'] = array();
            $cacheSettings['backend'][] = array(
              'route' => $data['endpoint'],
            );
          }
        }
      }
    }

    // This is require for frontend (since be needs it to be 0)
    // does this routes data change depending on BE calls?
    // need to ignore whether or not config tells us to check it
    // this is really important for setting headers on FE
    // was isset($cacheSettings['backend'])
    global $BASE_HREF;
    // detect frontend
    if ($BASE_HREF) {
      // mtime matters, we were supposed to check the backend
      // but it doesn't support it, we can't get mtime
      // so it's not useable

      // this will force no cache
      // because we should be dependent upon backend
      // but it can't provide (or we don't check)
      // if we cached it, it would likely be wrong
      $maxMtime = PHP_INT_MAX;
    }

    // why don't we get a warning about BACKEND_HEAD_SUPPORT not being set?
    if (BACKEND_HEAD_SUPPORT && isset($cacheSettings['backend'])) {
      $params = array();
      foreach($routeParams as $k => $v) {
        $params[':' . $k] = $v;
      }
      if (empty($params[':page'])) $params[':page'] = 1;
      //echo '<pre>', print_r($cacheSettings['backend'], 1), '</pre>', "\n";
      // hope for the best
      $checkMtime = true;
      $checkEtag = true;
      $maxMtime = 0; // reset so max() works
      // prevent double look ups in the same request
      global $_HEAD_CACHE;
      // ask backend when it was last-modified/etag
      foreach($cacheSettings['backend'] as $be) {
        //echo "checking[", print_r($be, 1), "] [", print_r($params, 1), "]\n";
        // interpolate
        $endpoint = str_replace(array_keys($params), array_values($params), $be['route']);
        // maybe log this? I could see it being helpful
        // isn't this pointless without an etag or last-modified
        // no, because it can set those without being asked
        // we don't need to save bw here because we're same byte cost as a 304

        // if we send our last timestamp
        // we might get a 304 and less database calls would happened
        // because it wouldn't need to render the content
        global $scratch;
        $headHeaders = array();
        $check = false;
        if ($scratch) {
          // fe only
          $key = BACKEND_BASE_URL . $endpoint;
          $check = $scratch->get('consume_beRsrc_' . $key);
          if (!empty($check['ts'])) {
            $headHeaders['If-Modified-Since'] = gmdate('D, d M Y H:i:s', $check['ts']) . ' GMT';
            $headHeaders['router-ts'] = $check['ts'];
          }
        }

        $result = request(array(
          //'url' => 'http://localhost/backend/' . str_replace(array_keys($params), array_values($params), $be['route']),
          'url'     => BACKEND_BASE_URL . $endpoint,
          'method'  => 'HEAD',
          'headers' => $headHeaders,
        ));
        // if we get a 304, we may not have the headers...
        $code = substr($result, 9, 3);
        //echo "code[$code]<br>\n";
        if ($code === '304' && $check) {
          if (isset($check['etag'])) {
            $etag = $check['etag'];
            $_HEAD_CACHE[$endpoint]['etag'] = $etag;
          }
          if (isset($check['ts'])) {
            $ts = $check['ts'];
            $_HEAD_CACHE[$endpoint]['last-modified'] = $headHeaders['If-Modified-Since'];
            $maxMtime = max($maxMtime, $ts);
          }
        } else {
          $headers = parseHeaders($result);
          $_HEAD_CACHE[$endpoint] = $headers;
          //echo "<pre>header", htmlspecialchars(print_r($headers, 1)), "</pre>\n";

          // check the interesting header

          // etag
          $etag = empty($headers['etag']) ? false : $headers['etag'];
          if ($checkEtag) {
            if ($etag) {
              $compoundEtags[] = $etag;
            } else {
              $checkEtag = false;
              $compoundEtags = array(); // release some memory
              // no dev warnings needed as this is a edge case...
            }
          }
          // last-modified
          if ($checkMtime) {
            if (isset($headers['last-modified'])) {
              $ts = strtotime($headers['last-modified']);
              //header('X-Debug-isUncached-mtime: ' . $ts);
              $maxMtime = max($maxMtime, $ts);
            } else {
              if (DEV_MODE) {
                if ($etag) {
                  //echo "No last-modified on backend[", $be['route'], "]\n";
                } else {
                  echo "No way to cache backend[", $be['route'], "], no cacheSettings on backend?\n";
                }
              }
              if ($checkMtime) {
                // this is the one that failed it...
                header('X-Debug-isUncached-backendFailure: ' . $endpoint);
              }
              $checkMtime = false;
              $maxMtime = PHP_INT_MAX;
              // if this one doesn't have it, we're done, it's not mtime cacheable
              // but we still need to check the rest for eTag now..
            }
          }


          // if both cache systems failed, we don't need to check any more
          if (!$checkMtime && !$checkEtag) {
            header('X-Debug-isUncached-backend-status: no-way-to-cache');
            if (DEV_MODE) {
              echo "No way to cache this frontend route\n";
            }
            break;
          }
        }
      }
      header('X-Debug-isUncached-febemtime: ' . ($checkMtime ? 'use' : 'ignore'));
      //header('X-Debug-isUncached-mtime: ' . $maxMtime);
      header('X-Debug-isUncached-febeeTag: ' . ($checkEtag ? 'use' : 'ignore'));
    }
    //if (DEV_MODE) {
    //}
    //header('X-Debug-isUncached-maxMtime: ' . $maxMtime);

    // fe and be only thing
    $mtime = 0;
    $eTag = '';

    //echo "maxMtime[$maxMtime] checkEtag[$checkEtag]<br>\n";

    // if some way to cache is available (mtime or etag)
    if ($maxMtime !== PHP_INT_MAX || $checkEtag) {
      // get the other timestamps involved
      // has some be only things
      $mtime = $this->getMaxMtime($cacheSettings, $routeParams);
      header('X-Debug-isUncached-actualMtime: ' . $mtime . '_was_' . $maxMtime);

      // see if we need to mixin the max BE data timestamp
      if ($maxMtime && $maxMtime !== PHP_INT_MAX) {
        $mtime = max($mtime, $maxMtime);
      }
      if ($checkEtag) {
        //echo "etag system[$mtime] [", count($compoundEtags), "]<br>\n";
        $eTag = sha1($mtime . '@' . join(',', $compoundEtags));
        // reset mtime if we can't use it
        if ($maxMtime === PHP_INT_MAX) $mtime = 0;
      }
    } else {
      header('X-Debug-isUncached: noEtag-Or-febePHP_INT_MAX');
    }
    //header('X-Debug-isUncached-finalMtime: ' . $mtime);

    // is cacheable in some form
    if (($mtime && $mtime !== PHP_INT_MAX) || $eTag) {
      //global $now;
      //$diff = $now - $mtime;
      //echo "last change[$diff]<br>\n";
      $cacheHeaderOptions = $options;

      // inject etag if needed
      if ($eTag) {
        $cacheHeaderOptions['etag'] = $eTag;
      }

      //echo "mtime[$mtime] cacheHeaderOptions[", print_r($cacheHeaderOptions, 1), "]<br>\n";
      if (!$mtime) {
        header('X-Debug-isUncached-mtimeFalish: ' . $mtime);
      }
      // 304 processing
      header('X-Debug-isUncached-settings: ' . $mtime.'_'.$eTag);
      if (checkCacheHeaders($mtime, $cacheHeaderOptions)) {
        // it's cached!
        // roughly 120ms rn
        // not any faster tbh
        return false;
      }
      // not cached
      header('X-Debug-isUncached-notcached: clientDidNotRequest');
      // frontend only, right now...
      /*
      if ($alwaysSetHeaders) {
        // looks like checkCacheHeaders already alwaysSetHeaders
        // mtime is 0? we have etag
        // if !mtime, we don't set LastMod
        header('X-Debug-isUncached: setting_mtime_' . $mtime.'_'.$eTag);
        //_doHeaders($mtime, $cacheHeaderOptions);
      }
      */
    } else {
      header('X-Debug-isUncached: noEtag-Or-PHP_INT_MAX');
    }

    return true; // render content
  }

  // match: func, params
  // alternatively we could take $res
  function callHandlerFunc($match, $request) {
    $request['params'] = $match['params'];
    //$request['routeOptions'] = $this->routeOptions[$key];
    $func = $match['func'];
    // if we pushed a response handler
    // we could attach additional layout stuff and processing
    $func($request);
  }

  // call cachable handler func
  function callCachableHandler($res) {
    $match = $res['match'];
    $request = $res['request'];
    $isHead = $res['isHead'];

    $params = $match['params'];
    // if (not cache) && (not head)
    // could just pass route
    $key = $request['method'] . '_' . $match['cond'];
    // do we need to do work or not
    if ($this->isUncached($key, $params, $res['routeOptions'])) {
      if ($isHead) {
        //header('connection: close');
        return;
      }
      $this->callHandlerFunc($match, $request);
    }
  }

  // is path supposed to start with /? seems to be yes
  // determineRoute? run
  // since we call another router
  // we need to communicate the exact cacheOptions
  function findRoute($method, $path, $level = 0) {
    $isHead = false;
    if ($method === 'HEAD') {
      $method = 'GET';
      $isHead = true;
    }
    $methods = $this->methods[$method];
    $originalPath = $path; // back it up

    // can ? be used in a filename? no
    if (strpos($path, '?') !== false) {
      list($path, $querystring) = explode('?', $path, 2);
    }
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
        // kill warning
        if (!isset($this->routeOptions[$method . '_' . $cond])) $this->routeOptions[$method . '_' . $cond] = false;
        return array(
          'match' => array(
            'cond' => $cond, 'params' => array(), 'func' => $func,
           ),
          'isHead' => $isHead,
          'request' => $request,
          'routeOptions' => $this->routeOptions[$method . '_' . $cond],
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

      // default to first
      $match = $matches[0];
      if (count($matches) !== 1) {
        $minScore = 99;
        foreach($matches as $c => $row) {
          $score = levenshtein($row['cond'], $path);
          if ($score < $minScore) {
            $match = $row;
            $minScore = $score;
          }
          //echo "[$c][", print_r($row, 1), "]=[$score]<br>\n";
        }
      }
      //echo "key[", $method . '_' . $match['cond'], "]<br>\n";
      //echo "<pre>[", print_r($this->routeOptions, 1), "]</pre>\n";
      $routeOptions = isset($this->routeOptions[$method . '_' . $match['cond']]) ? $this->routeOptions[$method . '_' . $match['cond']] : array();
      return array(
        'match' => $match,
        'isHead' => $isHead,
        'request' => $request,
        // is already in request...
        //'method' => $method,
        'routeOptions' => $routeOptions,
      );

    }
    // can't handle 404 here because sometimes we return to another router
    return false;
  }

  // index calls this to see if our output is cacheable
  function sendHeaders($method, $path) {
    $res = $this->findRoute($method, $path);
    if ($res === false) return false; // 404 passthru
    $key = $res['request']['method'] . '_' . $res['match']['cond'];
    $uncached = $this->isUncached($key, $res['match']['params'], $res['routeOptions']);
    // we only run in the frontend
    if (DEV_MODE) {
      //header('X-Debug-sendHeaders-key: ' . $key);
      header('X-Debug-sendHeaders-cache: ' . ($uncached ? 'miss' : 'hit'));
    }
    // HEAD can only return headers
    if ($res['isHead']) {
      //header('connection: close');
      //if (DEV_MODE) {
        //header('X-Debug-sendHeaders-isHead: true');
      //}
      // don't need to process content
      return true;
    }
    $this->headersSent = true;
    return !$uncached;
  }

  // primary function of the router
  // FIXME: option to release all other methods (maybe set in the route definition itself)
  function exec($method, $path) {
    $res = $this->findRoute($method, $path);
    if ($res === false) return false; // 404 passthru
    if ($this->headersSent) {
      // definitely not a 304
      $this->callHandlerFunc($res['match'], $res['request']);
    } else {
      // potentially handle 304s
      $this->callCachableHandler($res);
    }
    return true;
  }

}

?>