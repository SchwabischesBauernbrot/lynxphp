<?php
$params = $get();

$board = getQueryField('boardUri');

//echo "board[$board]<br>\n";

global $db;

if (!$board) {
  $lynxReports = getOpenGlobalReports();
  return sendResponse2(array(
    'reports' => $lynxReports,
  ));
}

$lynxReports = getOpenReports($board);
// weird CF was agrgressively caching this...
sendResponse2(array(
  'reports' => $lynxReports,
));
?>