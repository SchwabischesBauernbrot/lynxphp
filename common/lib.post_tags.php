<?php

// load this file when needed, the entire system shouldn't need this

function tagPost_getAll() {
  global $pipelines;
  // would be nice to show what module this comes from...
  $io = array(
    'tags' => array(
      'thread' => array('description' => 'post is a new thread',),
      'reply'  => array('description' => 'post is a new reply'),
    ),
  );
  // maybe; the order of these matter, why?
  $pipelines[PIPELINE_POSTTAG_REGISTER]->execute($io);
  return $io['tags'];
}

function tagPost($boardUri, $post, $files, $privPost) {
  global $pipelines;
  $isReply = $post['threadid'] ? true : false;
  $io = array(
    'boardUri' => $boardUri,
    'p'        => $post,
    'priv'     => $privPost,
    'files'    => $files,
    'tags' => array(
      'thread' => !$isReply,
      'reply'  => $isReply,
    ),
  );
  $pipelines[PIPELINE_NEWPOST_TAG]->execute($io);
  return $io['tags'];
}

?>