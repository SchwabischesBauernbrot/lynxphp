<?php
$params = $get();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

sendResponse(getBoardSettings($boardUri));
?>