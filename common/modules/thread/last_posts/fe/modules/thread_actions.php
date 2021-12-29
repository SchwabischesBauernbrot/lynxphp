<?php

$params = $getModule();

// how many posts do we have?

//echo "<pre>[", print_r($io, 1), "]</pre>\n";

// or maybe if === 5 ...
if (isset($io['postCount']) && $io['postCount'] > 50) {
  $io['actions']['all'][] = array(
    'link'  => $io['boardUri'] . '/thread/' . $io['p']['no'] . '/last50.html',
    'label' => 'last 50 posts',
  );
}