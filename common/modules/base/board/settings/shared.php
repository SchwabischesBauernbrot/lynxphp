<?php

// this data and functions used for all module php code

// function are automatically exported

/*
if (!function_exists('getBoardFields')) {
  echo "included once<br>\n";
*/
  function getBoardFields($section) {
    global $shared;
    $fields = false;
    $boardSettings = getCompiledSettings('bo');
    //echo "<pre>", print_r($boardSettings, 1), "</pre>\n";

    if (isset($boardSettings[$section])) {
      if (is_array($boardSettings[$section])) {
        $fields = $boardSettings[$section];
      } else {
      }
    } else {
      $fields = $boardSettings['board'];
    }
    return $fields;
  }
/*
} else {
  echo "included twice", gettrace(), "<br>\n";
}
*/

// allow export of data as $shared in your handlers and modules
return array(
  //'fields' => array(
  //),
);

?>