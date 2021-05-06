<?php
$params = $get();

$res = userInGroupMiddleware($request, 'admin');
if (!$res) {
  // if no session, it will already handle output...
  return;
}

global $db, $models;

$id = $request['params']['id'];
// get current groups
$res = $db->find($models['usergroup'], array('criteria' => array(
  'userid' => $id,
)));
$inGroups = json_decode($_POST['groups'], true);
//echo "inGroups[", print_r($inGroups, 1), "]<br>\n";
$toAdd  = array();
$toKeep = array();
$toDel  = array();
while($row = $db->get_row($res)) {
  if (!in_array($row['groupid'], $inGroups)) {
    $toDel[]  = $row['usergroupid'];
  } else {
    $toKeep[] = $row['groupid'];
  }
}
$db->free($res);
if (count($toDel)) {
  $db->delete($models['usergroup'], array('criteria'=>array(array('usergroupid', 'IN', $toDel))));
}

foreach($inGroups as $gid) {
  if (!in_array($gid, $toKeep)) {
    $toAdd[] = array('userid'=>$id, 'groupid'=>$gid);
  }
}
if (count($toAdd)) {
  $db->insert($models['usergroup'], $toAdd);
}

// include owned boards, groups...
sendResponse(array(
  'success' => true,
  'toAdd'  => $toAdd,
  'toKeep' => $toKeep,
  'toDel'  => $toDel,
));

?>
