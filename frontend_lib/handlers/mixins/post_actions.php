<?php

function renderPostActions($boardUri, $options = false) {
  global $pipelines;
  $templates = loadTemplates('mixins/post_actions');
  $tabs = array(
    array('name'=>'Delete', 'content' => $templates['loop0']),
    array('name'=>'Report', 'content' => $templates['loop1']),
    // BO, Global or Admin only actions:
    array('name'=>'Media', 'content' => $templates['loop2']),
    //array('name'=>'Ban', 'content' => $templates['loop3']),
  );
  $tabs = array(
    'Delete' => $templates['loop0'],
    'Report' => $templates['loop1'],
    // BO, Global or Admin only actions:
    'Media' => $templates['loop2'],
    // BO, Global or Admin only actions:
    'Ban' => $templates['loop3'],
  );
  $bottomHtml = $templates['loop4']; // captcha

  $captcha_html = '';
  $io = array('field'   => 'captcha', 'details' => false);
  // generate/store/send captcha challange, image, and possibly an ID
  $pipelines[PIPELINE_FORM_CAPTCHA]->execute($io);
  if (isset($io['html'])) {
    //$captcha_html = $io['html'];
  }

  $bottomHtml = str_replace('{{captcha}}', $captcha_html, $bottomHtml);
  $tags = array(
    'actions' => renderTabs($tabs, array(
      'name' => 'action',
      'defaultNone' => true, 'useDetails' => false, 'any' => $bottomHtml,
      'closeAll' => true,
  )));
  return replace_tags($templates['header'], $tags);
}

?>
