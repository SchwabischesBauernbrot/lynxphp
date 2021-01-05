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
    if (!isset($rsrcArr['handlerFile'])) {
      $rsrcArr['handlerFile'] = $this->dir . 'be/handlers/'. $label . '.php';
      if (!file_exists($rsrcArr['handlerFile'])) {
        echo "Failed to setup [", $rsrcArr['handlerFile'], "]<br>\n";
      }
    }
    $this->resources[$label] = $rsrcArr;
  }
  function useResource($label, $params = false) {
    $rsrc = $this->resources[$label];
    if (!empty($rsrc['requires'])) {
      $ok = true;
      foreach($rsrc['requires'] as $name) {
        if (empty($params[$name])) {
          $ok = false;
          echo "<pre>Cannot call [$label] because [$name] is missing from parameters: ", print_r($params, 1), "</pre>\n";
          return;
        }
      }
    }
    if (isset($rsrc['params'])) {
      if (is_array($rsrc['params'])) {
        if (!isset($rsrc['querystring'])) $rsrc['querystring'] = array();
        if (!isset($rsrc['formData']))    $rsrc['formData'] = array();
        $qs = array_flip($rsrc['params']['querystring']);
        $fd = array_flip($rsrc['params']['formData']);
        foreach($params as $k=>$v) {
          if (isset($qs[$k])) {
            $rsrc['querystring'][] = $k . '=' . urlencode($v);
          } else if (isset($fd[$k])) {
            $rsrc['formData'][$k] = $v;
          } else {
            echo "Don't know what to do with $k in $label<br>\n";
          }
        }
      } else
      if ($rsrc['params'] === 'querystring') {
        if (!isset($rsrc['querystring'])) $rsrc['querystring'] = array();
        foreach($params as $k=>$v) {
          // should we urlencode k too?
          $rsrc['querystring'][] = $k . '=' . urlencode($v);
        }
      } else {
        echo "Unknown parameter type[", $params['params'], "]<br>\n";
      }
    }
    $result = consume_beRsrc($rsrc, $params);
    return $result;
  }
  function buildBackendRoutes() {
    global $routers;
    // we install models...
    /*
    if (file_exists($this->dir . 'models.php')) {
      include $this->dir . 'models.php';
    }
    */
    // activate backend hooks
    if (file_exists($this->dir . 'be/index.php')) {
      include $this->dir . 'be/index.php';
    }
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
      //echo "Adding [$label][", $rsrc['endpoint'], "] to [$router]<br>\n";

      // might be included from frontend...
      if (isset($routers[$router])) {
        $res = $routers[$router]->fromResource($label, $rsrc);
        if ($res !== true) {
          echo "Problem building routes for : $res<br>\n";
        }
      }
    }
  }
  function buildFrontendRoutes(&$router, $method) {
    // activate frontend hooks
    if (file_exists($this->dir . 'fe/index.php')) {
      include $this->dir . 'fe/index.php';
    }
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
    $this->handlers[$method][$cond] = $file;
  }
  function addForm($cond, $file, $options = false) {
    if (!isset($options['get_options'])) $options['get_options'] = false;
    if (!isset($options['post_options'])) $options['post_options'] = false;
    $this->addHandler('GET', $cond, 'form_'.$file.'_get', $options['get_options']);
    $this->addHandler('POST', $cond, 'form_'.$file.'_post', $options['post_options']);
  }
  function addModule($pipeline_name, $file = false) {
    $bsn = new pipeline_module($this->pkg->name. '_' . $pipeline_name);
    if ($file === false) $file = $pipeline_name;
    $path = strtolower($this->pkg->dir) . 'fe/modules/' . strtolower($file) . '.php';
    $pkg = &$this->pkg;
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
  function buildRoutes(&$router, $method) {
    // do we have any routes in this method
    if (empty($this->handlers[$method])) {
      return;
    }
    $pkg = &$this->pkg;
    // only build the routes we need
    foreach($this->handlers[$method] as $cond => $file) {
      $path = strtolower($this->pkg->dir) . 'fe/handlers/' . strtolower($file) . '.php';
      $func = function($request) use ($path, $pkg) {
        // lastMod function?
        // well just deep memiozation could work...
        // middlewares, wrapContent => sendResponse
        $getHandler = function() {
          return array();
        };
        $intFunc = include $path;
      };
      $router->addMethodRoute($method, $cond, $func);
    }
  }
  function toString() {
    $content = '';
    if (is_array($this->handlers)) {
      $content .= 'Handlers: ';
      $content .= 'Methods: ' . join(', ', array_keys($this->handlers));
      $content .= '<table>';
      foreach($this->handlers as $type => $handlers) {
        //$content .= '<li>'. $type;
        if (is_array($handlers)) {
          foreach($handlers as $route => $rname) {
            $content .= '<tr><th>' . $rname . '<td>' . $type . '<td>' . $route;
          }
        }
      }
      $content .= '</table>';
    }
    return $content;
  }
}

?>
