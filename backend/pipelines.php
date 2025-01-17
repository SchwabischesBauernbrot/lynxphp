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
  'PIPELINE_BOARD_QUERY_MODEL',
  'PIPELINE_BOARD_DATA',
  'PIPELINE_BOARD_CATALOG_DATA',
  'PIPELINE_PORTALS_DATA',
  'PIPELINE_POST_DATA',
  'PIPELINE_BE_POST_EXPOSE_DATA_FIELD',
  'PIPELINE_BE_POST_FILTER_DATA_FIELD',
  'PIPELINE_THREAD_DATA', // threadDBtoAPI (affects board and thread page)
  'PIPELINE_REPLY_DATA',
  'PIPELINE_USER_DATA',
  'PIPELINE_POST',
  'PIPELINE_REPLY_ALLOWED',
  'PIPELINE_POST_ADD',
  'PIPELINE_POSTTAG_REGISTER',
  'PIPELINE_BE_USER_PERMITTED',
  //'PIPELINE_DELETE_POST', // not needed/implemented yet but not a bad idea
  'PIPELINE_THREAD_PRE_DELETE',
  'PIPELINE_THREAD_POST_DELETE',
  //'PIPELINE_DELETE_REPLY', // not needed/implemented yet but not a bad idea
  'PIPELINE_NEWPOST_TAG',
  'PIPELINE_NEWPOST_PROCESS',
  'PIPELINE_BE_FILE_FIX_MIME',
  'PIPELINE_BE_FILE_FIX_FILEDATA',
  'PIPELINE_USER_LOGIN',
  'PIPELINE_ACCOUNT_DATA',
  'PIPELINE_SESSION_EXPIRATION',
  // Non-target work queue pipelines
  'PIPELINE_WQ_POST_ADD',
  // really used mor like FILE_EXISTS...
  'PIPELINE_WQ_FILE_ADD',
  'PIPELINE_WQ_REQUEST_DELETE_THREAD',
);
// renaming a pipeline will cause everything in queue to not execute

// we don't need to necessarily call this here
definePipelines($backEndPipelines);

?>