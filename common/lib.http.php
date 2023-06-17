<?php

// requires ensureOptions (currently in: common/common.php)
// also need DEV_MODE defined
if (!defined('DEV_MODE')) define('DEV_MODE', false);

$curlLog = array();

// lib.url

// if you have count you need ?
// params is expected to be a key/value array
// we return a joinable array of querystring sets
function paramsToQuerystringGroups($params) {
  if (!$params) return array();
  $qarr = array();
  foreach($params as $k => $v) {
    $qarr[] = $k . '=' . $v;
  }
  return $qarr;
}

function parseQuerystringFromStr($url) {
  if (strpos($url, '?') === false) return array();
  list($before, $after) = explode('?', $url, 2);
  $sets = explode('&', $after);
  $qs = array();
  foreach($sets as $set) {
    list($k, $v) = explode('=', $set, 2);
    $qs[$k] = $v;
  }
  return $qs;
}

// end lib.url

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
// probably should be something like http_client_request

// probably should take an URL as a parameter since it's required
// FUPs have to be multipart?

// should take a form option that throws it into application/x-www-form-urlencoded mode
function request($options = array()) {
  extract(ensureOptions(array(
    'url' => '',
    'method' => 'AUTO',
    'headers' => array(),
    'user' => false,
    'pass' => false,
    'body' => false,
    'devData' => false,
    'multipart' => 'auto',
  ), $options));
  $header = '';

  if ($multipart !== 'auto') {
    if ($multipart) {
      // $body needs to be an array
      if (!is_array($body)) {
        // convert string to array?
        // probably just should throw an error and not make the call
        echo gettrace(), " string body given to multipart<br>\n";
        exit(1);
      }
    } else {
      // $body needs to be a string
      if (is_array($body)) {
        // convert to string
        $list = array();
        foreach($body as $key => $value) {
          $list[] = $key . '=' . urlencode($value);
        }
        $body = join('&', $list);
      }
    }
  }

  // workaround curlHelper compatibility (in at least consume_beRsrc)
  if ($headers === '') $headers = array();
  if (count($headers)) {
    $header = $headers;
  }
  return curlHelper($url, $body, $header, $user, $pass, $method, $devData);
}

//open handle
if (!function_exists('curl_init')) {
  echo "PHP does not have the curl extension installed<br>\n";
  exit(1);
}
$ch = curl_init();

$curl_headers = array();

function curlHelper($url, $fields='', $header='', $user='', $pass='', $method='AUTO', $devData = '') {
  global $ch;
  if (DEV_MODE) {
    $start = microtime(true);
  }

  if (!$url) {
    echo gettrace(), " no url given to curlHelper<br>\n";
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

  // got array to string conversion, wtf...
  // could only support an array before php5 it seems
  // https://stackoverflow.com/a/5224895
  // but for file uploads we need array lol
  // we need a hint that's not a class check...
  //if (is_array($fields)) {
    //echo "<pre>fields", print_r($fields, 1), "</pre>\n";
    //echo "method[$method]<br>\n"; // POST
    //echo '<pre>headers', print_r($header, 1), '</pre>', "\n"; // no headers
    //$fields = http_build_query($fields);
  //}
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
  //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
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
  // but maybe we need to see what's actually sent on the wire?
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
      'requestHeadersIn' => $header,
      // gets the out header...
      'requestHeadersOut' => curl_getinfo($ch, CURLINFO_HEADER_OUT),
      'responseHeaders' => $respHeader,
      'result' => $result,
      'curlInfo' => $infos,
      'devData' => $devData,
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

function request_getLastLog() {
  global $curlLog;
  return $curlLog[count($curlLog) - 1];
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
  echo '  <pre>requestHeaders', htmlspecialchars(print_r($l['requestHeadersIn'], 1)), '</pre>', "\n";
  //echo '  <pre>requestHeaders', htmlspecialchars(print_r($l['requestHeadersOut'], 1)), '</pre>', "\n";
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
    //echo '<span title="', print_r($l['curlInfo'], 1) , '">CurlInfos</span>';
    //echo '<span title="', $l['result'] , '">Result</span>';
    //echo '<span title="', $l['trace'] , '">Trace</span>';
    echo $l['trace'];
    echo '<details>';
    echo '  <summary>Response [', number_format($l['curlInfo']['size_download']), ' bytes]</summary>', "\n";
    //echo "<pre>", htmlspecialchars(print_r($l['curlInfo'], 1)), "</pre>\n";
    // response body could be the most information
    if (isset($l['result'][0]) && ($l['result'][0] === '[' || $l['result'][0] === '{')) {
      echo '  <pre>', htmlspecialchars(json_encode(json_decode($l['result'], true), JSON_PRETTY_PRINT)), '</pre>', "\n";
    } else {
      if ($l['method'] === 'HEAD') {
        curl_log_echo_header($l);
      } else {
        // result can be empty on 304s
        echo '  [', (empty($l['result'][0]) ? '' : $l['result'][0]), ']<pre>', htmlspecialchars($l['result']), '</pre>', "\n";
      }
    }
    echo '  Response headers: <pre>', htmlspecialchars(print_r($l['responseHeaders'], 1)), '</pre>', "\n";
    //echo '  Request headers: <pre>', htmlspecialchars(print_r($l['requestHeadersOut'], 1)), '</pre>', "\n";
    if (!empty($l['devData'])) {
      echo '  Dev Data: <pre>', htmlspecialchars(print_r($l['devData'], 1)), '</pre>', "\n";
    }

    // would be good to decode where this route lives in the backend...

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

function _doWeHaveEtag($etag, $checkEtag) {
  //if (DEV_MODE) echo "compare [$etag]vs[$checkEtag]<br>\n";
  return $etag === $checkEtag;
}

function _isTimestampValid($ts, $checkTs) {
  //if (DEV_MODE) echo "compare SERVER[$ts] vs CACHE[$checkTs]<br>\n";
  return $ts <= $checkTs;
}

// check: etag, ts, res
function doWeHaveHeader($headers, $check) {
  //echo "<pre>test[", gettype($check), gettype($check['res']), htmlspecialchars(print_r($check, 1)), "]</pre>\n";
  // if our local cache is valid, return it's data
  $hasEtag = isset($headers['etag']) && isset($check['etag']) && _doWeHaveEtag($headers['etag'], $check['etag']);
  if ($hasEtag) return true;
  return isset($headers['last-modified']) && isset($check['ts']) && _isTimestampValid(strtotime($headers['last-modified']), $check['ts']);
}

?>