<?php

return array(
  array(
    'models' => array(
      array(
        'name'   => 'post_string',
        'fields' => array(
          'string'    => array('type' => 'str'),
          // for specific boards
          'uri' => array('type' => 'int'),
          'action' => array('type' => 'int'),
        ),
      ),
    ),
    'modules' => array(
      // handle queuing process based on strings setting
      // FIXME: there's an ordering issue here...
      array('pipeline' => PIPELINE_NEWPOST_PROCESS, 'module' => 'newpost_process'),
      // this optional pipeline, because it's a dependency
      // it'll be included/defined
      // (unless they disable the module...)
      array('pipeline' => PIPELINE_BE_ADMIN_QUEUE_DATA, 'module' => 'admin_queue')
    ),
  ),
);


?>
