<?php

// apache/nginx hack
if (!function_exists('getallheaders')) {
  function getallheaders() {
    $headers = '';
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }
    return $headers;
  }
}

// persisent across requests but purgeable
// so like sessions but unique across the app not browser

// table
// pub/sub? lists

// we may want to move sphinx over redis to reduce the number of requirements
// iff we find sphinx to be an adequate replacement

if (class_exists('Redis')) {
  class memoryhash extends redisStub {
  }
// deprecated until further testing
/*
} elseif (class_exists('APCIterator')) {
  class memoryhash extends apcStub {
  }
} elseif (function_exists('mysqli_connect')) {
  global $CONFIG;
  // this is WAY slower than redis (but still pretty compact?)
  // maybe like 100x to 1000x slower
  if (isset($CONFIG['sphinx'])) {
    class memoryhash extends sphinxStub {
    }
  } else {
    class memoryhash extends mysqlStub {
    }
  }
} elseif (function_exists('shm_attach')) {
  class memoryhash extends sharedmemStub {
  }
*/
} else {
  class memoryhash extends fileStub {
  }
}

// we either need a nukeAll functionality
// or a way to discover keys that have been set
class baseMemoryHash {
  function inc($key,$step=1) {
    $count=(int)$this->get($key);
    $count+=$step;
    $this->set($key,$count);
    return $count;
  }
  function subscribe($queue) {
  }
  function receive($queue, $waitformsg=false) {
    $msgs=$this->get('queue:'.$queue);
    $this->clear('queue:'.$queue);
    return $msgs;
  }
  function send($queue,$message) {
    $msgs=$this->get('queue:'.$queue);
    $msgs[]=$message;
    $this->set('queue:'.$queue, $msgs);
  }
}

class sharedmemStub extends baseMemoryHash {
  function __construct() {
    $systemId = ftok(__FILE__, 't');
    //echo "systemId[$systemId]<br>\n";
    /*
    // set up and connect
    $shid = shmop_open($systemId, "a", 0666, 0);
    if (empty($shid)) {
      // shared memory doesn't exist
      $shid = shmop_open($systemId, "c", 0666, );
    }
    */

    //table
    //$this->shm=shm_attach(0x701da13b,33554432);    //allocate shared memory
    $this->shm_id = shm_attach($systemId, 20*1024);
    if ($this->shm_id===false) {
      echo "can't attach to shared memory<br>\n";
    }

    // pub/sub
    // Create System V Message Queue. Integer value is the number of the Queue
    //if (!msg_queue_exists(100379)) {
    //$queue = msg_get_queue(100379);
    // http://php.net/manual/en/function.msg-stat-queue.php
    //msg_set_queue ($queue, array ('msg_perm.uid'=>'80'));

  }
  public function __destruct() {
    // detach self
    if ($this->shm_id) {
      shm_detach($this->shm_id);
    }
    //unset($this);
  }
  function keytoint($key) {
    return preg_replace('/[^0-9]/', '', (preg_replace('/[^0-9]/', '', md5($key))/35676248)/619876); // text to number system.
  }
  function clear($key) {
    // we get warnings if they don't exists
    if (shm_has_var($this->shm_id, $this->keytoint($key))) {
      return shm_remove_var($this->shm_id, $this->keytoint($key));
    } else {
      return false;
    }
  }
  function get($key) {
    if (shm_has_var($this->shm_id, $this->keytoint($key))) {
      return shm_get_var($this->shm_id, $this->keytoint($key));
    } else {
      return false;
    }
  }
  function set($key, $val) {
    //echo "key[$key] => [",$this->keytoint($key),"]<br>\n";
    shm_put_var($this->shm_id, $this->keytoint($key), $val);
  }
  function subscribe($queue) {
    // create it incase it's not created
    msg_get_queue($this->keytoint($queue));
  }
  function receive($queue, $waitformsg=false) {
    $qh=msg_get_queue($this->keytoint($queue));
    $qs=msg_stat_queue($qh);
    $msgtype=0;
    $message=false;
    $err=false;
    if ($qs['msg_qnum']>0) {
      // after we get the message it'll be removed from the queue and no one else can read it
      msg_receive($qh, 0, $msgtype, 10*1024, $message, true, ($waitformsg?0:MSG_IPC_NOWAIT) | MSG_NOERROR,$err);
    }
    return $message;
  }
  function send($queue,$message) {
    $qh=msg_get_queue($this->keytoint($queue));
    // can fail if no room in queue
    $sent=msg_send($qh, 1, $message, true, false, $err);
    if ($err) echo "send err[$err]<br>\n";
    return $sent;
  }
}

