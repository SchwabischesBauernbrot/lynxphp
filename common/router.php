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
  function all($cond, $func) {
    foreach(array_keys($this->methods) as $method) {
      $this->methods[$method][$cond] = $func;
    }
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

    $params = array();
    $request = array (
      'method' => $method,
      'originalPath' => $path,
      'path' => $path,
      'params' => $params,
    );

    //echo "path[$path] rules[", count($methods), "]<br>\n";

    // there should only be one match
    // the one match can have multiple calls...
    foreach($methods as $cond => $func) {
      //echo "rule[$cond]<br>\n";
      if ($path === $cond) {
        $func($request);
        return true;
      } else {
        $csegs = explode('/', $cond);
        //echo "AdvRule[$cond] pDirs[", count($segments), "] rDirs[", count($csegs), "]<br>\n";
        //echo "Rule has router[", strpos($cond, '*') !== false, "]<br>\n";
        // optimization?
        if (strpos($cond, '*') === false && count($csegs) !== count($segments)) {
          //echo "Skipping rule[$cond]<br>\n";
          continue;
        }
        $match = true;
        $params = array();
        //echo "Checking [$path] against [$cond]<br>\n";
        foreach($csegs as $i => $c) {
          //echo "[$i] [$c] vs [", $segments[$i], "]<br>\n";
          if (strlen($c) && $c === '*') {
            //echo "Router check<br>\n";
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
              $res = $func->exec($request['method'], $newPath);
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
          if ($segments[$i] !== $c) {
            $match = false;
            break;
          }
        }
        if ($match) {
          $request['params'] = $params;
          $func($request);
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
