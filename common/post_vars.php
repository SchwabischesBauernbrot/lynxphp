<?php

function hasPostVars($fields) {
  foreach($fields as $field) {
    if (empty($_POST[$field])) {
      wrapContent('Field "' . $field . '" required');
      return false;
    }
  }
  return true;
}

function getServerField($field, $default = '') {
  return empty($_SERVER[$field]) ? $default : $_SERVER[$field];
}

function getOptionalPostField($field) {
  return empty($_POST[$field]) ? '' : $_POST[$field];
}

function getip() {
  $ip = getServerField('REMOTE_ADDR', '127.0.0.1');
  $ip = getServerField('HTTP_X_FORWARDED_FOR', $ip); // cloudflare support
  return $ip;
}

?>
