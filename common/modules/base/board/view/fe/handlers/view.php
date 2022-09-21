<?php

include '../frontend_lib/handlers/boards.php'; // request2QueryThread

$boardUri = $request['params']['uri'];
// transform req => q
$q = request2QueryThread($request);
//echo "<pre>", print_r($request['portals'], 1), "</pre>\n";
getBoardThreadListing($q, $boardUri);

?>