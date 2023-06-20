<?php

include_once 'base.php';

// its slow
// but we have to serialize reading/writing ops
// also can break on normal users
// makes the site brittle
// does keep the load down
// but writes a lot to the disk
class manual_scratch_driver extends scratch_implementation_base_class {
  function __construct($prefix = '') {
    // we need to protect information, such as IP addresses
    // so we'll use the php extension so that only PHP can access this info
    $this->file = '../frontend_storage/'.$prefix.'manual.php';
    $this->lock = '../frontend_storage/'.$prefix.'manual.lock';
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
    if ($username !== 'www-data') {
      // we need to fix perms
      // FIXME: debian only
      // was USER / USER
      recurse_chown_chgrp($this->file, 'www-data', 'www-data');
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
    // try to acquire lock
    $stilllocked = 1;
    for($i = 0; $i < 1000; $i++) {
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
    // acquire lock
    global $ts;
    file_put_contents($lock . '/lock', $ts . '_' . posix_getpid());
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

  function clear($key) {
    if (!$this->waitForFileExists()) {
      return false;
    }
    $fp = fopen($this->file, 'r');
    if (!$fp) {
      return false;
    }
    $tmpfname = tempnam('/tmp', 'lynxphp');
    $wfp = fopen($tmpfname, 'w');
    if (!$wfp) {
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
    copy($tmpfname, $this->file);
    unlink($tmpfname);
    $this->checkPerms();
    return true;
  }

  function get($key) {
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
    if (!$this->waitForFileExists()) {
      return false;
    }
    $fp = fopen($this->file, 'r');
    if (!$fp) {
      return false;
    }
    $tmpfname = tempnam('/tmp', 'doubleplus');
    $wfp = fopen($tmpfname, 'w');
    if (!$wfp) {
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
    return true;
  }
}

?>
