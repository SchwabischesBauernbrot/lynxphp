<?php

class Router {
  function __construct() {
    $this->methods = array();
    $this->methods['GET']  = array();
    $this->methods['POST'] = array();
    $this->methods['HEAD'] = array();
    $this->methods['PUT'] = array();
    $this->methods['DELETE'] = array();
  }
  function get($cond, $func) {
    $this->methods['GET'][$cond] = $func;
  }
  function post($cond, $func) {
    $this->methods['POST'][$cond] = $func;
  }
  function exec($method, $path) {
    $methods = $this->methods[$method];
    $segments = explode('/', $path);
    // there should only be one match
    // the one match can have multiple calls...
    foreach($methods as $cond => $func) {
      if ($path === $cond) {
        $func();
        return true;
      } else {
        $csegs = explode('/', $cond);
        if (count($csegs) !== count($segments)) {
          continue;
        }
        $match = true;
        $params = array();
        foreach($csegs as $i => $c) {
          if (strlen($c) && $c[0] == ':') {
            $paramName = substr($c, 1);
            $params[$paramName] = $segments[$i];
            continue;
          }
          if ($segments[$i] !== $c) {
            $match = false;
            break;
          }
        }
        if ($match) {
          $func($params);
          return true;
        //} else {
          //echo "failed path[$path] cond[$cond]<Br>\n";
        }
      }
    }
    // FIXME: 404??
    return false;
  }
}

?>
