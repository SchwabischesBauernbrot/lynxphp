<?php
// for getting data from apache/nginx
ldr_require('../common/lib.units.php');

function isNginx() {
  return stripos(getServerField('SERVER_SOFTWARE'), 'nginx') !== false;
}

// should only use this
function getLowercaseHeaders() {
  $headers = array();
  if (isNginx()) {
    foreach($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
      }
    }
    return $headers;
  }
  // apache
  $unnormalizedHeaders = getallheaders();
  foreach($unnormalizedHeaders as $k=>$v) {
    // if-modified-since (was If-modified-since)
    $nk = strtolower($k);
    $headers[$nk] = $v;
    //header('X-Debug-checkCacheHeaders-browser-header-' . $k . ': ' . $k. ' => ' . $nk);
    //$headers[$k] = $v;
  }
  return $headers;
}

function ensureQuerystring() {
  // work around nginx weirdness with PHP and querystrings
  if (isNginx() && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
    $parts = explode('?', $_SERVER['REQUEST_URI']);
    $querystring = $parts[1];
    $chunks = explode('&', $querystring);
    $qs = array();
    foreach($chunks as $c) {
      if (!$c) continue; // skip empties
      //if (strpos('=', $c) !== false) {
      list($k, $v) = explode('=', $c);
      $qs[$k] = $v;
      if (empty($_GET[$k])) {
        $_GET[$k] = $v;
        // could stomp POST values...
        $_REQUEST[$k] = $v;
      }
      //}
    }
    //print_r($qs);
  }
}

function isPostTooBig($req_method) {
  // upload_max_filesize breakage will have _POST set...
  $max_length = min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
  // nginx always sets CONTENT_LENGTH, apache only passes when browser sets it
  // router ignore _POST _FILES count
  if (!count($_POST) && !count($_FILES) && $req_method === 'POST') {
    //echo "GET", print_r($_GET, 1), "<br>\n";
    //echo "POST", print_r($_POST, 1), "<br>\n";
    //echo "SERVER", print_r($_SERVER, 1), "<br>\n";
    if ($_SERVER['CONTENT_LENGTH'] > $max_length) {
      return true;
    }
  }
  // echo "max_length[", number_format($max_length), "] vs content_length[", number_format($_SERVER['CONTENT_LENGTH']), "]<br>\n";
  return false;
}

function getCookie($field, $default = '') {
  return empty($_COOKIE[$field]) ? $default : $_COOKIE[$field];
}

function getServerField($field, $default = '') {
  return empty($_SERVER[$field]) ? $default : $_SERVER[$field];
}

// if '', '' is passed, so no issue.
// FIXME: should be getOptionalGetField so this are a pair
// or getOptionalPostField => getPostField
function getQueryField($field) {
  return empty($_GET[$field]) ? '' : $_GET[$field];
}

// is it optional if I don't want the PHP warning?
function getOptionalPostField($field) {
  return empty($_POST[$field]) ? '' : $_POST[$field];
}

function getip() {
  $ip = getServerField('REMOTE_ADDR', '127.0.0.1');
  // FIXME: don't trust HTTP_X_FORWARDED_FOR from any one...
  // cloudflare support and frontend will tuck it here
  $ip = getServerField('HTTP_X_FORWARDED_FOR', $ip);
  if (strpos($ip, ',') !== false) {
    $parts = explode(',', $ip);
    // first is supposed to be real ip
    $ip = array_shift($parts);
  }
  return $ip;
}

function hasPostVars($fields) {
  foreach($fields as $field) {
    if (empty($_POST[$field])) {
      wrapContent('Detected POST field "' . $field . '" is missing; ' . join(',', $fields) . ' are required');
      return false;
    }
  }
  return true;
}

function hasGetVars($fields) {
  foreach($fields as $field) {
    if (empty($_GET[$field])) {
      wrapContent('Detected GET field "' . $field . '" is missing; ' . join(',', $fields) . ' are required');
      return false;
    }
  }
  return true;
}

$phpFileUploadErrors = array(
    0 => 'There is no error, the file uploaded with success',
    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
    3 => 'The uploaded file was only partially uploaded',
    4 => 'No file was uploaded',
    6 => 'Missing a temporary folder',
    7 => 'Failed to write file to disk.',
    8 => 'A PHP extension stopped the file upload.',
);

// handle non-4 errors & reporting, otherwise collect data/normalize
function fileParseGlobal($f, $errorCode, $row) {
  global $phpFileUploadErrors;
  // handle error
  if (!$f['tmp_name']) {
    if (!empty($errorCode)) {
      // decode error
      return array(
        'error' => $phpFileUploadErrors[$errorCode],
        'debug' => $row,
      );
    } else {
      // we don't have a path (and we should?)
      return array(
        'error' => 'empty file',
        'debug' => $row,
      );
    }
  }
  // warnings?
  return array(
    'tmp_name' => $f['tmp_name'],
    'type' => $f['type'],
    'name' => $f['name'],
  );
}

function processFilesVar($filter_fields = false) {
  $fields = $filter_fields;
  if ($fields === false) {
    // just auto-detect them
    $fields = array_keys($_FILES);
  }
  // normalized fields as an array
  if (!is_array($fields)) $fields = array($filter_fields);

  $files = array();
  if (isset($_FILES)) {
    // this could be a less crazy data structure...
    //print_r($_FILES);

    // for every field we're interested in
    foreach($fields as $field) {
      $files[$field] = array();
      // each field could have multiple file support...
      if (is_array($_FILES[$field]['tmp_name'])) {
        foreach($_FILES[$field]['tmp_name'] as $i=>$path) {
          $ec = $_FILES[$field]['error'][$i];
          if ($ec !== 4) {
            $files[$field][] = fileParseGlobal(array(
              'tmp_name' => $path,
              'type' => $_FILES[$field]['type'][$i],
              'name' => $_FILES[$field]['name'][$i],
            ), $ec, $_FILES[$field]);
          }
        }
      } else {
        $ec = $_FILES[$field]['error'];
        if ($ec !== 4) {
          $files[$field][] = fileParseGlobal($_FILES[$field], $ec, $_FILES[$field]);
        }
      }
    }
  }
  return $files;
}

?>