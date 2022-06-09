<?php
$params = $get();

post_queue_delete((int)$params['params']['queueid']);

sendResponse(array(
  'success' => true
));

?>
