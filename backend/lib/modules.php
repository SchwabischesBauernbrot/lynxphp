<?php

/**
  * Ensures only one object of extended class exists to conserve memory
  */
class module_registry {
  /** class its providing */
  var $instance;

  /** make sure it's a singleton */
  protected function __construct() {
    $this->name  = get_class($this);
    $this->registry = array();
  }

  /** make sure it can't be cloned */
  protected function __clone() {}

  /** make sure it can't be unserialized */
  public function __wakeup(){
    throw new Exception("Cannot unserialize singleton");
  }

  /**
   * php singleton hack copy this into the child class
   *
   * @returns string current class name
   */
  static function singleton() {
    static $instance; // think like a global that doesn't leave this function
    if (!isset($instance)) {
      $instance=new cwasingleton;
    }
    return $instance;
  }
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

  function resolve_all() {
    $list = array();
    for($this->registry as $name => $obj) {
      $this->resolve($name, $obj, $list);
    }
  }
}

class pipeline_registry extends module_registry {
}

class ui_registry extends module_registry {
}

class orderable_module {
  var dependencies; // these have to be completed
  var preempt; // I must be before these modules
  var name; // what my name is
}

// public base modules that modules can extend

// post validation/transformation
// page generation
class pipeline_module extends orderable_module {
  function __construct($name) {
    pipeline_registry::singleton()->register($name, $this);
  }
}

// Site/BO/Users options
class ui_module extends orderable_module {
  function __construct($name) {
    ui_registry::singleton()->register($name, $this);
  }
}

?>