function recvMsg($redis, $queue, $msg) {
  global $db;
  $db->queuedata[$queue][]=$msg;
}


class redisStub extends baseMemoryHash {
  function __construct() {
    global $CONFIG;
    $this->r=new Redis();
    if (file_exists('/tmp/redis.sock')) {
      $this->r->pconnect('/tmp/redis.sock');
    } else {
      $this->r->pconnect('127.0.0.1',6379);
    }
  }
  function clear($key) {
    $this->r->del($key);
  }
  function get($key) {
    $test=$this->r->get($key);
    if (!$test) {
      return $test; // empty, 0 or ''
    } else
    if (is_numeric($test)) {
      return $test;
    } else {
      $decoded=unserialize($test);
      // it's ok if test was empty
      if ($decoded===false) {
        echo "Can't decode[$test]<br>\n";
        return false;
      } else {
        return $decoded;
      }
    }
  }
  function set($key, $val) {
    if (is_numeric($val)) {
      $this->r->set($key, $val);
    } else {
      //echo "Key[$key] count[",count($val),"]<br>\n";
      $this->r->set($key, serialize($val));
    }
  }
  function inc($key, $step=1) {
    //echo "preinc[",$this->r->get($key),"]<br>\n";
    $test=$this->r->incrBy($key, $step); // will set and incr (should never return 0)
    //echo "postinc[",$this->r->get($key),"]<br>\n";
    //echo "[$test]=inc[$key][$step]<br>\n";
    return $test;
  }
  function recvMsg($redis, $queue, $msg) {
    $this->queuedata[$queue][]=$msg;
  }

  function receive($queue, $waitformsg=false) {
    $msgs=$this->r->get('list:'.$queue);
    $this->clear('list:'.$queue);
    if (!is_array($msgs)) return array();
    $data=array();
    foreach($msgs as $msg) {
      $data[]=unserialize($msg);
    }
    return $data;
  }
  function send($queue,$message) {
    //$msgs=$this->get('queue:'.$queue);
    //$msgs[]=$message;
    //$this->set('queue:'.$queue, $msgs);
    $this->r->rPush('list:'.$queue, serialize($message));
  }


  /*
  function subscribe($queue) {
    // this blocks
    $this->r->subscribe(array($queue.''), function($r, $c, $m) {
      $this->queuedata[$c][]=$m;
    });
  }
  function receive($queue, $waitformsg=false) {
    //$this->r->subscribe(array($queue), array($this,'data'));
    // we can loop but how do we yield
    $data=$this->queuedata[$queue];
    $this->queuedata[$queue]=array();
    return $data;
  }
  function send($queue,$message) {
    //$this->r->publish($queue, $message);
  }
  */
}

class apcStub extends baseMemoryHash {
  function __construct() {
  }
  function clear($key) {
    apc_delete($key);
  }
  function get($key) {
    return apc_fetch($key);
  }
  function set($key, $val) {
    apc_store($key, $val);
  }
  function inc($key,$step=1) {
    return apc_inc($key,$step);
  }
}

