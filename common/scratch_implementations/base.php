<?php

// persisent across requests but purgeable
// so like sessions but unique across the app not browser

// cache - ok to lose data, quick
// persist - not ok lose data, wait for consistency
//   anything like this should be in the backend/db

interface scratch_implementation_interface {
  function inc($key, $step = 1);
  function set($key, $val);
  function get($key);
  function clear($key);
  function clearAll();
}

// we either need a nukeAll functionality
// or a way to discover keys that have been set
class scratch_implementation_base_class implements scratch_implementation_interface {
  // implement if doesn't exist
  function inc($key, $step=1) {
    $count = (int)$this->get($key);
    $count += $step;
    $this->set($key, $count);
    return $count;
  }
  // can val be an array?
  function set($key, $val) { }
  function get($key) { }
  function clear($key) { }
  function clearAll() { }
}

?>