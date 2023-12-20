<?php

$params = $getHandler();

// admin check...
if (!perms_inGroups(array('admin'))) {
  wrapContent('access denied<br>' . "\n");
  return;
}

// call backend handler to delete banner
$result = $pkg->useResource('admin_del', array(
  'queueid' => $request['params']['id'],
));
if ($result && $result['success']) {
  // success
  global $BASE_HREF;
  redirectTo($BASE_HREF . 'admin/post_queue');
} else {
  wrapContent('Something went wrong... Error: ' . print_r($result, 1));
}

?>
