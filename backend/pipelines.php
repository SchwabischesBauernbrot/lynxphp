<?php

// pipelines
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
definePipeline('PIPELINE_BOARD_DATA', 'boardData');
definePipeline('PIPELINE_POST_DATA',  'postData');
definePipeline('PIPELINE_USER_DATA',  'userData');
definePipeline('PIPELINE_POST', 'post');
definePipeline('PIPELINE_FILE', 'file');
?>