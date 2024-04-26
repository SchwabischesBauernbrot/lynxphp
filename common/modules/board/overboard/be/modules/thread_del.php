<?php

// overboard/be
$params = $getModule();

global $db, $models;

// when a thread is scrub'd
// take this thread off the overboard
$res = $db->delete($models['overboard_thread'], array('criteria' => array(
    'uri' => $io['boardUri'], 'thread_id' => $io['threadNum']
)));
