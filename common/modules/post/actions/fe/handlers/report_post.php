<?php

$params = $getHandler();

$boardUri = $params['request']['params']['uri'];
$postNo = $params['request']['params']['id'];

// validate that post number isn't zero
if (!$postNo) {
  // 400?
  wrapContent('A post number is required\n');
  return;
}
// verify post exists? nah

// FIXME: move to a shared area

// FIXME: maybe a captcha

$templates = loadTemplates('mixins/post_actions');
$tmpl = 'Reporting Post #' . $postNo . ' on >>>/' . $boardUri . '/<br>
<form action="/forms/board/' . $boardUri . '/actions" method="POST">
  <!-- page number -->
  <input type=hidden name="checkedposts[]" value="' . $postNo . '">
  <input type=hidden name="action" value="report">
' . $templates['loop1'] . '
  <input type=submit value="report post">
</form>';
wrapContent($tmpl);

?>
