<?php

// https://stackoverflow.com/a/22500394
function convertPHPSizeToBytes($sSize) {
  //
  $sSuffix = strtoupper(substr($sSize, -1));
  if (!in_array($sSuffix,array('P','T','G','M','K'))){
    return (int)$sSize;
  }
  $iValue = substr($sSize, 0, -1);
  switch ($sSuffix) {
    case 'P':
      $iValue *= 1024;
      // Fallthrough intended
    case 'T':
      $iValue *= 1024;
      // Fallthrough intended
    case 'G':
      $iValue *= 1024;
      // Fallthrough intended
    case 'M':
      $iValue *= 1024;
      // Fallthrough intended
    case 'K':
      $iValue *= 1024;
      break;
  }
  return (int)$iValue;
}

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

function renderPostForm($boardUri, $url, $options = false) {
  global $pipelines;

  $templates = loadTemplates('mixins/post_form');

  $values = array();
  $type = 'Thread';
  $tagThread = '';
  if ($options) {
    if (!empty($options['reply'])) {
      $type = 'Reply';
      $tagThread = '<input type="hidden" name="thread" value="' . $options['reply'] . '">';
      $values['thread'] = $options['reply'];
    }
    // this is for posting...
    // moved into board_portal
    /*
    if ($options['page']) {
      $tagThread = '<input type="hidden" name="page" value="' . $options['page'] . '">';
    }
    */
  }

  // FIXME: we need to be able to override webserver...
  $maxfiles = convertPHPSizeToBytes(ini_get('max_file_uploads'));
  $maxfilesize = min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));

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
    'postpassword' => array('type' => 'password',      'label' => 'Passwords', 'maxlength' => 50),
  );
  $formOptions = array(
    'buttonLabel' => 'New ' . $type,
    'useSections' => true,
    'wrapClass'   => 'row',
    'labelClass'  => 'label',
    'formClass'   => 'form-post',
    'formId'      => 'postform',
    'postFormTag' => '
    <section class="row jsonly">
      <div class="noselect" id="dragHandle">New ' . $type . '</div>
      <a class="close postform-style" href="' . $_SERVER['REQUEST_URI'] . '#!">X</a>
    </section>',
    //'labelwrap' => 'div',
    //'labelwrapclass' => 'label',
  );
  $io = array(
    'boardUri'   => $boardUri,
    'type'       => $type,
    'formfields' => $formfields,
  );
  $pipelines[PIPELINE_POST_FORM_FIELDS]->execute($io);
  $formfields = $io['formfields'];
  $pipelines[PIPELINE_POST_FORM_OPTIONS]->execute($formOptions);
  $pipelines[PIPELINE_POST_FORM_VALUES]->execute($values);

  $tags = array(
    'form' => generateForm($boardUri . '/post', $formfields, $values, $formOptions),
    'type' => $type,
    'action'      => $url,
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
