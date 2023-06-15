<?php

$params = $getHandler();

$boardUri  = $params['request']['params']['uri'];
// do we need do this on the frontend?
// could reduce load on backend but could double it if it's valid...
if (!perms_isBO($boardUri)) {
  return wrapContent('Must be a BO to lock this thread');
}

$threadNum = $params['request']['params']['threadNum'];

$result = $pkg->useResource('cyclic', array(
  'uri' => $boardUri, 'threadNum' => $threadNum
));


if (isset($result['success']) && $result['success'] === 'ok') {
  action_redirectToWhere();
  return;
}
wrapContent('<pre>' . print_r($result, 1) . "</pre>\n");


?>
