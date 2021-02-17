<?php

/*
class backend_resource {
  var $endpoint;
  var $method;
  // post data
  // headers
  var $sendSession;
  var $sendIP;
  // response
  var $expectJson;
  function __construct($name, $params, $func) {
    $this->method = 'AUTO';
  }
  function use() {
  }
}
*/

// frontend usually routes wrap around these...
// so we can't just add more frontend resources
// we need to attach a frontend to it?
// and we don't need frontend attachments here...
// there could be some benefits of documenting the frontend routes here...
// here was pkg.json/module index

// a owner of a collection of pipeline_modules...
class package {
  var $ver;
  var $resources;
  function __construct($name, $ver, $dir) {
    $this->ver = $ver;
    $this->name = $name;
    // we should understand the path of the module...
    $this->dir = $dir . '/'; // ends in trailing slash...
    $this->resources = array();
    // should we register with something? not now...
    $this->frontend_packages = array();
    $this->backend_packages = array();
    $this->common = array(); // optional common data
  }
  // should we make a frontend_package/backend_package
  // no because they're optional and could have more than one
  // FIXME: rename this... they'll be in a fe/be context and we need to emphasize the pkg part
  function makeFrontend() {
    return new frontend_package($this);
  }
  function makeBackend() {
    return new backend_package($this);
  }
  // FIXME: These names were meant for client side only, make more universal
  /**
   * create resource
   * options
   *   general
   *     endpoint (lynx/bob) REQUIRED
   *     method (GET, POST, AUTO, etc)
   *     handlerFile - backend handler file
   *   post data
   *     requires - validation
   *     formData - associative array of key/values
   *   headers
   *     sendSession
   *     sendIP
   *   middlewares
   *     boardOwnerMiddleware, boardMiddleware
   *   response
   *     expectJson
   *     unwrapData
   */
  function addResource($label, $rsrcArr) {
    $label = strtolower($label); // must be lowercase since it's a filename
    if (!isset($rsrcArr['handlerFile'])) {
      $rsrcArr['handlerFile'] = $this->dir . 'be/handlers/'. $label . '.php';
      // is this only an issue if used (fe/be)?
      if (!file_exists($rsrcArr['handlerFile'])) {
        echo "Failed to setup [", $rsrcArr['handlerFile'], "]<br>\n";
      }
    }
    //echo "Adding [$label] to [", $this->name, "]<br>\n";
    $this->resources[$label] = $rsrcArr;
  }
  function useResource($label, $params = false, $options = false) {
    if (empty($this->resources[$label])) {
      echo "<pre>lib.pacakge:::package::useResource - Cannot call [$label] no such resource: ", print_r(array_keys($this->resources), 1), "</pre>\n";
      return;
    }
    $rsrc = $this->resources[$label];
    if (!empty($rsrc['requires'])) {
      $missing = array();
      foreach($rsrc['requires'] as $name) {
        // allow false to be a valid value...
        if (!isset($params[$name])) {
          $missing[] = $name;
        }
      }
      if (count($missing)) {
        echo "<pre>lib.pacakge:::package::useResource - Cannot call [$label] because ", join(', ', $missing), " are missing from parameters: ", print_r($params, 1), "</pre>\n";
        return;
      }
    }
    // handle $params mapping
    //echo "<pre>params[", print_r($rsrc, 1), "]</pre>\n";
    if (isset($rsrc['params'])) {
      if (is_array($rsrc['params'])) {
        //echo "<pre>params[", print_r($rsrc['params'], 1), "]</pre>\n";
        if (!isset($rsrc['params']['querystring'])) $rsrc['params']['querystring'] = array();
        if (!isset($rsrc['params']['formData']))    $rsrc['params']['formData'] = array();
        if (!is_array($rsrc['params']['querystring'])) $rsrc['params']['querystring'] = array($rsrc['params']['querystring']);
        if (!is_array($rsrc['params']['formData']))    $rsrc['params']['formData'] = array($rsrc['params']['formData']);
        $qs = array_flip($rsrc['params']['querystring']);
        $fd = array_flip($rsrc['params']['formData']);
        //echo "<pre>[", print_r($qs, 1), "]</pre>\n";
        //echo "<pre>[", print_r($fd, 1), "]</pre>\n";
        // FIXME: what if we call this multiple times?
        foreach($params as $k => $v) {
          if (isset($qs[$k])) {
            $rsrc['querystring'][] = $k . '=' . urlencode($v);
          } else if (isset($fd[$k])) {
            $rsrc['formData'][$k] = $v;
          } else {
            echo "lib.pacakge:::package::useResource - Don't know what to do with $k in $label<br>\n";
          }
        }
      } else
      if ($rsrc['params'] === 'querystring') {
        if (!isset($rsrc['querystring'])) $rsrc['querystring'] = array();
        if (is_array($params)) {
          foreach($params as $k=>$v) {
            // should we urlencode k too?
            if (is_string($v) || is_bool($v)) {
              $rsrc['querystring'][] = $k . '=' . urlencode($v);
            } else {
              echo "<pre>lib.pacakge:::package::useResource($label) - What do I do with [$k] of type [",gettype($v),"]=[", print_r($v, 1),"]</pre>\n";
            }
          }
        }
      } else if ($rsrc['params'] === 'postdata') {
        foreach($params as $k => $v) {
          if (is_array($v)) {
            // or we could pass PHP style...
            // backend might not be PHP...
            $rsrc['formData'][$k] = json_encode($v);
          } else {
            $rsrc['formData'][$k] = $v;
          }
        }
      } else {
        echo "lib.pacakge:::package::useResource - Unknown parameter type[", $params['params'], "]<br>\n";
      }
    }
    // does endpoint has params?
    if (strpos($rsrc['endpoint'], '/:') !== false) {
      $parts = explode('/:', $rsrc['endpoint']);
      $ds = array_shift($parts);
      $condParams = array();
      foreach($parts as $part) {
        $parts2 = explode('/', $part);
        $name = array_shift($parts2);
        $condParams[$name] = $params[$name];
        $rsrc['endpoint'] = str_replace(':' . $name, $condParams[$name], $rsrc['endpoint']);
      }
      //print_r($condParams);
    }

    // handle $options
    if ($options) {
      if (!empty($options['addPostFields'])) {
        foreach($options['addPostFields'] as $f => $v) {
          $rsrc['formData'][$f] = $v;
        }
      }
      if ($options['inWrapContent']) {
        $rsrc['inWrapContent'] = true;
      }
    }
    //echo "<pre>lib.pacakge:::package::useResource - cookie: ", print_r($_COOKIE, 1), "</pre>\n";
    //echo "<pre>lib.pacakge:::package::useResource - out: ", print_r($rsrc, 1), "</pre>\n";

    // make the call
    $result = consume_beRsrc($rsrc, $params);
    return $result;
  }
  function buildBackendRoutes() {
    global $routers, $pipelines;
    // we install models...
    /*
    if (file_exists($this->dir . 'models.php')) {
      include $this->dir . 'models.php';
    }
    */

    // activate backend hooks
    if (file_exists($this->dir . 'be/data.php')) {
      $bePkgs = include $this->dir . 'be/data.php';
      if (empty($bePkgs) || !is_array($bePkgs)) {
        return;
      }
      // we need to check for the array wrapper..
      if (isset($bePkgs['models']) || isset($bePkgs['modules'])) {
        echo "dir[", $this->dir, "] has data.php and found a models/modules at the root level, instead of an array<br>\n";
        exit;
      }
      foreach($bePkgs as $pName => $pData) {
        $bePkg = $this->makeBackend();
        if (isset($pData['models']) && is_array($pData['models'])) {
          foreach($pData['models'] as $m) {
            $bePkg->addModel($m);
          }
        }
        if (isset($pData['modules']) && is_array($pData['modules'])) {
          foreach($pData['modules'] as $m) {
            if (isset($pipelines[$m['pipeline']])) {
              // we could use constants in the data arrays
              // but then we need to separate pipelines to their own file
              // but breaks that data.php just contain data (no code)...
              $bePkg->addModule($m['pipeline'], $m['module']);
            } else {
              // pipeline isn't defined, likely modules admin interface
              echo "<pre>[", $this->dir . 'be/data.php', "]pipeline is not defiend in module [", print_r($m, 1), "]</pre>\n";
              echo "<pre>Missing[", $m['pipeline'], "] [", print_r($pipelines, 1), "]</pre>\n";
            }
          }
        }
      }
    }
    /*
    else
    if (file_exists($this->dir . 'be/index.php')) {
      include $this->dir . 'be/index.php';
    }
    */

    // delay loading of this unless the route is actually called
    /*
    // optional common functions and data
    // load here so they couldn't be called to calculate data for the module/data
    if (is_readable($this->dir . 'common.php')) {
      $this->common = include $this->dir . 'common.php';
    }
    */

    // install routes
    foreach($this->resources as $label => $rsrc) {
      $endpoint = $rsrc['endpoint'];
      // figure out which router
      $router = 'opt';
      if (substr($endpoint, 0, 6) === '4chan/') {
        $router = '4chan';
      } else
      if (substr($endpoint, 0, 5) === 'lynx/') {
        $router = 'lynx';
      }
      // requires the router name matches the route prefix
      $rsrc['endpoint'] = str_replace($router, '', $rsrc['endpoint']);

      // might be included from frontend...
      if (isset($routers[$router])) {
        //echo "Adding [$label][", $rsrc['endpoint'], "] to [$router]<br>\n";
        $res = $routers[$router]->fromResource($label, $rsrc, $this->dir);
        if ($res !== true) {
          echo "Problem building routes for : $res<br>\n";
        }
      } else {
        // admin/modules hits this path...
        //echo "Unknown router[$router]<br>\n";
      }
    }
  }
  function buildFrontendRoutes(&$router, $method) {
    // activate frontend hooks
    if (file_exists($this->dir . 'fe/data.php')) {
      $fePkgs = include $this->dir . 'fe/data.php';
      if (empty($fePkgs) || !is_array($fePkgs)) {
        return;
      }
      // package name is optinal
      foreach($fePkgs as $pName => $pData) {
        $fePkg = $this->makeFrontend();
        foreach($pData['handlers'] as $h) {
          //$fePkg->addHandler('GET', '/:uri/banners', 'public_list');
          $fePkg->addHandler(empty($h['method']) ? 'GET' : $h['method'], $h['route'], $h['handler']);
        }
        foreach($pData['forms'] as $f) {
          $fePkg->addForm($f['route'], $f['handler'], empty($f['options']) ? false : $f['options']);
        }
        foreach($pData['modules'] as $m) {
          $fePkg->addModule(constant($m['pipeline']), $m['module']);
        }
      }
    }
    /*
    else
    if (file_exists($this->dir . 'fe/index.php')) {
      include $this->dir . 'fe/index.php';
    }*/

    // optional common functions and data
    // load here so they couldn't be called to calculate data for the module/data
    /*
    if (is_readable($this->dir . 'common.php')) {
      $this->common = include $this->dir . 'common.php';
    }
    */

    // build all frontend routes
    foreach($this->frontend_packages as $fe_pkg) {
      $fe_pkg->buildRoutes($router, $method);
    }
  }
  function registerFrontendPackage($fe_pkg) {
    $this->frontend_packages[] = $fe_pkg;
  }
  function registerBackendPackage($be_pkg) {
    $this->backend_packages[] = $be_pkg;
  }
  function exec($label, $params) {
  }
}

