<?php

// frontend

// FIXME: we need access to package
$params = $getHandler();

$boardUri = $request['params']['board'];
// .json or .html?
$id = str_replace('.html', '', $request['params']['id']);
$id = (int)str_replace('.json', '', $id);

//echo "boardUri[$boardUri] id[$id]<br>\n";

// request JSON version
$result = $pkg->useResource('preview', array('board' => $boardUri, 'id' => $id . '.json'));

//echo "<pre>", htmlspecialchars(print_r($result, 1)), "</pre>\n";
$res = json_decode($result, true);
//echo "<pre>", htmlspecialchars(print_r($res, 1)), "</pre>\n";

// has final
if (!empty($res['final'])) {
  // without the wrap it's not very SEO friendly...
  // maybe js could make it smart...
  // could include direct replies...
  wrapContent(renderPost($boardUri, $res['final'], array(
    'checkable' => false,
  )));
} else {
  /*
  echo "boardUri[$boardUri] id[$id]<br>\n";
  echo "<pre>be", htmlspecialchars(print_r($result, 1)), "</pre>\n";
  echo "<pre>decode", htmlspecialchars(print_r($res, 1)), "</pre>\n";
  */
  // no wrap since we're embedded
  echo "Error rendering post[$id] on board[$boardUri]<br>\n";
}

?>