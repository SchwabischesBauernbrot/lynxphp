<?php

$params = $getHandler();

//echo "<pre>", print_r($params, 1), "</pre>\n";
$p = $params['request']['params'];
$uri = $p['uri'];
$pno = $p['pno'];

// get post data
//global $packages;
//$result = $packages['post_preview']->useResource('preview', array('board' => $uri, 'id' => $pno . '.json'));
//$result = $pkg->useResource('myop', array('uri' => $uri, 'pno' => $pno . '.json'));

//echo "<pre>result", print_r($result, 1), "</pre>\n";
//$ourPost = $result['data'];
//$showPass = !$ourPost;
//if ($showPass) {
  // present form asking for password
  // could include page to remember where to return too...
  $fields = array(
    'password' => array('label' => 'Password', 'type' => 'password'),
  );
  $values = array();
  // action has uri and pno in it
  $html = generateForm($params['action'], $fields, $values, array('buttonLabel' => 'Delete Post'));
  // we neeed to access load storage
  $html .= "
  <script>
  var elem = document.querySelector('input[type=password]')
  if (elem) {
    elem.value = localStorage.getItem('postpassword')
    console.log('raw', elem.value)
  }
  </script>
  ";
  wrapContent($html, array('title' => 'OP Password?'));
/*
} else {
  //echo "Guchi<br>\n";
  $result = $pkg->useResource('delete_reply', array('uri' => $uri, 'pno' => $pno . '.json'));
  //echo "<pre>", print_r($result, 1), "</pre>\n";
  if ($result['deleted']) {
    // success
    // knowing the tno, we could redirect back to the thread list
    redirectTo('/' . $uri . '/thread/' . $result['tno'] . '.html');
  } else {
    $code = 401;
    $msg = 'Not your OP';
    if ($result['tried']) {
      $code = 500;
      $msg = 'Tried to delete but something went wrong, Please let an admin know or try again later.';
    }
    wrapContent('Error: ' . $msg, array('title' => 'Error', 'code' => $code));
  }
  // confirmation?
  // just request the delete for now
  //wrapContent('Nuke', array('title' => 'Yours?'));
}
*/
