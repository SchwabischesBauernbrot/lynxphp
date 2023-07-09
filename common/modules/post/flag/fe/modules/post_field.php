<?php

$params = $getModule();

// io['formFields'] is formFields
//if (!empty($io['pipelineOptions']['showCAPTCHA'])) {

  // should this be enabled?
/*
"flagData":[
  {"_id":"64a165cf9eb4e6b53bdee01f","name":"Cuban and Don"},
  {"_id":"64a16590e8430bb54fab67f4","name":"Ichkeria"},
  {"_id":"649c11a4403810c56a5ae71b","name":"Ingria"},
  {"_id":"64a1654de8430bb54fab67df","name":"Povolj`e"},
  {"_id":"64a1646259e815b52f3f3cb6","name":"Primorye"},
  {"_id":"649d4c943f2976c562a9f445","name":"Russian Volunteer Corps"},
  {"_id":"649d4bd8b9aefcc585f319ea","name":"Siberia"},
  {"_id":"64a16500d8f195b544c9f84f","name":"Tatarstan"},
  {"_id":"649d4ba08ebb84c56c8679b9","name":"Ural Republic"},
  {"_id":"637f8b15b31c123c102319e4","name":"Беларусь"},
  {"_id":"637f905e0615ec3c3627981d","name":"Полк імя Кастуся Каліноўскага"}
]
*/
  // if flagData exist should be enough for now...
  /*
  // lynxchan is locationFlags
  $board_settings = getter_getBoardSettings($io['boardUri']);
  */
  $boardData = getter_getBoard($io['boardUri']);
  //print_r($boardData);
  $flags = empty($boardData['flagData']) ? array() : $boardData['flagData'];
  $options = array(
    '' => 'No Flag',
  );
  foreach($flags as $i => $row) {
    $n = $row['name'];
    $options[$n] = $n;
  }
  /*
  if (isset($board_settings['flag_mode'])) {
    $mode = $board_settings['flag_mode'];
    $enable = false;
    if ($enable) {
    */
      // id=customflag name=customflag
      // img class=jsonly selected-flag
      // data-src on the option
      $io['formfields']['flag'] = array( 'type' => 'select', 'label' => 'Flag', 'options' => $options);
      // options? load them from?
    //}
  //}
//}

?>