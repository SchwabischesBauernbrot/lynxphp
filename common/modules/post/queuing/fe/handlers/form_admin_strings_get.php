<?php

$params = $getHandler();

// admin check...
if (!perms_inGroups(array('admin'))) {
  wrapContent('access denied<br>' . "\n");
  return;
}

$data = $pkg->useResource('postq_list');
// queue_posts, boards

$strings = array_filter($data['queue_posts'], function($item) {
  return $item['strings_match'] > 0;
});
$ids = array_map(function($i) {
  return $i['queueid'];
}, $strings);

$idStr = join(',', $ids);

$str = post_queue_display($strings);

$yesAction = $params['action'];
$tmpl = <<< EOB
  $str
  Are you sure?
  <form method="POST" action="$yesAction">
    <input type=hidden name="ids" value="$idStr">
    <input type=submit value="Yes">
  </form>
EOB;
wrapContent($tmpl);

?>