class backend_package {
  function __construct($meta_pkg) {
    $this->pkg = $meta_pkg;
    $this->pkg->registerBackendPackage($this);
    $this->models = array();
    $this->modules = array();
  }
  function addModel($model) {
    global $db, $models;
    $name = $model['name'];
    $this->models[] = $name;
    // FIXME: move this into an activate module step
    // I think it makes the most to do this check once on start update
    // or maybe we build a list of tables to check and batch check...

    // might be activated in the frontend...
    if (isset($db)) {
      $db->autoupdate($model);
    }
    $models[$name] = $model;
  }
  // how to set dependencies/preempt?
  // FIXME: key caching...
  function addModule($pipeline_name, $file = false) {
    $bsn = new pipeline_module($this->pkg->name. '_' . $pipeline_name);
    if ($file === false) $file = $pipeline_name;
    $path = strtolower($this->pkg->dir) . 'be/modules/' . strtolower($file) . '.php';
    $pkg = &$this->pkg;
    $this->modules[] = $file;
    $bsn->attach($pipeline_name, function(&$io) use ($pipeline_name, $path, $pkg) {
      $getModule = function() use ($pipeline_name) {
        //echo "Set up module for [$pipeline_name]<br>\n";
        return array();
      };
      /*
      if (!file_exists($path)) {
        echo "This module [$pipeline_name], [$path] is not found<br>\n";
        return;
      }
      */
      //echo "Running path[$path]<br>\n";
      include $path;
    });
    return $bsn;
  }
  function toString() {
    $content ='<ul>';
    if (is_array($this->models) && count($this->models)) {
      global $models;
      $content .= '<li>Models<ul>';
      foreach($this->models as $mname) {
        $content .= '<li>' . $mname . modelToString($models[$mname]);
      }
      $content .= '</ul>';
    }
    if (is_array($this->modules) && count($this->modules)) {
      global $models;
      $content .= '<li>Modules<ul>';
      foreach($this->modules as $mname) {
        $content .= '<li>' . $mname;
      }
      $content .= '</ul>';
    }
    $content .= '</ul>';
    return $content;
  }
}

