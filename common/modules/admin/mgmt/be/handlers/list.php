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
    // group_concat(name)
    'pluck' => array('name' => $db->groupAgg('name').' as groupnames'),
    // need an aggregate function...
    //'pluck' => array('name' => 'name as groupnames'),
  )
);

$models['user']['children'] = array(
  array(
    'type'    => 'left',
    'model'   => $models['usergroup'],
    'pluck'   => array(),
    'groupby' => array('users.userid'),
  )
);
// we need to group by userid

// FIXME: pagination
$criteria = array();
$pk = getOptionalPostField('publickey');
if ($pk) {
  $criteria['publickey'] = $pk;
}
$em = getOptionalPostField('email');
if ($em) {
  $criteria['email'] = hash('sha512', BACKEND_KEY . $em . BACKEND_KEY);
}
$res = $db->find($models['user'], array('criteria' => $criteria, 'order' => 'userid'));
$arr = $db->toArray($res);
//print_r($arr);
$users = pluck($arr, array(
  'userid', 'publickey', 'created_at', 'updated_at', 'groupnames'
));
// include owned boards, groups...
sendResponse($users);

?>