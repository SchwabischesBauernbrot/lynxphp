<?php

$params = $getHandler();

wrapContent('Please wait...');

$userid = $_POST['userid'];
$groups = getOptionalPostField('groups');

$res = $pkg->useResource('updateusergroups', array('id' => $userid, 'groups' => $groups));

//echo '<pre>res[', print_r($res, 1), "]</pre>\n";
//return;

if ($res['success']) {
  // maybe a js alert?
  echo "Success<br>\n";
  redirectTo('/admin/users');
} else {
  wrapContent('Something went wrong...' . print_r($res, 1));
}

?>
