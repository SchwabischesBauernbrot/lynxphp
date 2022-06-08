<?php

$params = $getHandler();

// validate data
//print_r($_POST);

$strings = explode("\n", $_POST['string']);
$action = $_POST['action'];

// call backend handler to add strings
$result = $pkg->useResource('add', array(
  'strings' => $strings,
  'action'  => $action,
));

if (isset($result['success'])) {
  // success
  global $BASE_HREF;
  // why is BASE_HREF empty?
  redirectTo($BASE_HREF . 'admin/strings');
} else {
  wrapContent('Something went wrong...' . print_r($result, 1));
}

?>
