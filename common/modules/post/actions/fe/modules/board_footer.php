<?php

// FIXME: we need access to package
$params = $getModule();

// io has tags, boardUri and beforeFormEndHtml
//$io['tags']['postactions'] = renderPostActions($io['boardUri']);

$io['beforeFormEndHtml'] = renderPostActions($io['boardUri']);
?>
