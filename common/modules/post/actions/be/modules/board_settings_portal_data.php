<?php

$module = $getModule();

$reports = getOpenReports($io['boardUri']);
$io['out']['boardSettings']['openReportCount'] = count($reports);
?>
