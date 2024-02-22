<?php

// are we a global mixin
// or just a modular data structure
// well we want this to be modular, so we can slap some pipelines in
//
// a lot of this data can be DB-level configured
// maybe we don't want tabs?
// why wouldn't you want organization?

// since we load a template here
// that timestamp would affect all routes that use board_footer module
// or anything that needs to call this function...
function renderPostActions($boardUri, $options = false) {
  global $pipelines;
  $templates = loadTemplates('mixins/post_actions');
  // should be more data/DSL driven using lib.form with modules
  // can't really use lib.form because it one large complex tabbed form
  // well the lightest touch would just be rendering the fields...
  $levels = array(
    'Not sure' => '',
    'Cancer' => 'cancer',
    'Spam / Advertisement' => 'spam',
    'Flood' => 'flood',
    'Board Rule Violation' => 'board',
    'Site Rule Violation' => 'global',
    'Copyright Violation' => 'copyright',
    'Illegal Content' => 'illegal',
    'Other' => 'other',
  );

  $tabs = array(
    array('name'=>'Delete', 'content' => $templates['loop0']),
    array('name'=>'Report', 'content' => $templates['loop1']),
  );

  // BO, Global or Admin only actions:
  if (1) {
    $tabs[]=array('name'=>'Media', 'content' => $templates['loop2']);
    //array('name'=>'Ban', 'content' => $templates['loop3']),
  }

  $html_delete_additions = '';
  $pipelines[PIPELINE_FE_POST_ACTIONS_DELETE_ADDITIONS]->execute($html_delete_additions);

  /*
  <label>
  <!-- scrub won't be a thing because no dedupe but magrathea will need it -->
  <!-- remove single file only, all files only, remove text, scrub files -->
  </label>
  */
  $deleteHtml = replace_tags($templates['loop0'], array('additional' => $html_delete_additions));


  $levelsHtml = '';
  foreach($levels as $lbl => $v) {
    $levelsHtml .= '<option value="' . $v . '">' .  $lbl . "\n";
  }

  $html_report_additions = '';
  $pipelines[PIPELINE_FE_POST_ACTIONS_REPORT_ADDITIONS]->execute($html_report_additions);

  $reportHtml = replace_tags($templates['loop1'], array('levels' => $levelsHtml, 'additional' => $html_report_additions));

  $html_media_additions = '';
  $pipelines[PIPELINE_FE_POST_ACTIONS_MEDIA_ADDITIONS]->execute($html_media_additions);

  $mediaHtml = replace_tags($templates['loop2'], array('additional' => $html_media_additions));

  $html_ban_additions = '';
  $pipelines[PIPELINE_FE_POST_ACTIONS_BAN_ADDITIONS]->execute($html_ban_additions);

  $banHtml = replace_tags($templates['loop3'], array('additional' => $html_ban_additions));

  // stomps tabs above
  $tabs = array(
    'Delete' => $deleteHtml,
    'Report' => $reportHtml,
    // BO, Global or Admin only actions:
    'Media' => $mediaHtml,
    // BO, Global or Admin only actions:
    'Ban' => $banHtml,
  );
  $bottomHtml = $templates['loop4']; // captcha

  $captcha_html = '';
  $io = array('field'   => 'captcha', 'details' => false);
  // generate/store/send captcha challenge, image, and possibly an ID
  $pipelines[PIPELINE_FORM_CAPTCHA]->execute($io);
  if (isset($io['html'])) {
    // need a width container because it'll expand
    $captcha_html = '<div style="width: 300px">' . $io['html'] . '</div>' . "\n";
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

return array(
  'levels' => array(
    'Not sure' => '',
    'Cancer' => 'cancer',
    'Spam / Advertisement' => 'spam',
    'Flood' => 'flood',
    'Board Rule Violation' => 'board',
    'Site Rule Violation' => 'global',
    'Copyright Violation' => 'copyright',
    'Illegal Content' => 'illegal',
    'Other' => 'other',
  ),
);

?>