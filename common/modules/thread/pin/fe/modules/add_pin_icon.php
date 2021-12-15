<?php

$params = $getModule();


if (!empty($io['p']['sticky'])) {
  $io['icons'][] = array(
    'icon'  => 'sticky',
    'title' => 'Thread is pinned',
  );
}

?>
