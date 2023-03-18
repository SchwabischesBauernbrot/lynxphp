<?php

// require lib.units.php

// would be great for the form to filled to the size of the container it's in
// that way the templates/css can control the presentation of the form

// what about controlling the size of the textarea?
// and how it expands?
function renderPostFormHTML($boardUri, $options = false) {
  $type = 'Thread';
  $tagThread = '';
  $button = 'Create thread';

  extract(ensureOptions(array(
    'reply'     => false, // needs to be the threadId
    'showClose' => true,
    'values'    => array(),
    'formId'    => 'postform',
    'maxMessageLength' => false,
    'pipelineOptions' => false,
  ), $options));


  if ($reply) {
    $type = 'Reply on thread #<a href="' . $boardUri . '/thread/' . $reply .'.html">' . $reply . '</a>';
    $button = 'Add reply';
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
  // but still capped by the php.ini setting
  // if capped, we should say so
  $maxfiles = convertPHPSizeToBytes(ini_get('max_file_uploads'));

  // router has a max_length
  /// but lib.http.server has something too
  global $max_length;
  $maxfilesize = $max_length;

  $formfields = array(
    'thread'   => array('type' => 'hidden'),
    //''         => array('type' => 'title', 'label' => 'New ' . $type, 'wrapClass' => 'jsonly', 'labelClass'=> 'noselect', 'wrapId' => 'dragHandle'),
    'name'     => array('type' => 'text',          'label' => 'Name', 'maxlength' => 100, 'autocomplete' => 'off'),
    'email'    => array('type' => 'text',          'label' => 'Email',
      'maxlength' => 255, 'autocomplete' => 'off'),
    'sage'     => array('type' => 'checkbox',      'label' => 'Sage'),
    'subject'  => array('type' => 'text',          'label' => 'Subject',
      'maxlength' => 150, 'autocomplete' => 'off'),
      // FIXME: come up with a better way than HTML in the label...
      // array based label?
    'message'  => array('type' => 'textarea',      'label' => 'Message',
      'postlabel' => '<span class="messageCounter"></span>', 'autocomplete' => 'off'),
    'files'    => array('type' => 'multidropfile', 'label' => 'Files', 'uniqueId' => true,
      'postlabel' => 'Max ' . $maxfiles . ' files</small><small>' . formatBytes($maxfilesize) . ' total'),
  );
  if ($maxMessageLength) {
    $formfields['message']['maxLength'] = $maxMessageLength;
  }
  if (!isLoggedIn()) {
    $formfields['postpassword'] = array(
      'type' => 'password', 'label' => 'Passwords', 'maxlength' => 50, 'autocomplete' => 'new-password',
      'placeholder' => 'Password to delete/spoiler/unlink later',
    );
  }

  $postFormHTML = '<div class="noselect" id="dragHandle">New ' . $type . '</div>';
  if ($showClose) {
    $postFormHTML .= '<a class="close postform-style" href="' . $_SERVER['REQUEST_URI'] . '#!">X</a>';
  }
  // wrap in section
  $postFormHTML = '<section class="row jsonly">' . $postFormHTML . '</section>';

  $formOptions = array_merge(jsChanStyle(), array(
    'buttonLabel' => $button,
    'formId'      => $formId,
    // this stomped formClass that set form-post on it
    'formClass'   => 'enable_formjs form-post',
    'postFormTag' => $postFormHTML,
  ));

  global $pipelines;
  $io = array(
    'boardUri'   => $boardUri,
    'type'       => $type,
    'formfields' => $formfields,
    'pipelineOptions' => $pipelineOptions,
  );
  // CAPTCHA probably hooks in here somewhere
  $pipelines[PIPELINE_POST_FORM_FIELDS]->execute($io);
  $formfields = $io['formfields']; // map output
  $pipelines[PIPELINE_POST_FORM_OPTIONS]->execute($formOptions);
  $pipelines[PIPELINE_POST_FORM_VALUES]->execute($values);

  // get html for these parameters
  return generateForm($boardUri . '/post', $formfields, $values, $formOptions);
}

// url is not really the form action
// renderPostFormHTML goes to boardUri/post
// action tag is used to create the anchor link to open form
// so this function create a link with a collapsed form...

// this template just seems to slap the ,form-wrapper on it...
// makes it's collapsible (without details/summary)
// but the js makes it float..
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