class frontend_package {
  // attach
  // - backend_resource
  // - frontend route/handler
  function __construct($meta_pkg) {
    $this->pkg = $meta_pkg;
    $this->pkg->registerFrontendPackage($this);
    $this->handlers = array();
    $this->modules = array();
    $this->ranOnce = false;
  }
  // could make a addCRUD (optional update)
  // could make an addForm that has a get/post
  // maybe a list of overrides options (defaults to change behavior)
  // everything should be memioized (ttl/etag)
  // ttl is a safe bet...
  // most data sources are going to be the backend
  // so we'll need enough set up to talk to it
  function addHandler($method, $cond, $file, $options = false) {
    $method = strtoupper($method);
    if (!isset($this->handlers[$method])) {
      $this->handlers[$method] = array();
    }
    $this->handlers[$method][$cond] = array(
      'file' => $file,
      'options' => $options,
    );
  }
  function addForm($cond, $file, $options = false) {
    if (!isset($options['get_options'])) $options['get_options'] = array();
    $options['get_options']['form'] = true;
    if (!isset($options['post_options'])) $options['post_options'] = false;
    $this->addHandler('GET', $cond, 'form_'.$file.'_get', $options['get_options']);
    $this->addHandler('POST', $cond, 'form_'.$file.'_post', $options['post_options']);
  }
  function addModule($pipeline_name, $file = false) {
    $bsn = new pipeline_module($this->pkg->name. '_' . $pipeline_name);
    if ($file === false) $file = $pipeline_name;
    $path = strtolower($this->pkg->dir) . 'fe/modules/' . strtolower($file) . '.php';
    $pkg = &$this->pkg;
    $module_path = strtolower($this->pkg->dir);
    // incorrect because a fePkg can have multiple modules...
    /*
    $bsn->runOnce($pipeline_name, function() use ($module_path) {
      if (is_readable($module_path . 'fe/common.php')) {
        $this->common = include $module_path . 'fe/common.php';
      } else {
        if (file_exists($module_path . 'fe/common.php')) {
          echo "lulwat [$module_path]fe/common.php<br>\n";
        }
      }
    });
    */
    $ref = &$this;
    $bsn->attach($pipeline_name, function(&$io, $options = false) use ($pipeline_name, $path, $pkg, &$ref, $module_path) {
      $getModule = function() use ($pipeline_name, $options, &$ref, $module_path) {
        // $this is the bsn...
        if (!$ref->ranOnce) {
          if (is_readable($module_path . 'fe/common.php')) {
            //
            $ref->common = include $module_path . 'fe/common.php';
          } else {
            if (file_exists($module_path . 'fe/common.php')) {
              echo "lulwat [$module_path]fe/common.php<br>\n";
            }
          }
          $ref->ranOnce = true;
        }
        if (isset($ref->common)) {
          $common = $ref->common;
        }

        //echo "Set up module for [$pipeline_name]<br>\n";
        return array(
          'options' => $options,
        );
      };
      /*
      if (!file_exists($path)) {
        echo "This module [$pipeline_name], [$path] is not found<br>\n";
        return;
      }
      */
      //echo "Running path[$path]<br>\n";
      include $path;
    });
    return $bsn;
  }
  function buildRoutes(&$router, $method) {
    // do we have any routes in this method
    if (empty($this->handlers[$method])) {
      return;
    }
    $pkg = &$this->pkg;
    // only build the routes we need
    foreach($this->handlers[$method] as $cond => $row) {
      $file = $row['file'];
      $module_path = strtolower($this->pkg->dir);
      $path = $module_path . 'fe/' . 'handlers/' . strtolower($file) . '.php';
      // FIXME: hide the ../commoon
      $func = function($request) use ($path) {
        // as configured by ...
        echo "handler[$path] does not exist<br>\n";
      };
      if (file_exists($path)) {
        $func = function($request) use ($path, $pkg, $row, $module_path) {
          if (is_readable($module_path . 'shared.php')) {
            $shared = include $module_path . 'shared.php';
          }
          if (is_readable($module_path . 'fe/common.php')) {
            $common = include $module_path . 'fe/common.php';
          } else {
            if (file_exists($module_path . 'fe/common.php')) {
              echo "lulwat [$module_path]fe/common.php<br>\n";
            }
          }
          // lastMod function?
          // well just deep memiozation could work...
          // middlewares, wrapContent => sendResponse
          $getHandler = function() use ($request, $path, $row) {
            $res = array(
              'request' => $request,
            );
            if (!empty($row['options'])) {
              if (!empty($row['options']['form'])) {
                $res['action'] = $request['originalPath'];
              }
            }
            return $res;
          };
          $intFunc = include $path;
        };
      }
      $router->addMethodRoute($method, $cond, $func);
    }
  }
  function toString() {
    $content = '';
    if (is_array($this->handlers)) {
      $content .= 'Handlers: ';
      $content .= 'Methods: ' . join(', ', array_keys($this->handlers));
      $content .= '<table><tr><th>Name<th>Method<th>Route';
      foreach($this->handlers as $type => $handlers) {
        //$content .= '<li>'. $type;
        if (is_array($handlers)) {
          foreach($handlers as $route => $h) {
            $content .= '<tr><td>' . $h['file'] . '<td>' . $type . '<td>' . $route;
          }
        }
      }
      $content .= '</table>';
    }
    return $content;
  }
}

?>
