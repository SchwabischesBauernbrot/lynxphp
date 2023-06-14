<?php

// overboard/be
$params = $getModule();

global $db, $models;

// does saging affect this?

// is this a new thread?
$row = array('uri' => $io['boardUri'], 'thread_id' => $io['threadNum']);
$insert = false;
if ($io['threadNum'] === $io['id']) {
  // new thread
  $insert = true;
} else {
  // bump the updated_at
  $opts = array(
    'criteria' => $row
  );
  $cnt = $db->count($models['overboard_thread'], $opts);
  if ($cnt) {
    $db->update($models['overboard_thread'], array(), $opts);
  } else {
    $insert = true;
  }
}
if ($insert) {
  $db->insert($models['overboard_thread'], array($row));
}

?>