<?php
return array(
  'name' => 'post_reacts',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'doubleplus/:boardUri/reacts',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'board_add',
      'params' => array(
        'method' => 'POST',
        'endpoint' => 'doubleplus/:boardUri/reacts',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri', 'name'),
        'params' => 'postdata',
      ),
    ),
    array(
      'name' => 'board_del',
      'params' => array(
        'endpoint' => 'doubleplus/:boardUri/reacts/:reactid',
        'method' => 'DELETE',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri', 'reactid'),
        //'params' => 'postdata',
        //'params' => array (
          //'querystring' => 'boardUri',
          //'formData' => 'bannerId',
        //),
      ),
    ),
    array(
      'name' => 'add_react',
      'params' => array(
        'method' => 'POST',
        'endpoint' => 'doubleplus/:boardUri/posts/:threadId/:postId/reacts/:react',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri', 'threadId', 'postId', 'react'),
        'params' => 'postdata',
      ),
    ),
    array(
      'name' => 'del_react',
      'params' => array(
        'method' => 'DELETE',
        'endpoint' => 'doubleplus/:boardUri/posts/:threadId/:postId/reacts',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri', 'threadId', 'postId'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>
