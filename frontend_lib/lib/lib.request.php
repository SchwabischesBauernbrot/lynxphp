<?php

// needs $now, DEV_MODE defined

include '../common/scratch_implementations/manual.php';

function logRequest_unlock($scratch) {
  unlink($scratch->lock . '/lock');
  rmdir($scratch->lock);
}

function logRequest($ip) {
  //if (DEV_MODE) echo "ip[$ip]<br>\n";
  // forgive localhost
  if ($ip === '::1' || $ip === '127.0.0.1') {
    return;
  }
  // tor will typically come in on a private ip
  // FIXME: still will need a rate limit...
  if (isPrivateIP($ip)) {
    return;
  }
  global $now;
  $readsPerMinPerIP = 30;

  //$haship = md5($ip);

  // scope this a single user, so shouldn't affect others
  $persist_scratch = new manual_scratch_driver('request_'.$ip.'_');

  // expire all expired
  $key = 'request_' . $ip;
  //if (DEV_MODE) echo "key[$key]<br>\n";

  // if this lock fails (stays open)
  // then the rate limiting completely fails...
  $havelock = false;
  for($i = 0; $i < 30; $i++) {
    if ($persist_scratch->getlock()) {
      $havelock = true;
      break;
    }
    usleep(100);
  }
  if (!$havelock) {
    http_response_code(500);
    echo "Could not obtain lock";
    exit();
  }
  $last = $persist_scratch->get($key . '_last');
  if ($last) {
    $diff = $now - $last;
    //if (DEV_MODE) echo "last[$last] diff[$diff]<br>\n";
    if ($diff > 600) {
      //if (DEV_MODE) echo "diff[$diff]<br>\n";
      $persist_scratch->clear($key . '_first');
      $persist_scratch->clear($key . '_last');
      $persist_scratch->clear($key . '_count');
    }
  }
  // check
  $cnt = $persist_scratch->get($key . '_count');
  $persist_scratch->set($key . '_last', $now);
  if (!$cnt) {
    //if (DEV_MODE) echo "new count[$cnt]<br>\n";
    $persist_scratch->set($key . '_first', $now);
    $persist_scratch->set($key . '_count', '1');
    logRequest_unlock($persist_scratch);
  } else {
    // get period
    $start = $persist_scratch->get($key . '_first');
    // set
    $persist_scratch->inc($key . '_count');
    logRequest_unlock($persist_scratch);

    // check
    $sdiff = $now - $start;
    //if (DEV_MODE) echo "start[$start] sdiff[$sdiff]<br>\n";
    // calculate limit for this period
    $limit = ceil($sdiff / 60) * $readsPerMinPerIP;
    //if (DEV_MODE) echo "cnt[$cnt] limit[$limit]<br>\n";
    if ($cnt > $limit) {
      // too much
      // sleep holds socket open
      // this can waste server resources
      if (0) {
        sleep(1);
      } else {
        http_response_code(429);
        //header('X-RateLimit-ip');
        header('Retry-After: 60');
        echo "Too Many Requests";
        exit();
      }
    }
  }
}

?>
