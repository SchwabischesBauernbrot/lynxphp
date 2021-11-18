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

//echo "<pre>", htmlspecialchars(print_r($result, 1)), "</pre>\n";
//$res = json_decode($result, true);
//echo "<pre>", htmlspecialchars(print_r($res, 1)), "</pre>\n";

// has final
if (is_array($result)) {
  // without the wrap it's not very SEO friendly...
  // maybe js could make it smart...
  foreach($result as $p) {
    echo renderPost($boardUri, $p, array(
      //'checkable' => false,
    ));
  }
} else {
  /*
  echo "boardUri[$boardUri] id[$id]<br>\n";
  echo "<pre>be", htmlspecialchars(print_r($result, 1)), "</pre>\n";
  echo "<pre>decode", htmlspecialchars(print_r($res, 1)), "</pre>\n";
  */
  // no wrap since we're embedded
  echo "Error rendering updates since[$last] to thread[$threadNum] on board[$boardUri]<br>\n";
  echo "<pre>", htmlspecialchars(print_r($result, 1)), "</pre>\n";
}

?>