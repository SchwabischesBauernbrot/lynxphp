<?php

// FIXME: we need access to package
$params = $getModule();

if (!empty($io['p']['exposedFields']['reacts'])) {
  //print_r($io['p']['exposedFields']['reacts']);
  foreach($io['p']['exposedFields']['reacts'] as $r => $c) {
    $io['html'] .= '<span class="react" title="count: ' . $c. '">' . $r . '</span> ';
  }
}

?>
