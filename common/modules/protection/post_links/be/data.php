<?php

// post_links/be

return array(
  array(
    'models' => array(
      /*
      array(
        'name'   => 'tor_ip',
        'fields' => array(
          'ip'       => array('type' => 'str'),
        ),
      ),
      */
    ),
    'modules' => array(
      // register tag
      array('pipeline' => PIPELINE_POSTTAG_REGISTER, 'module' => 'register'),
      // determine if post needs a tag
      array('pipeline' => PIPELINE_NEWPOST_TAG, 'module' => 'newpost_tag'),
      // this optional pipeline, because it's a dependency
      // it'll be included/defined
      // (unless they disable the module...)
      array('pipeline' => PIPELINE_BE_ADMIN_QUEUE_DATA, 'module' => 'admin_queue')
    ),
  ),
);

?>
