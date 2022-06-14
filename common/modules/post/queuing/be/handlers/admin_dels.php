<?php
$params = $get();

$ids = explode(',', $_GET['ids']);

post_queue_delete($ids);

sendResponse(array(
  'success' => true,
));

?>
