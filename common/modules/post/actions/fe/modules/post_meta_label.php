<?php

$params = $getModule();

$checkable = $io['checkable'];

// finally wrap it all in a label tag
if ($io['meta'] !== '' && $checkable) {
  $io['meta'] = '    <label>' . "\n      " . $io['meta'] . '    </label>';
  if (DEV_MODE) {
    $io['meta'] = '<!-- DEV_MODE: post/actions/post_meta -->' . "\n" . $io['meta'];
  }
}


?>
