<?php

include_once 'base.php';

// we bring all of these into memory and use very few
// maybe we should only unserialize when we hit our key
// but then we're doing more diskio
// could mmap might be linux only though
// file()
// first line could be the indexes
// and then we can fseek to those indexes

// file2v3: file2v1+expiration-singlelargevalue+json
// if we don't actually store json in it (but decoded json), it's actually smaller than serialize too

// file2v2: file2v1+expiration-singlelargevalue
// with the size problem solved with expiration
// the overhead of singlelargevalue was not worth it
// especially since they'd need their own expiration system

// the size of the file caused more contention
// do we need pooling and/or multiple files

// file2v1: reduce time waiting for locks by using more disk space
// cache is less effective
// also we're a bit faster for reading/write because we're using a memory cache

// added large value (>1k) memory reduction
// FIXME: we need to worry about number of files in a directory
// FIXME: report, so I can tell from a request which file was used
// or files for that matter
// readfile2_v1 && v2 was made for analysis

// expiration of keys would help
class file2_scratch_driver extends scratch_implementation_base_class {
  function __construct($prefix = '') {
    // FIXME: on startup - do a write test check
    $file = realpath('../frontend_storage') . '/' . $prefix . 'cache2v3';
    $this->changed = false;
    $res = $this->openFileInPool($file);
    $this->data = false;
    if ($res) {
      $this->data     = $res['data'];
      $this->pid      = $res['pid'];
      $this->lockpath = $res['lock'];
      $this->filepath = $res['file'];
    } else {
      echo "<!-- file2 scratch driver - FAILED -->Frontend having some problems, hang tight";
    }
  }

  function commit() {
    if (!$this->data) return;
    if ($this->changed) {
      // huh can run out of memory here...
      $ndata = array();
      global $now;
      foreach($this->data as $k => $row) {
        if ($row['ttl'] >= $now) {
          $ndata[$k] = $row;
        }
        unset($this->data[$k]);
      }
      unset($this->data);
      //echo "<pre>", htmlspecialchars(print_r($ndata, 1)), "</pre>\n";
      file_put_contents($this->filepath, json_encode($ndata));
      //echo "closed[$this->filepath]<br>\n";
    }
    // probably want to retain this lock
  }

  function __destruct() {
    $this->commit();
    //$this->closeLock($this->lockpath, $this->pid);
    // how does this end up not existing?
    @unlink($this->lockpath);
  }

  function manyFilesGetPath($filename) {
    // minus extension
    // last X => xx/xx/filename.ext
    // mkdir
    // return directory
  }

  function isProcessRunning($pid) {
    return file_exists("/proc/$pid");
  }

  function lockAndOpen($filepath) {
    $lockpath = $filepath . '.lock';
    global $now;
    if (file_exists($lockpath)) {
      // FIXME: check content for expiration...
      // these seemed to be caused by fatal errors
      // https://www.php.net/manual/en/function.register-shutdown-function.php
      // might help
      $contents = file_get_contents($lockpath);
      // might not have a _
      list($n, $p) = explode('_', $contents, 2);
      // is p running, if not, clear this lock
      // is old?
      $diff = $now - (float)$n;
      $clearLock = false;
      //echo "lockAndOpen - [$filepath][$diff]<br>\n";
      if ($diff > 300 || $this->isProcessRunning($p)) {
        // clear lock
        $clearLock = true;
      }
      if (!$clearLock) return false;
      // we're just about to stomp on it anyways
      //unlink($lockpath);
    }
    $pid = posix_getpid();
    if (file_put_contents($lockpath, $now . '_' . $pid) === false) {
      echo "Cannot create lock[$lockpath]<br>\n";
      return false;
    }
    // could check lock to make sure we got it

    // these shouldn't be more than 1mb
    $serializedStr = file_get_contents($filepath);
    // json is faster
    $metadata = json_decode($serializedStr, true);
    $this->changed = false;
    //echo "opened[$filepath] [", join(',', array_keys($metadata)), "]<br>\n";
    return array(
      'file' => $filepath,
      'lock' => $lockpath,
      'pid'  => $pid,
      'data' => $metadata,
    );
  }

  /*
  function closeLock($lockpath, $pid = '') {
    if ($pid === '') $pid = posix_getpid();
    // FIXME: verify it's our lock
    unlink($lockpath);
  }
  */

  // find a suitable non-lock cache file
  function openFileInPool($filepath) {
    $i = 0;
    while($i < 100) {
      // we need to protect information, such as IP addresses
      // so we'll use the php extension so that only PHP can access this info
      $curPath = $filepath . '.' . $i . '.php';
      // if dne exist, create it (high watermark tracking)
      if (!file_exists($curPath)) {
        file_put_contents($curPath, serialize(array()));
      }
      // try to lock open file
      $result = $this->lockAndOpen($curPath);
      if ($result) {
        // return data of open file
        return $result;
      }
      // if locked, try next
      $i++;
    }
    return false;
  }

  // is this even called?
  function clear($key) {
    unset($this->data[$key]);
    return true;
  }

  function get($key) {
    return empty($this->data[$key]) ? '' : $this->data[$key]['data'];
  }

  // having a group would be ideal tbh for statistical analysis purposes
  function set($key, $val, $ttl = 86400) {
    global $now;
    $this->changed = true;
    $this->data[$key] = array(
      'data' => $val,
      'ttl' => (int)$now + $ttl, // absolute point in the future
    );
    return true;
  }
}

?>