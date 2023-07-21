<?php
$params = $get();

global $db, $models, $pipelines;
global $workqueue;

$count = $workqueue->getWorkCount();
$analyics = $workqueue->getAnalytics();

// send values
sendResponse2(array(
  'analyics' => $analyics,
  'count' => $count,
  //'boards' => $boardsThatQ,
));

?>