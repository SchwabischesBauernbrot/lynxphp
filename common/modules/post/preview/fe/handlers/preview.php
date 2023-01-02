<?php

// frontend
$params = $getHandler();

// input parameters
$boardUri = $request['params']['board'];
// .json or .html?
$id = str_replace('.html', '', $request['params']['id']);
$id = (int)str_replace('.json', '', $id);

//echo "boardUri[$boardUri] id[$id]<br>\n";

// request JSON version
$result = $pkg->useResource('preview', array('board' => $boardUri, 'id' => $id . '.json'));

// this is an official lynxchan endpoint
//echo "<pre>", htmlspecialchars(print_r($result, 1)), "</pre>\n";
$res = json_decode($result, true);
//echo "<pre>", htmlspecialchars(print_r($res, 1)), "</pre>\n";

// has final
if (!empty($res['final'])) {
  // without the wrap it's not very SEO friendly...
  // maybe js could make it smart...
  // could include direct replies...

  // could we include the post form
  // well we want to make sure they know the context before replying...
  $wrapOptions = array();

  if (CANONICAL_BASE) {
    $wrapOptions = array(
      // FIXME: don't require the trailing slash
      'canonical' => CANONICAL_BASE . $boardUri . '/preview/' . $id,
    );
  }
  // final.threadid, final.created_at
  if (!isset($res['final']['created_at'])) {
    http_response_code(404);
    wrapContent("Post[$id] on board[$boardUri] does not exist<br>\n");
    return;
  }

  wrapContent(renderPost($boardUri, $res['final'], array(
    'checkable' => false,
    // this is sometimes undefined? yes lynxbridge caused this
    // we just need to update the backend
    'postCount' => $res['postCount'],
    // postCount?
    'boardSettings' => $res['boardSettings'],
    // FIXME: get some BE-EP somehow
    'userSettings' => getUserSettings(),
  )), $wrapOptions);
} else {
  /*
  echo "boardUri[$boardUri] id[$id]<br>\n";
  echo "<pre>be", htmlspecialchars(print_r($result, 1)), "</pre>\n";
  echo "<pre>decode", htmlspecialchars(print_r($res, 1)), "</pre>\n";
  */
  // no wrap since we're embedded
  http_response_code(500);
  wrapContent("Error rendering post[$id] on board[$boardUri]<br>\n");
  //print_r($res['final']);
}

?>