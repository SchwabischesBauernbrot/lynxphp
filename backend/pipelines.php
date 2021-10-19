<?php

// pipelines

// separated out, so frontend can include if needed for dealing with modules...

// - boardDB to API
// - thread to API
// - post to API
// - user to API
// - create thread
// - create reply
// - upload file
// - get ip
// - post var processing

// should this be wrapped in a function?
// depends on how much memory/cputime this takes...
$backEndPipelines = array(
  'PIPELINE_BOARD_DATA',
  'PIPELINE_POST_DATA',
  'PIPELINE_USER_DATA',
  'PIPELINE_POST',
  'PIPELINE_FILE',
  'PIPELINE_REPLY_ALLOWED',
);
// we don't need to necessarily call this here
definePipelines($backEndPipelines);

?>