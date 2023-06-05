<?php

// FIXME: we need access to package
$params = $getModule();

// io is navItems

//print_r($params);
global $portalData;
//echo "<pre>", print_r($portalData, 1), "</pre>\n";
$openReportCount = false;
if (isset($portalData['boardSettings']['openReportCount'])) {
  $openReportCount = $portalData['boardSettings']['openReportCount'];
}

// costly but polish
if ($openReportCount === false) {
  $result = $pkg->useResource('open_reports', array('boardUri' => $io['boardUri']));
  if (!isset($result['reports']) || !is_array($result['reports'])) $result['reports'] = array();
  $openReportCount = count($result['reports']);
}

$io['navItems']['reports (' . $openReportCount . ')'] = '{{uri}}/settings/reports';

?>
