<?php

include 'base.php';

class fileQueue extends baseQueue {
  function __construct() {
    // we need to protect site information, such as IP addresses
    // so we'll use the php file extension so that only PHP can access this info
    $this->file='data/queue.php';
    $this->lock='data/queue.lock';
    if (!file_exists($this->file)) {
      if (!touch($this->file)) {
        echo "Can't create[",$this->file,"] webserver can't create files or write to data?<br>\n";
        exit();
      }
    }
  }
  function getlock() {
    $lock=$this->lock;
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
    file_put_contents($lock, $ts.'_'.posix_getpid());
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
    $fp=fopen($this->file,'r');
    $tmpfname = tempnam('/tmp', 'futabilly');
    $wfp = fopen($tmpfname, 'w');
    if (!$fp || !$wfp) {
      unlink($this->lock);
      return true;
    }
    $found=0;
    while(($line=fgets($fp)) !== false) {
      $data=unserialize(trim($line));
      if ($data['k']!=$key) {
        fputs($wfp, $line);
      }
    }
    fclose($fp);
    fclose($wfp);
    unlink($this->file);
    rename($tmpfname, $this->file);
    // unlock
    unlink($this->lock);
    return true;
  }
  function get($key) {
    //$lines=file($this->file);
    //foreach($lines as $line) {
    $fp=fopen($this->file,'r');
    if (!$fp) return false;
    while(($line=fgets($fp)) !== false) {
      $data=unserialize(trim($line));
      if ($data['k']==$key) {
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
    $fp=fopen($this->file,'r');
    $tmpfname = tempnam('/tmp', 'futabilly');
    $wfp = fopen($tmpfname, 'w');
    if (!$fp || !$wfp) {
      unlink($this->lock);
      return true;
    }
    $found=0;
    while(($line=fgets($fp)) !== false) {
      $data=unserialize(trim($line));
      if ($data['k']!=$key) {
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
    rename($tmpfname, $this->file);
    // unlock
    unlink($this->lock);
    return true;
  }
}

?>