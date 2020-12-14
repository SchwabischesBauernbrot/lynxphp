<?php

// modulization classes and functions
// provides support for the various modules

// the singleton could hold all the pipelines
// but what's the advantage of a singleton vs a global

// contains logic for compiling pipelines
class module_registry {
  /** class its providing */
  var $instance;

  /** make sure it's a singleton */
  /*protected */
  function __construct() {
    $this->name  = get_class($this);
    $this->registry = array();
    $this->compiled = false;
  }

  /** make sure it can't be cloned */
  //protected function __clone() {}

  /** make sure it can't be unserialized */
  /*
  public function __wakeup(){
    throw new Exception("Cannot unserialize singleton");
  }
  */

  /**
   * php singleton hack copy this into the child class
   *
   * @returns string current class name
   */
  /*
  static function singleton() {
    static $instance; // think like a global that doesn't leave this function
    if (!isset($instance)) {
      $instance=new cwasingleton;
    }
    return $instance;
  }
  */

  /*
  // php singleton hack put this in the child class
  function singleton() {
    return singleton::singleton(__CLASS__);
  }
  */

  /**
   * register a sinlgeton with master server
   *
   * @param string name a unique key for object that you're registering
   * @param object object child object to associate with master
   */
  function register($name, $object) {
    if (isset($this->registry[$name])) {
      echo "singleton::register - WARNING, overriding [$name]<br>\n";
      $bt=debug_backtrace();
      $btcnt=count($bt);
      for($i=1; $i<$btcnt; $i++) {
        echo $i.':'.(is_object($bt[$i]['object'])?get_class($bt[$i]['object']):'').'/'.$bt[$i]['class'].'->'.$bt[$i]['function']."<br>\n";
      }
    }
    $this->registry[$name]=$object;
  }

  function canXgoBeforeY($list, $x, $y) {

  }

  function checkForCircular($list) {
    // make sure nothing is invalid in this list...
    $left = $list;
    while($item = array_shift($left)) {
      // make sure I don't require something
      // that require mes
      // forward and backward
    }
  }

  function checkForGood($list) {
    // make sure nothing is invalid in this list...
    $left = $list;
    while($item = array_shift($left)) {
      $before[] = $item; // move into before list
      // check deps
      // make sure I'm after everything I need to be
      // check preempts
      // make sure I'm before everything I need to be
    }
    // no circular depends
    return false;
  }

  function resolve($name, $obj, $list) {
    // find position after all the dependencies
    $needs = $obj->dependencies;
    $startpos = count($list);
    foreach($list as $pos => $itemname) {
      $key = array_search($itemname, $needs);
      if ($key !== false) {
        unset($needs[$key]);
        if (!count($needs)) {
          $startpos = $pos;
          break;
        }
      }
    }
    if ($startpos == count($list)) {
      // need to reshuffle list...
    }
    // find position before all the preempts
    $needs = $obj->preempt;
    $endpos = 0;
    foreach(array_reverse($list) as $pos => $itemname) {
      $key = array_search($itemname, $needs);
      if ($key !== false) {
        unset($needs[$key]);
        if (!count($needs)) {
          $endpos = $pos;
          break;
        }
      }
    }
    if (!$endpos) {
      // need to reshuffle list...
    }

    if ($startpos > $endpos) {
      // need to reshuffle list...
    }
  }

  function findAllNoDeps() {
    return array_filter($this->registry, function ($m) {
      return !(count($m->dependencies) || count($m->preempt));
    });
  }

  function findPrereqs() {
    return array_filter($this->registry, function ($m) {
      return count($m->dependencies);
    });
  }
  function findPostreqs() {
    return array_filter($this->registry, function ($m) {
      return count($m->preempt);
    });
  }

  function expand_prerequirements($dep) {
    $deps = $dep->dependencies;
    foreach($dep->dependencies as $d) {
      $newDeps = $this->expand_prerequirements($d);
      $deps = array_merge($deps, $newDeps);
    }
    $deps = array_unique($deps);
    return $deps;
  }

  function expand_preempt($srcMod) {
    $prempts = $srcMod->preempt;
    foreach($srcMod->preempt as $mod) {
      $newPreempts = $this->expand_prequirements($mod);
      $prempts = array_merge($prempts, $newPreempts);
    }
    $prempts = array_unique($prempts);
    return $prempts;
  }

