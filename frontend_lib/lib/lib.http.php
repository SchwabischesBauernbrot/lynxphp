<?php

$curlLog = array();

// if you have count you need ?
function paramsToQuerystringGroups($params) {
  if (!$params) return array();
  $qarr = array();
  foreach($params as $k => $v) {
    $qarr[] = $k . '=' . $v;
  }
  return $qarr;
}

function parseHeaders($response) {
  $lines = explode("\r\n", $response);
  array_shift($lines);
  $headers = array();
  foreach($lines as $h) {
    if (!$h) continue;
    if (strpos($h, ': ') !== false) {
      list($k, $v) = explode(': ', $h);
      // normalize keys
      $headers[strtolower($k)] = $v;
    } else {
      echo "h[$h] has no :\n";
    }
  }
  return $headers;
}

// we could reuse the curl handle
// but we'd need to reset certain settings each time
// but ultimately we should rather aim for one curl request per page total

// router::getMaxMtime uses this
function request($options = array()) {
  extract(ensureOptions(array(
    'url' => '',
    'method' => 'AUTO',
    'headers' => array(),
    'user' => false,
    'pass' => false,
    'body' => false,
  ), $options));
  $header = '';

  // workaround curlHelper compatibility (in at least consume_beRsrc)
  if ($headers === '') $headers = array();
  if (count($headers)) {
    $header = $headers;
  }
  return curlHelper($url, $body, $header, $user, $pass, $method);
}

//open handle
$ch = curl_init();

$curl_headers = array();

function curlHelper($url, $fields='', $header='', $user='', $pass='', $method='AUTO') {
  global $ch;
  if (DEV_MODE) {
    $start = microtime(true);
  }

  if (!$url) {
    echo gettrace(), " no url given to curlHelper<br>\n";
    exit(1);
  }

  if (!function_exists('curl_init')) {
    echo "PHP does not have the curl extension installed<br>\n";
    exit(1);
  }
  // maybe only do this if AUTO or POST?
  //if (is_array($fields) && ($method === 'AUTO' || $method === 'POST')) {
  $hasFields = (is_array($fields) ? count($fields) : $fields) ? true : false;

  curl_reset($ch); // php 5.5+

  //set the url, number of POST vars, POST data
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, $hasFields);

  // can be an urlencoded string or an array
  // an array will set "Content-type to multipart/form-data"
  // if you send files, this has to be an array
  // https://stackoverflow.com/a/15200804
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  if ($header) {
    $headers = array();
    foreach($header as $k => $v) {
      $headers[] = $k . ': ' . $v;
    }
    //echo '<pre>headers', print_r($headers, 1), '</pre>', "\n";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  }
  if ($user && $pass) {
    curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  }
  if ($method === 'AUTO') {
    $method = 'POST'; // for logging
    if (!$hasFields) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      $method = 'GET'; // for logging
    } else {
      //echo "fields[$fields_string]<br>\n";
    }
  } else
  if ($method ===' PUT') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  } else
  if ($method === 'GET') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
  } else
  if ($method === 'DELETE') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
  } else
  if ($method === 'HEAD') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    // apache writes all this shit to the error_log too
    //curl_setopt($ch, CURLOPT_VERBOSE, 1);
  }
  // to get the request header, but we have those...
  // but maybe we need to see what's actuall sent on the wire?
  //curl_setopt($ch, CURLINFO_HEADER_OUT, 1); // this makes curl_getinfo($ch, CURLINFO_HEADER_OUT) work

  // we need this for everything
  //if (DEV_MODE) {
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  //}

  //curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_TIMEOUT, 45);

  //execute post
  $txt = curl_exec($ch);

  $infos = curl_getinfo($ch); // curl_info
  $header_size = $infos['header_size'];
  $respHeader = substr($txt, 0, $header_size);
  global $curl_headers;
  $curl_headers[] = $respHeader;
  if ($method === 'HEAD') {
    $result = $respHeader;
  } else {
    $result = substr($txt, $header_size);
  }

  if (DEV_MODE) {
    global $curlLog;
    $curlLog[] = array(
      'method' => $method,
      'url' => $url,
      'trace' => gettrace(),
      'postData' => $fields,
      'took' => (microtime(true) - $start) * 1000,
      'requestHeaders' => $header,
      // gets the out header...
      //'responseHeaders' => curl_getinfo($ch, CURLINFO_HEADER_OUT),
      'responseHeaders' => $respHeader,
      'result' => $result,
      'curlInfo' => $infos,
      // $l['curlInfo']['http_code']
      //'statusCode' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
    );
  }

  //close handle
  //curl_close($ch);

  return $result;
}

