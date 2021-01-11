<?php

// FIXME: we need access to package
$params = $getModule();

// io is navItems

//print_r($params);

// costly but polish
$result = $pkg->useResource('open_reports', array('boardUri' => $io['boardUri']));

if (!is_array($result['reports'])) $result['reports'] = array();

$io['navItems']['reports ('.count($result['reports']).')'] = '{{uri}}/settings/reports';

?>
