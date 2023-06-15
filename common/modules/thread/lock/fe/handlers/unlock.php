<?php

$params = $getHandler();

$boardUri  = $params['request']['params']['uri'];
if (!perms_isBO($boardUri)) {
  return wrapContent('Must be a BO to unlock this thread');
}

$threadNum = $params['request']['params']['threadNum'];

$result = $pkg->useResource('unlock', array(
  'uri' => $boardUri, 'threadNum' => $threadNum
));


if (isset($result['success']) && $result['success'] === 'ok') {
  action_redirectToWhere();
  return;
}
wrapContent('<pre>' . print_r($result, 1) . "</pre>\n");

?>
