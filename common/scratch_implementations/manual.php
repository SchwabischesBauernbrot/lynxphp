<?php

include_once 'base.php';

// manual file scratch driver
// mainly for lib.request

// believe this is based off file.php driver

// its slow
// but we have to serialize reading/writing ops
// also can break on normal users
// makes the site brittle
// does keep the load down
// but writes a lot to the disk
// FIXME: we need to worry about number of files in a directory

// new design idea
// what if we make a file for every second (or even $now)
// every sec is fine if we append_only logging...
// so we bucket for every minute... keep the last hour or so maybe
// one per day, keep two days, hash the ips
// record limit hits as hints
// we read a range, total
// nuke any that have expired...
// only need to total the current ip and whether it's a read, write, violation

class manual_scratch_driver extends scratch_implementation_base_class {
  function __construct($prefix = '') {
    // we need to protect information, such as IP addresses
    // so we'll use the php extension so that only PHP can access this info
    // why the manual postfix? just to remind a different driver
    $this->file = '../frontend_storage/'.$prefix.'manual.php';
    $this->lock = '../frontend_storage/'.$prefix.'manual.lock';

    // always ensure the file exists
    if (!file_exists($this->file)) {
      if (!touch($this->file)) {
        echo "Can't create[", getcwd(), '/', $this->file, "] webserver can't create files or write to data?<br>\n";
        exit();
      }
      $this->checkPerms();
    }
    $this->changed = false;
    // we also no longer serialize/unserialize the data
    // because we only deal with scalars atm
    $this->memory = false;
  }

  function checkPerms() {
    $username = posix_getpwuid(posix_geteuid())['name'];
    //echo "username[$username]<br>\n";

    if ($username !== USER) {
      // we need to fix ownership perms
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

    // try to acquire lock by just doing
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
    $lockfile = $lock . '/lock';
    global $now;
    if ($stilllocked) {
      // well lets do some additional investigation
      if (file_exists($lockfile)) {
        // read it and see if it's expired
        $str = file_get_contents($lockfile);
        list($then, $pid) = explode('_', $str, 2);
        $diff = $now - $then;
        if ($diff > 300) {
          // stale lock move forward
          $this->unlock();
          // move forward properly
          return $this->getlock();
        } else {
          // recent lock
          echo "scratch_manual: can't get lock[$lock]<br>\n";
          return false;
        }
      } else {
        // maybe about to release lock or stuck
        // maybe if this fails then return false;
        if (!rmdir($this->lock)) {
          echo "scratch_manual: can't remove lockdir[$lock]<br>\n";
          return false;
        }
        // move forward properly
        return $this->getlock();
      }
    }
    // acquire lock
    file_put_contents($lockfile, $now . '_' . posix_getpid());
    // why bother checking the perms on the non-lock file?
    //$this->checkPerms();
    // we'll also check it on unlock
    if (!file_exists($lockfile)) {
      echo "scratch_manual: cant create lockfile[$lockfile]<br>\n";
      return false;
    }
    // could check lock to make sure we got it
    $lines = file($this->file);
    $this->memory = array();
    foreach($lines as $l) {
      $tline = trim($l);
      list($lKey, $sValue) = explode('_:_', $tline, 2);
      // don't deserialize here, well do when asked
      $this->memory[$lKey] = $sValue;
    }
    $this->changed = false;
    return true;
  }

  // 2 functions or two return codes?
  function unlock() {
    if ($this->memory !== false && is_array($this->memory)) {
      $tmpfname = tempnam('/tmp', 'doubleplus');
      $data = '';
      foreach($this->memory as $k => $v) {
        $data .= $k . '_:_' . $v . "\n";
      }
      file_put_contents($tmpfname, $data);
      /*
      $wfp = fopen($tmpfname, 'w');
      if (!$wfp) {
        return false;
      }
      foreach($this->memory as $k => $v) {
        fputs($wfp, $k . '_:_' . $v . "\n");
      }
      fclose($wfp);
      */
      unlink($this->file);
      // rename is NFS safe but can't move across parition boundaries
      copy($tmpfname, $this->file);
      unlink($tmpfname);
      $this->checkPerms();
    } // else there's an issue
    unlink($this->lock . '/lock');
    if (!rmdir($this->lock)) {
      echo "scratch_manual: can't remove lockdir[$lock]<br>\n";
      return false;
    }
    return true;
  }

  // non of these call getlock
  // because we do manual locking
  // so we can basically do a transaction to the file

  function clear($key) {
    $this->changed = true;
    unset($this->memory[$key]);
  }

  function get($key) {
    // we would deserialize here
    return empty($this->memory[$key]) ? false : $this->memory[$key];
  }

  function set($key, $val) {
    $this->changed = true;
    // we would serialize here
    $this->memory[$key] = $val;
  }
}

?>
