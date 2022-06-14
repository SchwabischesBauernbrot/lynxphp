<?php

$params = $getHandler();

// admin check...
if (!perms_inGroups(array('admin'))) {
  wrapContent('access denied<br>' . "\n");
  return;
}

$ids = $_POST['ids'];
$ids = explode(',', $ids);

// call backend handler to delete banner
$result = $pkg->useResource('admin_dels', array('ids' => $ids));
if ($result && $result['success']) {
  // success
  global $BASE_HREF;
  redirectTo($BASE_HREF . 'admin/queue');
} else {
  wrapContent('Something went wrong... Error: ' . print_r($result, 1));
}

?>
