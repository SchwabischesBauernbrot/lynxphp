<?php

// frontend

// FIXME: we need access to package
$params = $getHandler();

$boardUri = $request['params']['boardUri'];
$threadNum = (int)$request['params']['num'];
$last = (int)getQueryField('last');

//echo "boardUri[$boardUri] id[$id]<br>\n";

// request JSON version
$result = $pkg->useResource('refresh', array(
  'boardUri' => $boardUri, 'thread'=> $threadNum, 'last' => $last,
));
// result === false might not be an error but just no posts...

//echo "<pre>", htmlspecialchars(print_r($result, 1)), "</pre>\n";
//$res = json_decode($result, true);
//echo "<pre>", htmlspecialchars(print_r($res, 1)), "</pre>\n";

// has final
if (is_array($result)) {
  // without the wrap it's not very SEO friendly...
  // maybe js could make it smart...
  global $boards_settings;
  foreach($result as $p) {
    echo renderPost($boardUri, $p, array(
      'boardSettings' => empty($boards_settings[$boardUri]) ? false : $boards_settings[$boardUri],
      //'checkable' => false,
    ));
  }
} else {
  /*
  echo "boardUri[$boardUri] id[$id]<br>\n";
  echo "<pre>be", htmlspecialchars(print_r($result, 1)), "</pre>\n";
  echo "<pre>decode", htmlspecialchars(print_r($res, 1)), "</pre>\n";
  */
  // not great, we're flipping formats, how is JS behavior supposed to stay consistent
  // but we do need the porting....
  // no wrap since we're embedded
  if (DEV_MODE) {
    wrapContent("<pre>BE Error, params since[$last] thread[$threadNum] board[$boardUri] DEBUG:", htmlspecialchars(print_r($result, 1)), "</pre>\n");
  } else {
    // json envelope for meta information?
    echo "Error rendering updates since[$last] to thread[$threadNum] on board[$boardUri]<br>\n";
  }
}

?>