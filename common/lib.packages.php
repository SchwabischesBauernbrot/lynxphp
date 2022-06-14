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
    $this->backendRoutesAdded = false;
    $this->frontendPackagesLoaded = false;
    // backend and frontend deps are likely to be different...
    // but we may need to do it in module.php before we get to data...
    // why? we don't...
    // FIXME: there is also optional dependencies
    // and we don't load the dep or the pipelines
    // if they aren't enable or don't exist
    $this->dependencies = array(); // set in module.php
  }

  // should we make a frontend_package/backend_package
  // no because they're optional and could have more than one
  // only one fe/ directory but that array can have multiple for on/off
  // it's not ib/mb support
  // FIXME: rename this... they'll be in a fe/be context and we need to emphasize the pkg part
  // these just register the object, not the data...
  function makeFrontend() {
    return new frontend_package($this);
  }
  function makeBackend() {
    return new backend_package($this);
  }
  function registerFrontendPackage($fe_pkg) {
    $this->frontend_packages[] = $fe_pkg;
  }
  function registerBackendPackage($be_pkg) {
    $this->backend_packages[] = $be_pkg;
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
    // what's this about?
    if (!isset($rsrcArr['handlerFile'])) {
      $rsrcArr['handlerFile'] = $this->dir . 'be/handlers/'. $label . '.php';
    }
    // is this only an issue if used (fe/be)?
    if (!file_exists($rsrcArr['handlerFile'])) {
      echo "Failed to setup resource[$label] file[", $rsrcArr['handlerFile'], "] is missing or unaccessible<br>\n";
    }
    if ($rsrcArr['endpoint'][0] === '/') {
      echo "Resource[$label]'s endpoint should NOT start with a slash<br>\n";
    }
    if (empty($rsrcArr['params']) && !empty($rsrcArr['requires']) && strpos($rsrcArr['endpoint'], '/:') === false) {
      echo "lib.package:::package::useResource($label) - Unset parameter type for required fields... in [", $this->dir, "]<br>\n";
      //print_r($rsrcArr['requires']);
      //print_r($rsrcArr['params']);
    }
    //echo "Adding [$label] to [", $this->name, "]<br>\n";
    $this->resources[$label] = $rsrcArr;
  }
  function useResource($label, $params = false, $options = false) {
    $label = strtolower($label); // UX but also camelcase is nice to make something clear
    if (empty($this->resources[$label])) {
      echo "<pre>lib.package:::package::useResource - Cannot call [$label] no such resource: ", print_r(array_keys($this->resources), 1), "</pre>\n";
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
        echo "<pre>lib.package:::package::useResource($label) - Cannot call [$label] because ", join(', ', $missing), " are missing from parameters: ", print_r($params, 1), "</pre>\n";
        return;
      }
    }
    // handle $params mapping
    //echo "<pre>params[", print_r($rsrc, 1), "]</pre>\n";
    if (isset($rsrc['params'])) {
      // mixed
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
            echo "lib.package:::package::useResource($label) - Don't know what to do with $k in $label<br>\n";
          }
        }
      } else
      if ($rsrc['params'] === 'querystring') {
        if (!isset($rsrc['querystring'])) $rsrc['querystring'] = array();
        if (is_array($params)) {
          foreach($params as $k=>$v) {
            // should we urlencode k too?
            if (is_string($v) || is_bool($v) || is_numeric($v)) {
              $rsrc['querystring'][$k] = urlencode($v);
            } else if (is_array($v)) {
              // we just assume it's a list of strings
              // php will bitch if not...
              $rsrc['querystring'][$k] = urlencode(join(',', $v));
            } else {
              echo "<pre>lib.package:::package::useResource($label) - What do I do with [$k] of type [",gettype($v),"]=[", print_r($v, 1),"]</pre>\n";
            }
          }
        }
      } else if ($rsrc['params'] === 'postdata') {
        foreach($params as $k => $v) {
          if (is_array($v)) {
            // or we could pass PHP style...
            // backend might not be PHP...
            // we could post a application/json and keep it as an array...
            $rsrc['formData'][$k] = json_encode($v);
          } else {
            $rsrc['formData'][$k] = $v;
          }
        }
      } else {
        echo "lib.package:::package::useResource($label) - Unknown parameter type[", $params['params'], "]<br>\n";
      }
    } else {
      if (!empty($rsrc['requires'])) {
        // we didn't set them as GET or POST etc...
        // we should warn when we're set up, not being called
        //echo "lib.package:::package::useResource($label) - Unset parameter type for required fields... in [", $this->dir, "]<br>\n";
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
      if (!empty($options['inWrapContent'])) {
        $rsrc['inWrapContent'] = true;
      }
    }
    //echo "<pre>lib.package:::package::useResource - cookie: ", print_r($_COOKIE, 1), "</pre>\n";
    //echo "<pre>lib.package:::package::useResource - out: ", print_r($rsrc, 1), "</pre>\n";

    // make the call
    $result = consume_beRsrc($rsrc, $params);
    return $result;
  }

  // hotpath
  function buildBackendRoutes() {
    //echo "buildBackendRoutes - registering ", $this->name, "<br>\n";
    if ($this->backendRoutesAdded) {
      // already processed...
      return;
    }

    if (count($this->dependencies)) {
      //echo "need to load:<br>\n";
      //print_r($this->dependencies);
      global $packages;
      foreach($this->dependencies as $pkg_path) {
        $depPkg = registerPackage($pkg_path);
        $depPkg->buildBackendRoutes();
        $packages[$depPkg->name] = $depPkg;
      }
      //echo "loaded<br>\n";
    }

    global $routers, $pipelines;

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
          foreach($pData['models'] as $k => $m) {
            $bePkg->addModel($m, $k);
          }
        }
        if (isset($pData['pipelines']) && is_array($pData['pipelines'])) {
          foreach($pData['pipelines'] as $m) {
            //echo "ESTABLISHING [", $m['name'], "]<br>\n";
            $bePkg->addPipeline($m);
          }
        }
        if (isset($pData['modules']) && is_array($pData['modules'])) {
          foreach($pData['modules'] as $m) {
            if (!isset($pipelines[$m['pipeline']])) {
              // need to load dependency
              // yea we won't know where the pipeline lives yet...

              // might just need to lowercase pipeline tbh...
              // maybe do it in the attach phase?
            }
            if (isset($pipelines[$m['pipeline']])) {
              // we could use constants in the data arrays
              // but then we need to separate pipelines to their own file
              // but breaks that data.php just contain data (no code)...
              $bePkg->addModule($m['pipeline'], $m['module']);
            } else {
              // pipeline isn't defined, likely modules admin interface
              // or dependency isn't loaded yet...

              // we can't attach if it doesn't exist I think
              //echo "deps[", print_r($this->dependencies, 1), "]<bR>\n";
              echo "<pre>[", $this->dir . 'be/data.php', "]pipeline[", $m['pipeline'], "] is not defined in module[", $m['module'], "] complete entry:[", print_r($m, 1), "]</pre>\n" . gettrace();
              echo "<pre>Missing[", $m['pipeline'], "] [", print_r($pipelines, 1), "]</pre>\n";
            }
          }
        }
      }
    }

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
      // will always be missed the first /
      $endpoint = $rsrc['endpoint'];
      // figure out which router
      $router = 'opt';
      if (substr($endpoint, 0, 6) === '4chan/') {
        $router = '4chan';
      } else
      if (substr($endpoint, 0, 5) === 'lynx/') {
        $router = 'lynx';
      } else
      if (substr($endpoint, 0, 11) === 'doubleplus/') {
        $router = 'doubleplus';
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
    $this->backendRoutesAdded = true;
  }
  // hotpath
  // if router is omitted, we only set up modules/pipelines
  // FIXME: an option to only load a list of these resources...
  // like js only needs the js only
  function frontendPrepare($router = false, $method = 'GET', $options = false) {
    if ($this->frontendPackagesLoaded) return; // already processed
    //echo "buildFrontendRoutes<br>\n";
    $ensuredOptions = ensureOptions(array(
      'loadHandlers'  => true,
      'loadForms'     => true,
      'loadJs'        => false,
      'loadModules'   => true,
      'loadPipelines' => true,
      'router' => $router, // false
      'method' => $method, // GET
    ), $options);

    // activate frontend hooks
    if (file_exists($this->dir . 'fe/data.php')) {
      $fePkgs = include $this->dir . 'fe/data.php';
      //echo "Loading ", $this->dir, "\n";
      if (empty($fePkgs) || !is_array($fePkgs)) {
        return;
      }
      // echo "Has pkg data\n";
      // package name is optional?
      foreach($fePkgs as $pName => $pData) {
        $fePkg = $this->makeFrontend();
        $fePkg->name = $pName;
        $fePkg->unpack($pData, $ensuredOptions);
      }
    //} else {
      //echo "No fe/data.php in [", $this->dir, "]<br>\n";
    }

    // optional common functions and data
    // load here so they couldn't be called to calculate data for the module/data
    /*
    if (is_readable($this->dir . 'common.php')) {
      $this->common = include $this->dir . 'common.php';
    }
    */

    if ($router) {
      // build all frontend routes
      foreach($this->frontend_packages as $fe_pkg) {
        $fe_pkg->buildRoutes($router, $method);
      }
    }
    $this->frontendPackagesLoaded = true;
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
    $this->ranOnce = false;
  }
  function addModel($model, $potentialName) {
    global $db, $models;
    // each of these are required to be unique
    // we should change the format...
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
    $pkg = &$this->pkg;
    $module_path = strtolower($pkg->dir);
    $path = $module_path . 'be/modules/' . strtolower($file) . '.php';
    $this->modules[] = $file;
    $ref = &$this;
    $bsn->attach($pipeline_name, function(&$io) use ($pipeline_name, $path, $pkg, $module_path, &$ref) {
      $getModule = function() use ($pipeline_name) {
        //echo "Set up module for [$pipeline_name]<br>\n";
        return array();
      };

      if (!$ref->ranOnce) {
        if (is_readable($module_path . 'be/common.php')) {
          // ref isn't defined...
          //$ref->common =
          $ref->common = include $module_path . 'be/common.php';
        } else {
          if (file_exists($module_path . 'be/common.php')) {
            echo "perms? [$module_path]be/common.php<br>\n";
          }
        }
        $ref->ranOnce = true;
        if (isset($ref->common)) {
          $common = $ref->common;
        }
      }

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
  function addPipeline($pipeline) {
    // has to be a string...
    //echo "backend_package::addPipeline [", $pipeline['name'], "]<br>\n";
    definePipeline($pipeline['name']);
  }
  // FIXME: addScheduledTask
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
  // - backend_resource via pkg
  // - frontend route/handler
  function __construct($meta_pkg) {
    $this->pkg = $meta_pkg;
    $this->pkg->registerFrontendPackage($this);
    $this->handlers = array();
    $this->modules = array();
    $this->js = array();
    $this->ranOnce = false;
  }

  function unpack($pData, $ensuredOptions) {
    // unpacks load*, router, method
    extract($ensuredOptions);

    // no is_array because if they mess up the format
    // let php handle it, we don't need to
    global $packages;
    if (isset($pData['dependencies'])) {
      foreach($pData['dependencies'] as $depPkgName) {
        //echo "depPkgName[$depPkgName] dir[", $this->pkg->dir, "]<br>\n";
        $depPkg = registerPackage($depPkgName); // load
        $depPkg->frontendPrepare($router, $method, $ensuredOptions);
        $packages[$depPkg->name] = $depPkg; // register package global
      }
    }

    // we could split this into multiple functions...
    // maybe all this should be moved into fe_pkg
    if ($router) {
      if ($loadHandlers && isset($pData['handlers'])) {
        foreach($pData['handlers'] as $h) {
          // FIXME: skip adding the methods we don't need...
          //$this->addHandler('GET', '/:uri/banners', 'public_list');
          $m = empty($h['method']) ? 'GET' : $h['method'];
          //echo $m, '_', $h['route'], ' ', print_r($h, 1), "\n";
          $options = array(
            'cacheSettings' => empty($h['cacheSettings']) ? false : $h['cacheSettings'],
            'loggedIn' => empty($h['loggedIn']) ? false : $h['loggedIn'],
          );
          //echo $m, '_', $h['route'], ' ', print_r($options, 1), "\n";
          // file maybe more descriptive and consistent than handler
          $this->addHandler($m, $h['route'], $h['handler'], $options);
        }
      }
      if ($loadForms && isset($pData['forms'])) {
        foreach($pData['forms'] as $f) {
          // FIXME: skip adding the methods we don't need...
          $this->addForm($f['route'], $f['handler'], empty($f['options']) ? false : $f['options']);
        }
      }
    }
    if ($loadJs && isset($pData['js']) && is_array($pData['js'])) {
      // no need to unpack, we just need to transfer the data
      // maybe some rules to determine if to load it or not?
      $this->js = $pData['js'];
      // unpack it into pipelines if we're on this page?
    }
    if ($loadModules && isset($pData['modules'])) {
      foreach($pData['modules'] as $m) {
        if (!defined($m['pipeline'])) {
          echo "Pipeline [", $m['pipeline'], "] is not defined, found in [", $this->pkg->dir, "]<br>\n";
        } else {
          $this->addModule(constant($m['pipeline']), $m['module']);
        }
      }
    }
    if ($loadPipelines && isset($pData['pipelines'])) {
      foreach($pData['pipelines'] as $m) {
        // name has to be a string
        $this->addPipeline($m);
      }
    }
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
    //echo "[$method][$cond]=>opts[", print_r($options, 1), "]\n";
    $this->handlers[$method][$cond] = array(
      'file' => $file,
      'options' => $options,
    );
  }

  function addForm($cond, $file, $options = false) {
    if (!isset($options['get_options'])) $options['get_options'] = array();
    //else echo "addForm - [", print_r($options['get_options'], 1), "]\n";
    $options['get_options']['form'] = true;
    if (!isset($options['post_options'])) $options['post_options'] = false;
    $this->addHandler('GET', $cond . '.html', 'form_'.$file.'_get', $options['get_options']);
    $this->addHandler('POST', $cond . '.php', 'form_'.$file.'_post', $options['post_options']);
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

    /*
    shared.php
    fe/common.php
    maybe the file should handle these includes themselves for performance reasons
    not all need this
    and I don't see any benefit instrumenting this
    we can just wrap it just in case
    */

    $ref = &$this;
    // this function isn't called unless the pipeline is executed
    $bsn->attach($pipeline_name, function(&$io, $options = false) use ($pipeline_name, $path, $pkg, &$ref, $module_path) {
      // $this is the bsn...
      if (!$ref->ranOnce) {
        //echo "module_path[$module_path]<Br>\n";
        if (is_readable($module_path . 'shared.php')) {
          $ref->shared = include $module_path . 'shared.php';
        } else {
          if (file_exists($module_path . 'shared.php')) {
            echo "perms? [$module_path]shared.php<br>\n";
          }
        }
        if (is_readable($module_path . 'fe/common.php')) {
          //
          $ref->common = include $module_path . 'fe/common.php';
        } else {
          if (file_exists($module_path . 'fe/common.php')) {
            echo "perms? [$module_path]fe/common.php<br>\n";
          }
        }
        $ref->ranOnce = true;
      }
      //$common = false;
      if (isset($ref->common)) {
        $common = $ref->common;
      }
      //$shared = false;
      if (isset($ref->shared)) {
        $shared = $ref->shared;
      }

      $getModule = function() use ($pipeline_name, $options, &$ref, $module_path) {
        //echo "module get<br>\n";
        //echo "Set up module for [$pipeline_name]<br>\n";
        return array(
          //'shared' => $shared,
          //'common' => $common,
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

  function addPipeline($pipeline) {
    // name has to be a string
    definePipeline($pipeline['name']);
  }

  function buildRoutes($router, $method) {
    // do we have any routes in this method
    if (empty($this->handlers[$method])) {
      //echo "no routes for [$method]<Br>\n";
      return;
    }
    $ref = &$this;
    $pkg = &$this->pkg;
    // only build the routes we need
    foreach($this->handlers[$method] as $cond => $row) {
      $file = $row['file'];
      $module_path = strtolower($this->pkg->dir);
      $path = $module_path . 'fe/handlers/' . strtolower($file) . '.php';
      // FIXME: hide the ../commoon
      $func = function($request) use ($path) {
        // as configured by ...
        echo "handler[$path] does not exist<br>\n";
      };
      if (file_exists($path)) {
        $func = function($request) use ($path, $pkg, $row, $module_path, $ref) {
          if (!$ref->ranOnce) {
            if (is_readable($module_path . 'shared.php')) {
              $ref->shared = include $module_path . 'shared.php';
            }
            if (is_readable($module_path . 'fe/common.php')) {
              $ref->common = include $module_path . 'fe/common.php';
            } else {
              if (file_exists($module_path . 'fe/common.php')) {
                echo "lulwat [$module_path]fe/common.php<br>\n";
              }
            }
            $ref->ranOnce = true;
          }
          // unpack them
          if (isset($ref->common)) {
            $common = $ref->common;
          }
          //$shared = false;
          if (isset($ref->shared)) {
            $shared = $ref->shared;
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
                if (strpos($request['originalPath'], '.html') !== false) {
                  $res['action'] = str_replace('.html', '.php', $request['originalPath']);
                } else {
                  $res['action'] = $request['originalPath'];
                }
              }
            }
            return $res;
          };
          $intFunc = include $path;
        };
      }
      /*
      $cacheSettings = false;
      if (!empty($row['options']['cacheSettings'])) {
        $cacheSettings = $row['options']['cacheSettings'];
      }
      */
      // module, name?
      if (!is_array($row['options'])) $row['options'] = array();
      $row['options']['module'] = $this->pkg->name;
      //$row['options']['name'] = $this->name;
      $row['options']['address'] = $row['file'];
      $router->addMethodRoute($method, $cond, $func, $row['options']);
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