function request_getLastHeader() {
  global $curl_headers;
  return $curl_headers[count($curl_headers) - 1];
}

function make_file($tmpfile, $type, $filename) {
  return curl_file_create($tmpfile, $type, $filename);
}

function curl_log_clear() {
  global $curlLog;
  $curlLog = array();
}

function curl_log_echo_header($l) {
  $headers = parseHeaders($l['result']);
  unset($headers['report-to']);
  unset($headers['expect-ct']);
  unset($headers['nel']);
  unset($headers['alt-svc']);
  unset($headers['cf-ray']);
  unset($headers['strict-transport-security']);
  unset($headers['x-frame-options']);
  unset($headers['x-content-type-options']);
  unset($headers['x-xss-protection']);
  //unset($headers['cf-cache-status']);
  unset($headers['server']);
  // a table format maybe better here...
  echo '<table>';
  foreach($headers as $k => $v) {
    echo '<tr><th>', $k, '<td>', $v, "\n";
  }
  echo '</table>';
  //echo '  <pre>', htmlspecialchars(print_r($headers, 1)), '</pre>', "\n";
  // FIXME: clean this up and integrate it better
  echo '  <pre>requestHeaders', htmlspecialchars(print_r($l['requestHeaders'], 1)), '</pre>', "\n";
  //echo '  <pre>statusCode', htmlspecialchars(print_r($l['statusCode'], 1)), '</pre>', "\n";
  //echo '  <pre>result', htmlspecialchars(print_r($l['result'], 1)), '</pre>', "\n";
}

function curl_log_report() {
  global $curlLog;
  $ttl = 0;
  echo '<ol>';
  foreach($curlLog as $l) {
    $m = ($l['method'] === 'AUTO' ? 'GET' : $l['method']);
    $joinChar = array(true => '&', false => '?');
    $hasQ = strpos($l['url'], '?') !== false;

    $clickUrl = str_replace(BACKEND_BASE_URL, BACKEND_PUBLIC_URL, $l['url']);

    echo '<li>' . $m . ' <a target=_blank rel=noopener href="' . $clickUrl . $joinChar[$hasQ] . 'prettyPrint=1">' . $l['url'] . '</a> took ' . $l['took'] . 'ms => ' . $l['curlInfo']['http_code'];
    if ($m === 'POST' && isset($l['postData'])) {
      if (is_array($l['postData'])) {
        echo ' [', print_r($l['postData'], 1), ']';
      } else {
        echo ' [', $l['postData'], ']';
      }
    }
    //echo '<span title="', $l['result'] , '">Result</span>';
    //echo '<span title="', $l['trace'] , '">Trace</span>';
    echo $l['trace'];
    echo '<details>';
    echo '  <summary>Response</summary>', "\n";
    if (isset($l['result'][0]) && ($l['result'][0] === '[' || $l['result'][0] === '{')) {
      echo '  <pre>', htmlspecialchars(json_encode(json_decode($l['result'], true), JSON_PRETTY_PRINT)), '</pre>', "\n";
    } else {
      if ($l['method'] === 'HEAD') {
        curl_log_echo_header($l);
      } else {
        echo '  [', $l['result'][0], ']<pre>', htmlspecialchars($l['result']), '</pre>', "\n";
      }
    }
    echo '  Response headers: <pre>', htmlspecialchars(print_r($l['responseHeaders'], 1)), '</pre>', "\n";
    //echo '  <pre>', htmlspecialchars(print_r($l, 1)), '</pre>', "\n";
    echo '</details>';
    // we can't get the headers unless it's a HEAD...
    /*
    if ($l['method'] !== 'HEAD') {
      curl_log_echo_header($l);
    }
    */
    $ttl += $l['took'];
  }
  echo '</ol>';
  echo count($curlLog), ' requests took ', number_format($ttl), 'ms<br>', "\n";
  // details?
}

?>