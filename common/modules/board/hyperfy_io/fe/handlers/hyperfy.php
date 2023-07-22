<?php

$params = $getHandler();

// FIXME: do we own this board?
$boardUri = $request['params']['uri'];

$settings = getter_getBoardSettings($boardUri);

//echo "<pre>", print_r($settings, 1), "</pre>\n";

$row = wrapContentData(array('header' => true));
wrapContentHeader($row);

if (empty($settings['hyperfy_uri'])) {
  echo "No Hyperfy URI set, ask the board owner to set it!<br>\n";
  wrapContentFooter($row);
  return;
}
$uri = $settings['hyperfy_uri'];

//echo "<pre>", print_r($settings, 1), "</pre>\n";

echo '<iframe width="100%" style="height: 95vh" src="https://hyperfy.io/' . urlencode($uri) . '/" frameborder=0>';

wrapContentFooter($row);