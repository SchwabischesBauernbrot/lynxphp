<?php

$params = $getModule();

//echo "<pre>", print_r($io, 1), "</pre>\n";

if (!empty($io['boardSettings']['hyperfy'])) {
  //$hfuri = $io['boardSettings']['hyperfy_uri'];
  $label = empty($io['boardSettings']['hyperfy_label']) ? 'Multiplayer' : $io['boardSettings']['hyperfy_label'];
  $io['navItems'][] = array('label' => $label, 'destinations' => $io['boardUri'] . '/hyperfy.html');
}

?>
