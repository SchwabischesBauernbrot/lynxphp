<?php

// FIXME: we need access to package
$params = $getModule();

//echo "boardSettingsNav", print_r($io['boardSettings'], 1), "<br>\n";

if (!empty($io['boardSettings']['react_mode']) && ($io['boardSettings']['react_mode'] === 'custom'
    || $io['boardSettings']['react_mode'] === 'all')) {
  // io is navItems
  $io['navItems']['Custom reacts'] = '{{uri}}/settings/reacts.html';
}

?>
