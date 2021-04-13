<?php

// this is all we should have in this file
// needs to be minimal in case we need to read data.php arrays
// nah the list of pipelines needs to be minimal
// not this function...
// the data is likely useless without the lib.packages

$pipelines = array();
function definePipeline($constant, $str = false) {
  global $pipelines;
  if ($str === false) $str = strtolower($constant);
  define($constant, $str);
  $pipelines[$str] = new pipeline_registry;
}

function definePipelines($constants) {
  global $pipelines;
  foreach($constants as $constant) {
    $str = strtolower($constant);
    define($constant, $str);
    $pipelines[$str] = new pipeline_registry;
  }
}


?>
