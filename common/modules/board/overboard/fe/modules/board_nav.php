<?php

$params = $getModule();

// $params['options']

// clear nav items
// no index, catalog, logs, banners
if ($io['boardUri'] === 'overboard') {
  $io['navItems'] = array();
}