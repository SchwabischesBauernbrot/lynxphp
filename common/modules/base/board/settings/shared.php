<?php

// this data and functions used for all module php code

// function are automatically exported

/*
if (!function_exists('getBoardFields')) {
  echo "included once<br>\n";
*/
  function getBoardFields($section) {
    $fields = false;
    $boardSettings = getCompiledSettings('bo');
    //print_r($boardSettings);

    if (isset($boardSettings[$section])) {
      if (is_array($boardSettings[$section])) {
        $fields = $boardSettings[$section];
      } else {
      }
    } else {
      $fields = $shared['fields']; // imported from fe/shared.php
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