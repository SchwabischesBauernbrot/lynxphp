<?php

include_once 'base.php';

// the size of the file caused more contention
// do we need pooling and/or multiple files

// added large value (>1k) memory reduction
// FIXME: we need to worry about number of files in a directory
class file_scratch_driver extends scratch_implementation_base_class {
  function __construct($prefix = '') {
    // we need to protect information, such as IP addresses
    // so we'll use the php extension so that only PHP can access this info
    $this->filebase = '../frontend_storage/'.$prefix.'cache_v2';
    $this->file = '../frontend_storage/'.$prefix.'cache_v2.php';
    $this->lock = '../frontend_storage/'.$prefix.'cache_v2.lock';
    if (!file_exists($this->file)) {
      if (!touch($this->file)) {
        echo "Can't create[", getcwd(), '/', $this->file, "] webserver can't create files or write to data?<br>\n";
        exit();
      }
    }
  }

  // FIXME: file permissions
  function checkPerms() {
    $username = posix_getpwuid(posix_geteuid())['name'];
    //echo "username[$username]<br>\n";
    if ($username !== USER) {
      // we need to fix perms
      recurse_chown_chgrp($this->file, USER, USER);
    }
  }

  function getlock() {
    $lock = $this->lock;
    /*
    if (file_exists($lock)) {
      // wait until unlocked
      $stilllocked = 1;
      // retry 3 times in 1s
      for($i = 0; $i < 3; $i++) {
        if (!file_exists($lock)) {
          $stilllocked = 0;
          break;
        }
        usleep(333);
      }
      if ($stilllocked) {
        echo "can't get lock[$lock] <!-- ", gettrace(), " --><br>\n";
        return false;
      }
    }
    */
    // wait until lock acquired
    $stilllocked = 1;
    for($i = 0; $i < 30; $i++) {
      if (@mkdir($lock)) {
        $stilllocked = 0;
        break;
      }
      # optional wait between lock attempts, could use usleep()
      usleep(100);
      # may want to return early if reach a max number of tries to get lock
    }
    if ($stilllocked) {
      echo "can't get lock[$lock] <!-- ", gettrace(), " --><br>\n";
      return false;
    }
    global $now;
    file_put_contents($lock . '/lock', $now . '_' . posix_getpid());
    // why bother checking the perms on the non-lock file?
    //$this->checkPerms();
    if (!file_exists($lock . '/lock')) {
      echo "cant create lock[$lock]<br>\n";
      return false;
    }
    // could check lock to make sure we got it
    return true;
  }

  function waitForFileExists() {
    if (!file_exists($this->file)) {
      $dne = 1;
      for($i = 0; $i < 30; $i++) {
        if (file_exists($this->file)) {
          return true;
          break;
        }
        usleep(100);
      }
      return false;
    }
    return true;
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
    if (!$this->getlock()) {
      return false;
    }
    if (!$this->waitForFileExists()) {
      return false;
    }
    $fp = fopen($this->file, 'r');
    $tmpfname = tempnam('/tmp', 'lynxphp');
    $wfp = fopen($tmpfname, 'w');
    if (!$fp || !$wfp) {
      unlink($this->lock . '/lock');
      rmdir($this->lock);
      return false;
    }
    $found = 0;
    while(($line = fgets($fp)) !== false) {
      $tline = trim($line);
      list($lKey, $sValue) = explode('_:_', $tline, 2);
      if ($lKey !== $key) {
        fputs($wfp, $line);
      }
    }
    fclose($fp);
    fclose($wfp);
    unlink($this->file);
    rename($tmpfname, $this->file);
    $this->checkPerms();
    // unlock
    unlink($this->lock . '/lock');
    rmdir($this->lock);
    return true;
  }

  function get($key) {
    $singleLargeValueFile = $this->singleLargeValueFile($key);
    if (file_exists($singleLargeValueFile)) {
      return file_get_contents($singleLargeValueFile);
    }
    if (!$this->waitForFileExists()) {
      return false;
    }
    $fp = fopen($this->file, 'r');
    //$lines=file($this->file);
    //foreach($lines as $line) {
    if (!$fp) return false;
    while(($line = fgets($fp)) !== false) {
      $tline = trim($line);
      list($lKey, $sValue) = explode('_:_', $tline, 2);
      if ($lKey === $key) {
        $data = unserialize($sValue);
        fclose($fp);
        return $data;
      }
    }
    fclose($fp);
    return false;
  }

  function set($key, $val) {
    if (sizeof($val) > 1024) {
      return file_put_contents($this->singleLargeValueFile($key), $val);
    }
    if (!$this->getlock()) {
      return false;
    }
    if (!$this->waitForFileExists()) {
      return false;
    }
    $fp = fopen($this->file, 'r');
    $tmpfname = tempnam('/tmp', 'doubleplus');
    $wfp = fopen($tmpfname, 'w');
    if (!$fp || !$wfp) {
      unlink($this->lock . '/lock');
      rmdir($this->lock);
      return false;
    }
    $found = 0;
    while(($line = fgets($fp)) !== false) {
      $tline = trim($line);
      list($lKey, $sValue) = explode('_:_', $tline, 2);
      if ($lKey !== $key) {
        fputs($wfp, $line);
      } else {
        // if key exists, replace it with new info
        //$data = unserialize($tline);
        fputs($wfp, $key . '_:_' . serialize($val) . "\n");
        $found = 1;
      }
    }
    fclose($fp);
    if (!$found) {
      // insert key at the end
      fputs($wfp, $key . '_:_' . serialize($val) . "\n");
    }
    fclose($wfp);
    unlink($this->file);
    // NFS safe
    copy($tmpfname, $this->file);
    unlink($tmpfname);
    $this->checkPerms();
    // unlock
    unlink($this->lock . '/lock');
    rmdir($this->lock);
    return true;
  }
}

?>
