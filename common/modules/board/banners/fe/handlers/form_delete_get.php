<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;
// maybe show banner...
$yesAction = '/' . $boardUri . '/settings/banners/' . $request['params']['id'] . '/delete.php';
$tmpl = <<< EOB
  Are you sure?
  <form method="POST" action="$yesAction">
    <input type=submit value="Yes">
  </form>
EOB;
wrapContent($tmpl);

?>
