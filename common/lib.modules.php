<?php

// modulization classes and functions
// provides support for the various modules

// requires config to be loaded...
// maybe it shouldn't...

// the singleton could hold all the pipelines
// but what's the advantage of a singleton vs a global

// contains logic for compiling pipelines
class module_registry {
  /** class its providing */
  //var $instance;
  var $name;
  var $registry;
  var $compiled;

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
   * register a singleton with master server
   *
   * @param string name a unique key for object that you're registering
   * @param object object child object to associate with master
   */
  function register($name, $object) {
    if (isset($this->registry[$name])) {
      echo "singleton::register - WARNING, overriding [$name]<br>\n";
      // FIXME: convert to gettrace
      $bt = debug_backtrace();
      $btcnt = count($bt);
      for($i=1; $i<$btcnt; $i++) {
        echo $i.':'.(is_object($bt[$i]['object'])?get_class($bt[$i]['object']):'').'/'.$bt[$i]['class'].'->'.$bt[$i]['function']."<br>\n";
      }
    }
    $this->registry[$name] = $object;
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

  // we could hash all the names in the pipeline to make a cacheable key
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

  function debug() {
    if (!$this->compiled) {
      $this->compile();
    }
    print_r(array_keys($this->compile_modules));
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
  var $code;
  var $doOnceCode;
  var $ranOnce;
  function __construct() {
    $this->dependencies = array();
    $this->preempt      = array();
    $this->doOnceCode   = array();
    $this->ranOnce = false;
  }
  function runOnce($pipeline, $code) {
    $this->doOnceCode[] = $code;
  }
  function attach($pipeline, $code) {
    // deps and preempt are set
    $this->code = $code;
  }
  function exec(&$param) {
    if (!$this->ranOnce) {
      foreach($this->doOnceCode as $code) {
        $code();
      }
      $this->ranOnce = true;
    }
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
    parent::__construct();
    $this->name = $name;
  }
  // FIXME: convert to external file
  function attach($pipeline, $code) {
    // deps and preempt are set
    global $pipelines;
    if (empty($pipelines[$pipeline])) {
      echo "no pipeline[$pipeline]<br>\n";
      return;
    }
    $pipelines[$pipeline]->register($this->name, $this);
    $this->code = $code;
  }
}

// this is a lot cleaner than the above
class portal_pipeline_module extends orderable_module {
  function __construct($name) {
    parent::__construct();
    $this->name = $name;
  }
  function attach($plRegistry, $code) {
    // deps and preempt are set
    $plRegistry->register($this->name, $this);
    $this->code = $code;
  }
}

//include 'lib.loader.php';
// is it the other way around that uses us?
// well we don't use package class at all here..
// but I don't think it uses anything in here either...
// it uses pipeline_module
//ldr_require('lib.packages.php');

?>