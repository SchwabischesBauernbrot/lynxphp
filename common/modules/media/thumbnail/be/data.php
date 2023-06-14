<?php
return array(
  // package one
  array(
    'name' => 'post_files',
    'version' => 1,
    'modules' => array(
      array(
        'pipeline' => PIPELINE_WQ_FILE_ADD,
        'module'   => 'thumbnail',
      ),
    ),
  ),
);
?>