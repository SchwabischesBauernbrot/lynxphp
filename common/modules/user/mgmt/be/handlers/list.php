<?php
$params = $get();

$res = userInGroupMiddleware($request, 'admin');
if (!$res) {
  // if no session, it will already handle output...
  return;
}

global $db, $models;

$models['usergroup']['parents'] = array(
  array(
    'type'  => 'left',
    'model' => $models['group'],
    //'pluck' => array('name' => 'groupname'),
    'pluck' => array('name' => 'group_concat(name) as groupnames'),
    // need an aggregate function...
    //'pluck' => array('name' => 'name as groupnames'),
  )
);

$models['user']['children'] = array(
  array(
    'type'    => 'left',
    'model'   => $models['usergroup'],
    'pluck'   => array(),
    'groupby' => 'users.userid',
  )
);
// we need to group by userid

// FIXME: pagination

$res = $db->find($models['user'], array('order' => 'userid'));
$arr = $db->toArray($res);
//print_r($arr);
$users = pluck($arr, array(
  'userid', 'username', 'email', 'created_at', 'updated_at', 'groupnames'
));
// include owned boards, groups...
sendResponse($users);

?>
