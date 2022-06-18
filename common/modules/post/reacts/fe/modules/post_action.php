<?php

if (!function_exists('doSignals')) {
  function doSignals($signals, $your, $threadId, &$io) {
    foreach($signals as $s) {
      if ($your) {
        if ($s === $your) {
          $io['actions']['all'][] = array(
            'label' => 'Remove React: ' . $s,
            'link' => '/' . $io['boardUri'] . '/thread/' . $threadId . '/' . $io['p']['no'] . '/react/delete',
          );
        } else  {
          $io['actions']['all'][] = array(
            'label' => 'Change React: ' . $s,
            'link' => '/' . $io['boardUri'] . '/thread/' . $threadId . '/' . $io['p']['no'] . '/react/' . $s,
          );
        }
      } else {
        $io['actions']['all'][] = array(
          'label' => 'Add React: ' . $s,
          'link' => '/' . $io['boardUri'] . '/thread/' . $threadId . '/' . $io['p']['no'] . '/react/' . $s,
        );
      }
    }
  }
}

// FIXME: we need access to package
$params = $getModule();
if (!empty($io['boardSettings']['react_mode'])) {
  $threadId = $io['p']['threadid'];
  if (empty($threadId) && $io['p']['no']) {
    $threadId = $io['p']['no'];
  }
  $your = false;
  if (!empty($io['p']['exposedFields']['your_react'])) {
    $your = $io['p']['exposedFields']['your_react'];
  }
  if ($io['boardSettings']['react_mode'] === 'signal3') {
    $signals = $shared[$io['boardSettings']['react_mode']];
    doSignals($signals, $your, $threadId, $io);
  } else
  if ($io['boardSettings']['react_mode'] === 'signal7') {
    //echo "<pre>io", print_r($io['p'], 1), "</pre>\n";
    $signals = $shared[$io['boardSettings']['react_mode']];
    //echo "<pre>io", print_r($io['actions'], 1), "</pre>\n";
    doSignals($signals, $your, $threadId, $io);
  } else
  if ($io['boardSettings']['react_mode'] === 'custom' || $io['boardSettings']['react_mode'] === 'all') {
    if ($your === false) {
      $io['actions']['all'][] = array(
        'label' => 'Add React',
        'link' => '/' . $io['boardUri'] . '/thread/' . $threadId . '/' . $io['p']['no'] . '/react',
      );
    } else {
      $io['actions']['all'][] = array(
        'label' => 'Change React',
        'link' => '/' . $io['boardUri'] . '/thread/' . $threadId . '/' . $io['p']['no'] . '/react',
      );
      $io['actions']['all'][] = array(
        'label' => 'Remove React',
        'link' => '/' . $io['boardUri'] . '/thread/' . $threadId . '/' . $io['p']['no'] . '/react/delete',
      );
    }
  }
}

?>
