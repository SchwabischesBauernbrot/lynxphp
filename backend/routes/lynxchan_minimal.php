<?php

//
// Lynxchan compatible API for lynxphp
//

return array(
  'lynxchan_minimal' => array(
    'dir'  => 'lynx',
    'routes' => array(
      'createBoard' => array(
        'method' => 'POST',
        'route'  => '/createBoard',
        'file'   => 'create_board',
      ),
      'files' => array(
        'method' => 'POST',
        'route'  => '/files',
        'file'   => 'files',
      ),
      'newThread' => array(
        'method' => 'POST',
        'route'  => '/newThread',
        'file'   => 'new_thread',
      ),
      'replyThread' => array(
        'method' => 'POST',
        'route'  => '/replyThread',
        'file'   => 'reply_thread',
      ),
      'account' => array(
        'route'  => '/account',
        'file'   => 'account',
      ),
    ),
  ),
);
