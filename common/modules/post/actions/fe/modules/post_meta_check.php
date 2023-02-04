<?php

$params = $getModule();

$checkable = $io['checkable'];
if ($checkable) {
  // this is just the checkbox at this point
  $templates = moduleLoadTemplates('post_meta', __DIR__);
  // we include all three because we can't be sure where the post is
  // in or outside a board context (outside example: overboard/multiboard)
  $html = replace_tags($templates['header'], array(
    'uri'       => $io['uri'],
    'threadNum' => $io['threadNum'],
    'no'        => $io['p']['no'],
  ));
  // insert html at the beginning
  $io['meta'] = $html . $io['meta'];
}

// probably should be a separate module
// finally wrap it all in a label tag
/*
if ($io['meta'] !== '' && $checkable) {
  $io['meta'] = '    <label>' . "\n      " . $io['meta'] . '    </label>';
  if (DEV_MODE) {
    $io['meta'] = '<!-- DEV_MODE: post/actions/post_meta -->' . "\n" . $io['meta'];
  }
}
*/

?>
