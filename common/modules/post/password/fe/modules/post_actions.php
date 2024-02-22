<?php

$params = $getModule();

//echo "<pre>", print_r($io, 1), "</pre>\n";
$settings = $io['boardSettings'];
//$settings = getter_getBoardSettings($io['boardUri']);
//echo "<pre>board settings:", print_r($settings, 1), "</pre>\n";
if (empty($settings['delete_disallow'])) {
  // managed ops is possible

  // does this thread has it enabled?

  // who are we?
  //if (loggedIn()) {
  //}
  // just reads cache
  //$user = getUserData(); 
  // who is OP?

  // even if we're not logged in, we should show the option...
  // if we're logged in, we don't need to prompt for password...
  //echo "op_managed is on<br>\n";

  // is this the OP thread?
  if (!$io['p']['threadid']) {
    // OP
  } else {
    //echo "<pre>", print_r($io, 1), "</pre>\n";
    // get userid for post $io['p]['threadid']
    // can't use user, it only displays when logged in
    $io['actions']['all'][] = array(
      // '/:uri/posts/:pno/delete',
      'link' => $io['boardUri'].'/posts/' .  $io['p']['no'] . '/delete.html',
      'label' => 'delete',
    );
  }
}