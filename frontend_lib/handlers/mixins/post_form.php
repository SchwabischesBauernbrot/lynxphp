<?php

// moved into setup.php
/*
function formatBytes($bytes, $precision = 2) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  // Uncomment one of the following alternatives
  //$bytes /= pow(1024, $pow);
  $bytes /= (1 << (10 * $pow));

  return round($bytes, $precision) . ' ' . $units[$pow];
}
*/

// https://stackoverflow.com/a/19570313
function asBytes($ini_v) {
  $ini_v = trim($ini_v);
  $s = [ 'g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10 ];
  return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
}

// https://stackoverflow.com/a/49122313
function number_abbr($number) {
  $abbrevs = [12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => ''];

  foreach ($abbrevs as $exponent => $abbrev) {
    if (abs($number) >= pow(10, $exponent)) {
      $display = $number / pow(10, $exponent);
      $decimals = ($exponent >= 3 && round($display) < 100) ? 1 : 0;
      $number = number_format($display, $decimals).$abbrev;
      break;
    }
  }

  return $number;
}

function renderPostFormHTML($boardUri, $options = false) {
  $type = 'Thread';
  $values = array();
  $tagThread = '';

  extract(ensureOptions(array(
    'reply'     => false,
    'showClose' => true,
    'formId'    => 'postform',
  ), $options));


  if ($reply) {
    $type = 'Reply';
    $tagThread = '<input type="hidden" name="thread" value="' . $reply . '">';
    $values['thread'] = $reply;
  }
  // this is for posting...
  // moved into board_portal
  /*
  if ($options['page']) {
    $tagThread = '<input type="hidden" name="page" value="' . $options['page'] . '">';
  }
  */

  // FIXME: we need to be able to override webserver...
  $maxfiles = convertPHPSizeToBytes(ini_get('max_file_uploads'));
  global $max_length;
  $maxfilesize = $max_length;

  $formfields = array(
    'thread'   => array('type' => 'hidden'),
    //''         => array('type' => 'title', 'label' => 'New ' . $type, 'wrapClass' => 'jsonly', 'labelClass'=> 'noselect', 'wrapId' => 'dragHandle'),
    'name'     => array('type' => 'text',          'label' => 'Name', 'maxlength' => 100),
    'email'    => array('type' => 'text',          'label' => 'Email',
      'maxlength' => 255, 'autocomplete' => 'off'),
    'sage'     => array('type' => 'checkbox',      'label' => 'Sage'),
    'subject'  => array('type' => 'text',          'label' => 'Subject',
      'maxlength' => 150, 'autocomplete' => 'off'),
    'message'  => array('type' => 'textarea',      'label' => 'Message', 'autocomplete' => 'off'),
    'files'    => array('type' => 'multidropfile', 'label' => 'Files',
      'postlabel' => 'Max ' . $maxfiles . ' files</small><small>' . number_abbr($maxfilesize) . ' total'),
  );
  if (!isLoggedIn()) {
    $formfields['postpassword'] = array('type' => 'password',      'label' => 'Passwords', 'maxlength' => 50);
  }

  $postFormHTML = '<div class="noselect" id="dragHandle">New ' . $type . '</div>';
  if ($showClose) {
    $postFormHTML .= '<a class="close postform-style" href="' . $_SERVER['REQUEST_URI'] . '#!">X</a>';
  }
  // wrap in section
  $postFormHTML = '<section class="row jsonly">' . $postFormHTML . '</section>';

  $formOptions = array_merge(jsChanStyle(), array(
    'buttonLabel' => 'New ' . $type,
    'formId'      => $formId,
    'postFormTag' => $postFormHTML,
  ));

  global $pipelines;
  $io = array(
    'boardUri'   => $boardUri,
    'type'       => $type,
    'formfields' => $formfields,
  );
  $pipelines[PIPELINE_POST_FORM_FIELDS]->execute($io);
  $formfields = $io['formfields']; // map ouptut
  $pipelines[PIPELINE_POST_FORM_OPTIONS]->execute($formOptions);
  $pipelines[PIPELINE_POST_FORM_VALUES]->execute($values);

  // get html for these parameters
  return generateForm($boardUri . '/post', $formfields, $values, $formOptions);
}

function renderPostForm($boardUri, $url, $options = false) {
  global $pipelines;

  $type = 'Thread';
  if ($options) {
    if (!empty($options['reply'])) {
      $type = 'Reply';
    }
  }

  //echo "type[$type]<br>\n";

  $templates = loadTemplates('mixins/post_form');
  $tags = array(
    'form'   => renderPostFormHTML($boardUri, $options),
    'type'   => $type,
    'action' => $url,
    /*
    'uri'  => $boardUri,
    'tagThread'   => $tagThread,
    'maxlength'   => 4096,
    'maxfiles'    => $maxfiles,
    'maxfilesize' => $maxfilesize,
    */
  );
  $pipelines[PIPELINE_POST_FORM_TAGS]->execute($tags);
  return replace_tags($templates['header'], $tags);
}

?>