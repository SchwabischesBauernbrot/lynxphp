<?php



function logRequest($ip) {
  global $db, $models, $now;

  // how much history are we interested in?
  // so lets say you get 60 requests per min per ip

  // expire all expired
  $db->delete($models['request'], array('criteria' =>
    // only holds the record for 60s and then nukes it...
    // we don't want that because we want that history if they're active..
    // so clear any history when they're inactive for 15 mins
    // $db->make_direct
    array(array(($db->unixtime() . ' - updated_at'), '>', $db->make_direct('600')))
  ));
  //echo "ip[$ip]<br>\n";
  if ($ip === '::1' || $ip === '127.0.0.1') {
    return;
  }

  $res = $db->find($models['request'],
    array( 'criteria' => array( 'ip' => $ip, 'type' => 'backend') )
  );
  $row = array('count' => 0, 'updated_at' => 0, 'requestid' => 0);
  if ($db->num_rows($res)) {
    $row = $db->get_row($res); // should only be one
  }
  $db->free($res);
  $count = $row['count'] + 1;
  $readsPerMinPerIP = 60;

  // update or insert?
  if ($row['requestid']) {
    $sdiff = $now - $row['created_at'];
    $limit = ceil($sdiff / 60) * $readsPerMinPerIP;
    $ldiff = $now - $row['updated_at'];
    //echo "started [$sdiff]s ago limit[$limit]<br>\n";
    //echo "last    [$ldiff]s ago count[$count]<br>\n";
    if ($limit < $count) {
      //echo "Delay<br>\n";
      sleep(1);
    }
    $db->update($models['request'], array('count' => $count), array('criteria' => array('requestid'=>$row['requestid'])));
  } else {
    $db->insert($models['request'], array(array('ip'=>$ip, 'type'=>'backend', 'count'=>1)) );
  }
}

?>