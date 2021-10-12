<?php

// backend

$params = $get();

sendResponse(array(
  'success' => 'ok',
  'params' => print_r($params, 1),
));

?>