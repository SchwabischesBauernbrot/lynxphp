<?php

$curlLog = array();

// we could reuse the curl handle
// but we'd need to reset certain settings each time
// but ultimately we should rather aim for one curl request per page total
function curlHelper($url, $fields='', $header='', $user='', $pass='', $method='AUTO') {
  if (DEV_MODE) {
    $start = microtime(true);
  }
  if (is_array($fields) && $method === 'AUTO') {
    $fields_string='';
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
    $fields_string=rtrim($fields_string, '&');
  } else {
    $fields_string=$fields;
  }

  if (!function_exists('curl_init')) {
    echo "PHP does not have the curl extension installed<br>\n";
    exit(1);
  }

  //open handle
  $ch = curl_init();

  //set the url, number of POST vars, POST data
  curl_setopt($ch,CURLOPT_URL,$url);
  if (is_array($fields)) {
    curl_setopt($ch,CURLOPT_POST,count($fields));
  } else {
    curl_setopt($ch,CURLOPT_POST, $fields?true:false);
  }
  curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
  if ($header) {
    $headers = array();
    foreach($header as $k => $v) {
      $headers[] = $k . ': ' . $v;
    }
    curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
  }
  if ($user && $pass) {
    curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pass);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  }
  if ($method==='AUTO') {
    if (!$fields) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    }
  } else
  if ($method==='PUT') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  } else
  if ($method==='GET') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
  }
  //curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
  //curl_setopt($ch,CURLOPT_HEADER,true); // include response header in output
  //execute post
  $result = curl_exec($ch);

  //close handle
  curl_close($ch);
  if (DEV_MODE) {
    global $curlLog;
    $curlLog[] = array(
      'method' => $method,
      'url' => $url,
      'took' => (microtime(true) - $start) * 1000,
    );
  }

  return $result;
}

function make_file($tmpfile, $type, $filename) {
  return curl_file_create($tmpfile, $type, $filename);
}

function curl_log_report() {
  global $curlLog;
  $ttl = 0;
  echo '<ol>';
  foreach($curlLog as $l) {
    $m = ($l['method'] === 'AUTO' ? 'GET' : $l['method']);
    echo '<li>' . $m . ' <a target=_blank href="' . $l['url'] . '?prettyPrint=1">' . $l['url'] . '</a> took ' . $l['took'] . 'ms';
    $ttl += $l['took'];
  }
  echo '</ol>';
  echo count($curlLog), ' requests took ', $ttl, 'ms';
}

?>
