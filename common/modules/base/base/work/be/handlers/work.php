<?php
$params = $get();

global $workqueue;

// do one unit of work...
// loop until specified time?
// probably should move into it's own route so it's more controlled
$workqueue->getWork();

?>