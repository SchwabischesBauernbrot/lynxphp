<?php

$params = $getModule();

// do we have a post delete action

// seems to be before post/password
//echo "<pre>", print_r($io, 1), "</pre>\n";
// boardUi, p[postid, deleted, sub, replies, del_replies, no, threadid], actions [all, user, bo, global, admin], boardSettings, postCount

$wtd = null; // whoel thread deleted
if (isset($io['p']['del_replies'])) {
  // on deleted thread listing
  $wtd = $io['p']['deleted'] && $io['p']['replies'] === $io['p']['del_replies'];
}

// we're a plugin providing an undelete/scrub
// post_queue don't emulated deleted, this is just safer
if (!empty($io['p']['deleted'])) {
  // do we need to decode thread/reply?
  $io['actions']['bo'][] = array(
    // '/:uri/posts/:pno/delete',
    'link' => $io['boardUri'] . '/posts/' .  $io['p']['no'] . '/undelete.html',
    'label' => 'Undelete post',
    'includeWhere' => true,
  );
}

$io['actions']['bo'][] = array(
  // '/:uri/posts/:pno/delete',
  'link' => $io['boardUri'] . '/posts/' .  $io['p']['no'] . '/scrub.html',
  'label' => 'Scrub post',
  'includeWhere' => true,
);

if ($wtd) {
  // these actions shouldn't be on the deleted thread listing tbh
  // want them to actually view the thread to make decisions

  // inject undelete thread action
  $io['actions']['bo'][] = array(
    // '/:uri/posts/:pno/delete',
    'link' => $io['boardUri'] . '/threads/' .  $io['p']['no'] . '/undelete.html',
    'label' => 'Undelete thread',
    'includeWhere' => true,
  );
  // should we add a scrub version too for BOs?
  $io['actions']['bo'][] = array(
    // '/:uri/posts/:pno/delete',
    'link' => $io['boardUri'] . '/threads/' .  $io['p']['no'] . '/scrub.html',
    'label' => 'Scrub thread',
    'includeWhere' => true,
  );
}

