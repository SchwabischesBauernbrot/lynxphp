<?php

// FIXME: we need access to package
$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;
$templates = moduleLoadTemplates('banner_detail', __DIR__);
$tmpl = $templates['header'];

// wrap form
$tmpl = '<form method="POST" action="' . $boardUri . '/settings/banners/add" enctype="multipart/form-data">' . $tmpl . '
  <input type=submit>
</form>';
// pop up fields...
$tmpl = str_replace('{{image}}', '<input type=file name=image>', $tmpl);

wrapContent($tmpl);

?>
