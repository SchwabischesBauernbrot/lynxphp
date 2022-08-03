<?php

include_once 'base.php';

// the size of the file caused more contention
// do we need pooling and/or multiple files

// file2: reduce time waiting for locks by using more disk space
// cache is less effective
// also we're a bit faster for reading/write because we're using a memory cache
class file2_scratch_driver extends scratch_implementation_base_class {
  function __construct() {
    // FIXME: on startup - do a write test check
    $file = realpath('../frontend_storage') . '/cache2';
    $res = $this->openFileInPool($file);
    $this->data = false;
    if ($res) {
      $this->data     = $res['data'];
      $this->pid      = $res['pid'];
      $this->lockpath = $res['lock'];
      $this->filepath = $res['file'];
    } else {
      echo "FAILED";
    }
  }

  function __destruct() {
    $this->commit();
    //$this->closeLock($this->lockpath, $this->pid);
    unlink($this->lockpath);
  }

  function commit() {
    if (!$this->data) return;
    file_put_contents($this->filepath, serialize($this->data));
    // probably want to retain this lock
  }

  function manyFilesGetPath($filename) {
    // minus extension
    // last X => xx/xx/filename.ext
    // mkdir
    // return directory
  }

  function lockAndOpen($filepath) {
    $lockpath = $filepath . '.lock';
    if (file_exists($lockpath)) {
      // FIXME: check content for expiration...
      return false;
    }
    global $now;
    $pid = posix_getpid();
    file_put_contents($lockpath, $now . '_' . $pid);
    if (!file_exists($lockpath)) {
      echo "cant create lock[$lock]<br>\n";
      return false;
    }
    // could check lock to make sure we got it

    // these shouldn't be more than 1mb
    $serializedStr = file_get_contents($filepath);
    $data = unserialize($serializedStr);
    return array(
      'file' => $filepath,
      'lock' => $lockpath,
      'pid'  => $pid,
      'data' => $data,
    );
  }

  /*
  function closeLock($lockpath, $pid = '') {
    if ($pid === '') $pid = posix_getpid();
    // FIXME: verify it's our lock
    unlink($lockpath);
  }
  */

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

  function clear($key) {
    unset($this->data[$key]);
    return true;
  }

  function get($key) {
    return empty($this->data[$key]) ? '' : $this->data[$key];
  }

  function set($key, $val) {
    $this->data[$key] = $val;
    return true;
  }
}

?>