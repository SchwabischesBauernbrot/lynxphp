<?php

// this is all we should have in this file
// needs to be minimal in case we need to read data.php arrays
// nah the list of pipelines needs to be minimal
// not this function...
// the data is likely useless without the lib.packages

$pipelines = array();
function definePipeline($constant, $str) {
  global $pipelines;
  define($constant, $str);
  $pipelines[$str] = new pipeline_registry;
}

?>
