<?php

$params = $getModule();

$io['addFields'][] = array('label' => 'Links?', 'field' => 'links_has');
$io['addFields'][] = array('label' => 'Links', 'field' => 'links_found', 'type' => 'compact_informative');

?>
