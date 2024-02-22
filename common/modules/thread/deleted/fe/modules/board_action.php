<?php

$params = $getModule();

//print_r($io);

$boardUri = $io['boardUri'];

$io['actions']['bo'][] = array(
  'link' => '/' . $boardUri . '/threads/deleted', 'label' => 'Deleted Threads',
  'includeWhere' => true,
);

/*
// FIXME: promote to all later
// add "add to favorites" link
// FIXME: are we already in their favorites?
// ask BE

// FIXME: an EP that gets this included in the normal board data...
$res = $pkg->useResource('is', array('boardUri' => $boardUri));
//echo "isRes[", print_r($res, 1), "]<br>\n";
if ($res && $res['favorite']) {
  $io['actions']['user'][] = array(
    'link' => '/' . $boardUri . '/unfavorite?', 'label' => 'Remove favorites',
    'includeWhere' => true,
  );
} else {
  $io['actions']['user'][] = array(
    'link' => '/' . $boardUri . '/favorite?', 'label' => 'Add to favorites',
    'includeWhere' => true,
  );
}
*/

?>
