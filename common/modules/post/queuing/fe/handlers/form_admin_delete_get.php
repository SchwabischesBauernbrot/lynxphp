<?php

$params = $getHandler();

// admin check...
if (!perms_inGroups(array('admin'))) {
  wrapContent('access denied<br>' . "\n");
  return;
}

// maybe show banner...
$yesAction = '/admin/queue/' . $request['params']['id'] . '/delete.php';
$tmpl = <<< EOB
  Are you sure?
  <form method="POST" action="$yesAction">
    <input type=submit value="Yes">
  </form>
EOB;
wrapContent($tmpl);

?>
