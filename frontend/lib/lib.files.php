<?php

function processFiles($filter_fields = false) {
  $fields = $filter_fields;
  if ($fields === false) {
    // just auto-detect them
    $fields = array_keys($_FILES);
  }
  // normalized fields as an array
  if (!is_array($fields)) $fields = array($filter_fields);

  $files = array();
  if (isset($_FILES)) {
    $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );
    //print_r($_FILES);
    foreach($fields as $field) {
      // each field could have multiple file support...
      $files[$field] = array();
      if (is_array($_FILES[$field]['tmp_name'])) {
        echo "detected multiple files<br>\n";
        foreach($_FILES[$field]['tmp_name'] as $i=>$path) {
          $res = sendFile($path, $_FILES[$field]['type'][$i], $_FILES[$field]['name'][$i]);
          // check for error
          if (empty($res['data']['hash'])) {
            echo "multifile - file error[", print_r($res, 1), "]<br>\n";
            return;
          }
          $files[$field][] = $res['data'];
        }
      } else {
        if ($_FILES[$field]['error'] && $_FILES[$field]['error'] !== 4) {
          echo "file PHP file upload error[", $phpFileUploadErrors[$_FILES[$field]['error']], "](", $_FILES[$field]['error'], ")<br>\n";
          return;
        }
        // make sure there is a file upload...
        if ($_FILES[$field]['error'] !== 4) {
          $res = sendFile($_FILES[$field]['tmp_name'], $_FILES[$field]['type'], $_FILES[$field]['name']);
          // check for error
          if (empty($res['data']['hash'])) {
            echo "fe::::lib.files:::processFiles file error[", print_r($res, 1), "]<br>\n";
            return;
          }
          $files[$field][] = $res;
        }
      }
    }
  }
  return array(
    'errors' => array(),
    'handles' => $files,
  );
}

?>
