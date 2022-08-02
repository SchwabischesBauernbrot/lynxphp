<?php

include 'base.php';

// the size of the file caused more contention
// do we need pooling and/or multiple files
class file_scratch_driver extends scratch_implementation_base_class {
  function __construct() {
    // we need to protect information, such as IP addresses
    // so we'll use the php extension so that only PHP can access this info
    $this->file = '../frontend_storage/cache.php';
    $this->lock = '../frontend_storage/cache.lock';
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
    if (file_exists($lock)) {
      // wait until unlocked
      $stilllocked=1;
      // retry 3 times in 1s
      for($i=0; $i<3; $i++) {
        if (!file_exists($lock)) {
          $stilllocked=0;
          break;
        }
        usleep(333);
      }
      if ($stilllocked) {
        echo "cant get lock[$lock]<br>\n";
        return false;
      }
    }
    global $ts;
    file_put_contents($lock, $ts . '_' . posix_getpid());
    // why bother checking the perms on the non-lock file?
    //$this->checkPerms();
    if (!file_exists($lock)) {
      echo "cant create lock[$lock]<br>\n";
      return false;
    }
    // could check lock to make sure we got it
    return true;
  }

  function clear($key) {
    if (!$this->getlock()) {
      return false;
    }
    $fp = fopen($this->file, 'r');
    $tmpfname = tempnam('/tmp', 'lynxphp');
    $wfp = fopen($tmpfname, 'w');
    if (!$fp || !$wfp) {
      unlink($this->lock);
      return true;
    }
    $found = 0;
    while(($line = fgets($fp)) !== false) {
      $data = unserialize(trim($line));
      if ($data['k'] != $key) {
        fputs($wfp, $line);
      }
    }
    fclose($fp);
    fclose($wfp);
    unlink($this->file);
    rename($tmpfname, $this->file);
    $this->checkPerms();
    // unlock
    unlink($this->lock);
    return true;
  }

  function get($key) {
    //$lines=file($this->file);
    //foreach($lines as $line) {
    $fp = fopen($this->file, 'r');
    if (!$fp) return false;
    while(($line = fgets($fp)) !== false) {
      $data = unserialize(trim($line));
      if ($data['k'] === $key) {
        fclose($fp);
        return $data['v'];
      }
    }
    fclose($fp);
    return false;
  }

  function set($key, $val) {
    if (!$this->getlock()) {
      return false;
    }
    $fp = fopen($this->file,'r');
    $tmpfname = tempnam('/tmp', 'doubleplus');
    $wfp = fopen($tmpfname, 'w');
    if (!$fp || !$wfp) {
      unlink($this->lock);
      return true;
    }
    $found=0;
    // FIXME: change format so don't need to unserailize to get the k
    // or just serialize the whole thing...
    while(($line = fgets($fp)) !== false) {
      $data = unserialize(trim($line));
      if ($data['k'] !== $key) {
        fputs($wfp, $line);
      } else {
        // if key exists, replace it with new info
        fputs($wfp, serialize(array('k'=>$key, 'v'=>$val))."\n");
        $found=1;
      }
    }
    fclose($fp);
    if (!$found) {
      // insert key at the end
      fputs($wfp, serialize(array('k'=>$key, 'v'=>$val))."\n");
    }
    fclose($wfp);
    unlink($this->file);
    // NFS safe
    copy($tmpfname, $this->file);
    unlink($tmpfname);
    $this->checkPerms();
    // unlock
    unlink($this->lock);
    return true;
  }
}

?>