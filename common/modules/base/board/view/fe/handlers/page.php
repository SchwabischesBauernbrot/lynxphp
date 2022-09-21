<?php

include '../frontend_lib/handlers/boards.php'; // request2QueryThread

$boardUri = $request['params']['uri'];
$page = $request['params']['page'] ? $request['params']['page'] : 1;
$q = request2QueryThread($request);
getBoardThreadListing($q, $boardUri, $page);

?>