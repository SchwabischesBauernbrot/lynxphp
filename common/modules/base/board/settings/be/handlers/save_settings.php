<?php

$params = $get();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

global $db, $models;
$row = getBoardRaw($boardUri);
//if (!is_array($settings['json'])) $settings['json'] = array();

//echo "<pre>GET[", print_r($_GET, 1), "]</pre>\n";
//echo "<pre>POST[", print_r($_POST, 1), "]</pre>\n";
//echo "<pre>shared[", print_r($shared, 1), "]</pre>\n";
//echo "<pre>FILES[", print_r($_FILES, 1), "]</pre>\n";

//echo "settings[", gettype($settings), "][", print_r($settings, 1), "]<br>\n";
//echo "json[", gettype($settings['json']), "][", print_r($settings['json'], 1), "]<br>\n";
$dbFields = array('uri', 'title', 'description');
$row['json'] = json_decode($row['json'], true);
//echo "<pre>database settings[", gettype($row['json']['settings']), "][", print_r($row['json']['settings'], 1), "]<br>\n";
//echo "input[", print_r($_POST, 1), "]</pre>\n";

$urow = array();
foreach($_POST as $k => $v) {
  //echo "set [$k=$v]<br>\n";
  if (in_array($k, $dbFields)) {
    $urow[$k] = $v;
  } else {
    $row['json']['settings'][substr($k, 9)] = $v;
  }
}

// checkbox states are always changed
foreach($shared['fields'] as $f => $t) {
  //echo "type[", print_r($t, 1), "]<br>\n";
  if ($t['type'] === 'checkbox') {
    $sf = substr($f, 9); // just assuming no checkbox dbFields
    $row['json']['settings'][$sf] = getOptionalPostField($f);
  }
}
//echo "<pre>merge[", print_r($row['json']['settings'], 1), "]</pre>\n";

// FIXME: move all posts/files if uri changes....

//echo "row[", gettype($row), "][", print_r($row, 1), "]<br>\n";
$ok1 = saveBoardSettings($boardUri, $row['json']['settings']);
$ok2 = $db->update($models['board'], $urow, array('criteria'=>array('uri'=>$boardUri)));

sendResponse(array(
  'success' => ($ok1 && $ok2) ? 'true' : 'false',
  //'settings' => $settings,
));

?>