  function expand($name) {
    $m = $this->registry[$name];
    $expMod = array(
      'prereq' => $this->expand_prequirements($m),
      'preempt' => $this->expand_preempt($m),
    );
    // now within this scope
    // any problems we can't resolve?
    $clean = true;
    foreach($expMod['prereq'] as $name) {
      if (in_array($name, $expMod['preempt'])) {
        $clean = false;
        break;
      }
    }
    if ($clean) {
      foreach($expMod['preempt'] as $name) {
        if (in_array($name, $expMod['prereq'])) {
          $clean = false;
          break;
        }
      }
    }
    if ($clean) {
      return $expMod;
    }

    // fix internal ordering...

    return $expMod;
  }

  function resolve_all() {
    $list = array();
    foreach($this->registry as $name => $obj) {
      $this->resolve($name, $obj, $list);
    }
  }

  function compile() {
    $this->resolve_all();
    // FIXME: prereq/prempt handling
    $this->compile_modules = $this->registry;
  }

  function execute(&$param) {
    if (!$this->compiled) {
      $this->compile();
    }
    //print_r(array_keys($this->compile_modules));
    foreach($this->compile_modules as $name => $mod) {
      $mod->exec($param);
    }
  }
}

class pipeline_registry extends module_registry {
}

class orderable_module {
  var $dependencies; // these have to be completed
  var $preempt; // I must be before these modules
  var $name; // what my name is
  function __construct() {
    $this->dependencies = array();
    $this->preempt      = array();
  }
  function attach($pipeline, $code) {
    // deps and preempt are set
    $this->code = $code;
  }
  function exec(&$param) {
    $code = $this->code;
    $code($param);
  }
}

// public base modules that modules can extend

// post validation/transformation
// page generation
// data vs code: Site/BO/Users options
class pipeline_module extends orderable_module {
  function __construct($name) {
    $this->name = $name;
  }
  // FIXME: convert to external file
  function attach($pipeline, $code) {
    // deps and preempt are set
    global $pipelines;
    $pipelines[$pipeline]->register($this->name, $this);
    $this->code = $code;
  }
}

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
  function useResource($label, $params) {
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
      //echo "Adding [$label] to [$router]<br>\n";
      $res = $routers[$router]->fromResource($label, $rsrc);
      if ($res !== true) {
        echo "Problem building routes for : $res<br>\n";
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
  function registerFrontendPackage(&$fe_pkg) {
    $this->frontend_packages[] = &$fe_pkg;
  }
  function registerBackendPackage(&$fe_pkg) {
    $this->backend_packages[] = &$fe_pkg;
  }
  function exec($label, $params) {
  }
}

class backend_package {
  function __construct($meta_pkg) {
    $this->pkg = $meta_pkg;
    $this->pkg->registerBackendPackage($this);
  }
  function addModel($model) {
    global $db, $models;
    $name = $model['name'];
    // FIXME: move this into an activate module step
    // I think it makes the most to do this check once on start update
    // or maybe we build a list of tables to check and batch check...
    $db->autoupdate($model);
    $models[$name] = $model;
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
}

//
// loader functions
//

function registerPackages() {
}

$module_base = 'common/modules/';

function registerPackageGroup($group) {
  global $module_base, $packages;
  $dir = '../' . $module_base . $group;
  if (!is_dir($dir)) {
    // does not exists
    return false;
  }
  $dh = opendir($dir);
  if (!$dh) {
    // permissions
    return false;
  }
  $loaded = 0;
  while (($file = readdir($dh)) !== false) {
    if ($file[0] === '.') continue;
    //echo "filename: $file : filetype: " . filetype($dir . $file) . "\n";
    $path = $dir . '/' . $file;
    if (is_dir($path)) {
      $loaded++;
      $pkg = &registerPackage($group . '/' . $file);
      $packages[$pkg->name] = $pkg;
    }
  }
  closedir($dh);
  return $loaded;
}

function &registerPackage($pkg_path) {
  global $module_base;
  $full_pkg_path = '../' . $module_base . $pkg_path . '/';
  $pkg = include $full_pkg_path . 'index.php';
  return $pkg;
}

function getEnabledModules() {
  return array('base');
}

function enableModule($module){
  include '../common/modules/' . $module . '/index.php';
}

function enableModuleType($type, $module){
  $path = '../common/modules/' . $module . '/' . $type . '.php';
  if (file_exists($path)) {
    include $path;
  }
}

function enableModules() {
  $modules = getEnabledModules();
  foreach($modules as $module) {
    enableModule($module);
  }
}

function enableModulesType($type) {
  $modules = getEnabledModules();
  foreach($modules as $module) {
    enableModuleType($type, $module);
  }
}

?>
