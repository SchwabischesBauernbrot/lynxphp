<?php

$params = $getModule();

// io has portalExts
$io['portalExts'][] = array(
  'header' => '<form action="/forms/board/' . $io['boardUri'] . '/actions" method="POST" enctype="application/x-www-form-urlencoded">
  <input type="hidden" name="thread" value="' . $io['threadNum'] . '">
  <input type="hidden" name="page" value="' . $io['pageNum'] . '">',
  'post' => 'every',
  'footer' => renderPostActions($io['boardUri']) . '</form>',
);

?>
