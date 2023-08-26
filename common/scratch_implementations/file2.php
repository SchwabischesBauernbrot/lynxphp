<?php

include_once 'base.php';

// the size of the file caused more contention
// do we need pooling and/or multiple files

// file2: reduce time waiting for locks by using more disk space
// cache is less effective
// also we're a bit faster for reading/write because we're using a memory cache

// added large value (>1k) memory reduction
// FIXME: we need to worry about number of files in a directory
class file2_scratch_driver extends scratch_implementation_base_class {
  function __construct($prefix = '') {
    $this->filebase = '../frontend_storage/' . $prefix . 'cache2_v1';
    // FIXME: on startup - do a write test check
    $file = realpath('../frontend_storage') . '/' . $prefix . 'cache2';
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
      file_put_contents($this->filepath, serialize($this->data));
    }
    // probably want to retain this lock
  }

  function __destruct() {
    $this->commit();
    //$this->closeLock($this->lockpath, $this->pid);
    unlink($this->lockpath);
  }

  function manyFilesGetPath($filename) {
    // minus extension
    // last X => xx/xx/filename.ext
    // mkdir
    // return directory
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
      if ($diff > 300) {
        // clear lock
        $clearLock = true;
      }
      if (!$clearLock) return false;
      // we're just about to stomp on it anyways
      //unlink($lockpath);
    }
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
    $this->changed = false;
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

  function singleLargeValueFile($key) {
    $keyhash = md5($key);
    return $this->filebase . '_' . $keyhash . '.php';
  }

  function clear($key) {
    $singleLargeValueFile = $this->singleLargeValueFile($key);
    if (file_exists($singleLargeValueFile)) {
      return unlink($singleLargeValueFile);
    }
    unset($this->data[$key]);
    return true;
  }

  function get($key) {
    $singleLargeValueFile = $this->singleLargeValueFile($key);
    // a tad slow because of the io
    // but
    if (file_exists($singleLargeValueFile)) {
      return file_get_contents($singleLargeValueFile);
    }
    return empty($this->data[$key]) ? '' : $this->data[$key];
  }

  function set($key, $val) {
    if (sizeof($val) > 1024) {
      return file_put_contents($this->singleLargeValueFile($key), $val);
    }
    $this->changed = true;
    $this->data[$key] = $val;
    return true;
  }
}

?>