class mysqlStub extends baseMemoryHash {
  function __construct() {
    global $CONFIG;
    $this->conn=false;
    if (isset($CONFIG['mysql'])) {
      $this->conn=mysqli_connect($CONFIG['mysql']);
      if ($this->conn) {
        mysqli_set_charset($this->conn, 'utf8');
      } else {
        echo "cant connect to mysql<br>\n";
      }
    }
    $this->dne=false;
  }
  function clear($key) {
    if (!$this->conn) return false;
    $sql='delete from futabilly_store where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
    mysqli_query($this->conn, $sql);
    $err=mysqli_error($this->conn);
    return $err;
  }
  function get($key) {
    if (!$this->conn) return false;
    $sql='select val from futabilly_store where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
    $res=mysqli_query($this->conn, $sql);
    $err=mysqli_error($this->conn);
    list($val)=mysqli_fetch_row($res);
    mysqli_free_result($res);
    // any unserailizeation? sure
    if (!$val || is_numeric($val)) {
      return $val; // empty, 0 or ''
    }
    $decoded=unserialize($val);
    // it's ok if test was empty
    if ($decoded===false) {
      echo "Can't decode[$val]<br>\n";
      return false;
    } else {
      return $decoded;
    }
  }
  function set($key, $val) {
    if (!$this->conn) return false;
    if (!is_numeric($val)) {
      //echo "Key[$key] count[",count($val),"]<br>\n";
      $val=serialize($val);
    }
    if ($this->dne) {
      $cnt=0;
      $this->dne=0; // reset for next set call
    } else {
      $sql='select count(*) from futabilly_store where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
      $res=mysqli_query($this->conn, $sql);
      $err=mysqli_error($this->conn);
      list($cnt)=mysqli_fetch_row($res);
      mysqli_free_result($res);
    }
    if ($cnt) {
      // update
      $sql='update futabilly_store set val=\''.mysqli_real_escape_string($this->conn, $val).'\'
        where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
      mysqli_query($this->conn, $sql);
      $err=mysqli_error($this->conn);
    } else {
      // insert
      $sql='insert into futabilly_store (key, val) values
        (\''.mysqli_real_escape_string($this->conn, $key).'\', \''.mysqli_real_escape_string($this->conn, $val).'\')';
      mysqli_query($this->conn, $sql);
      $err=mysqli_error($this->conn);
    }
    if ($err) {
      return false;
    } else {
      return true;
    }
  }
  // slightly optimized but no waste
  function inc($key, $step=1) {
    if (!$this->conn) return false;
    $sql='select count(*) from futabilly_store where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
    $res=mysqli_query($this->conn, $sql);
    $err=mysqli_error($this->conn);
    list($cnt)=mysqli_fetch_row($res);
    mysqli_free_result($res);
    if ($cnt) {
      // update
      $sql='update futabilly_store set val=val+'.$step.' where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
      mysqli_query($this->conn, $sql);
      $err=mysqli_error($this->conn);
      return $err;
    } else {
      // insert
      $this->dne=true;
      return $this->set($key, $step);
    }
  }
}

// can support mset optimization too
class sphinxStub extends mysqlStub {
  function __construct() {
    global $CONFIG;
    $this->conn=false;
    if (isset($CONFIG['sphinx'])) {
      $this->conn=mysqli_connect($CONFIG['sphinx']);
      if ($this->conn) {
        mysqli_set_charset($this->conn, 'utf8');
      } else {
        echo "cant connect to sphinx<br>\n";
      }
    }
    $this->dne=false;
  }
  function set($key, $val) {
    if (!$this->conn) return false;
    if (!is_numeric($val)) {
      //echo "Key[$key] count[",count($val),"]<br>\n";
      $val=serialize($val);
    }
    if ($this->dne) {
      $cnt=0;
      $this->dne=0; // reset for next set call
    } else {
      $sql='select count(*) from futabilly_store where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
      $res=mysqli_query($this->conn, $sql);
      $err=mysqli_error($this->conn);
      list($cnt)=mysqli_fetch_row($res);
      mysqli_free_result($res);
    }
    // or we could crc to a docid and replace into that
    if ($cnt) {
      // update
      $sql='update futabilly_store set val=\''.mysqli_real_escape_string($this->conn, $val).'\'
        where key=\''.mysqli_real_escape_string($this->conn, $key).'\'';
      mysqli_query($this->conn, $sql);
      $err=mysqli_error($this->conn);
    } else {
      // insert
      for($r=0; $r<9999; $r++) {
        $err='';

        $res=mysqli_query($this->conn, 'select max(id) from futabilly_store');
        //echo mysqli_error($this->conn),"<br>\n";
        list($docid)=mysqli_fetch_row($res);
        mysqli_free_result($res);
        if (!$docid) $docid=0;

        $sql='insert into futabilly_store (id, key, val) values
          ('.$docid.', \''.mysqli_real_escape_string($this->conn, $key).'\', \''.mysqli_real_escape_string($this->conn, $val).'\')';
        mysqli_query($this->conn, $sql);
        $err=mysqli_error($this->conn);

        if ($err) {
          //echo $err," in sql[$sql] had [$err]<br>\n";
        } else {
          break;
        }
      }
    }
    if ($err) {
      return false;
    } else {
      return true;
    }
  }
}

// another type where two levels would help
class fileStub extends baseMemoryHash {
  function __construct() {
    // we need to protect informatino in memory, such as IP addresses
    // so we'll use the php extension so that only PHP can access this info
    $this->file='data/cache.php';
    $this->lock='data/cache.lock';
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
