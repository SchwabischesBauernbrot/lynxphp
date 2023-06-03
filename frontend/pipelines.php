<?php

// pipelines

// separated out for testing framework

// I could move the PIPELINE_ prefix into the definePipeline function
// but then you couldn't locate these in grep

// should this be wrapped in a function?
// depends on how much memory/cputime this takes...

// pipelines...
// - content page?
// template pipelines
// - siteNav (logged in vs logged out?)
// - boardNav
// - threadNav
// - postNav
// - panelNav
// - catalog tile
// - page tmpl
// - thread tmpl
// - reply tmpl
// - boardListing
// - board search
// - boardSettingTmpl

// forms pipelines
// - newThreadForm
// - newReplyForm
// - boardSettingsForm
// handler pipelines (pipelines creating pipelines)
// well maybe each module should leave it's own pipeline?
// but what consequences does that mean for the eco-system...
// - login
// - logout

$frontEndPipelines = array(
  'PIPELINE_HOMEPAGE_BOARDS_FIELDS',

  // now defined in base/board/view
  //'PIPELINE_BOARD_HEADER_TMPL',
  //'PIPELINE_BOARD_FOOTER_TMPL',
  'PIPELINE_BOARD_NAV',
  'PIPELINE_BOARD_STICKY_NAV',
  'PIPELINE_BOARD_DETAILS_TMPL',
  'PIPELINE_BOARD_SETTING_NAV',
  'PIPELINE_BOARD_SETTING_TMPL',
  'PIPELINE_BOARD_SETTING_GENERAL',

  'PIPELINE_FORM_CAPTCHA',
  'PIPELINE_FORM_WIDGET_THEMETHUMBNAILS',

  'PIPELINE_THREAD_ACTIONS',
  'PIPELINE_THREAD_ICONS',

  // POST_DATA?
  'PIPELINE_POST_PREPROCESS',
  'PIPELINE_POST_POSTPREPROCESS',
  //
  'PIPELINE_POST_TEXT_FORMATTING',
  'PIPELINE_POST_FORM_FIELDS',
  'PIPELINE_POST_FORM_OPTIONS',
  'PIPELINE_POST_FORM_TAGS',
  'PIPELINE_POST_FORM_VALUES',
  'PIPELINE_POST_VALIDATION',
  'PIPELINE_POST_ACTIONS',
  'PIPELINE_POST_ICONS',
  'PIPELINE_POST_LINKS',
  //'PIPELINE_POST_META_PREPEND',
  'PIPELINE_POST_META_PROCESS',
  //'PIPELINE_POST_META_APPEND',
  'PIPELINE_POST_ROW_APPEND',

  'PIPELINE_ADMIN_NAV',
  'PIPELINE_ADMIN_HEADER_TMPL',
  'PIPELINE_ADMIN_SETTING_GENERAL',

  'PIPELINE_GLOBALS_NAV',
  'PIPELINE_GLOBALS_HEADER_TMPL',

  'PIPELINE_ACCOUNT_NAV',

  // SETTINGS be included in the next 2?
  'PIPELINE_USER_NAV',
  'PIPELINE_USER_HEADER_TMPL',

  'PIPELINE_HEADERS',

  'PIPELINE_SITE_HEAD',
  'PIPELINE_SITE_HEAD_SCRIPTS',
  'PIPELINE_SITE_HEAD_STYLES',
  'PIPELINE_SITE_LEFTNAV',
  'PIPELINE_SITE_RIGHTNAV',
  // we can have one pipeline adjust all this data
  // and likely would be less overhead
  'PIPELINE_SITE_FOOTER_HEADER',
  'PIPELINE_SITE_FOOTER_NAV',
  // PIPELINE_SITE_FOOTER_HTML better?
  'PIPELINE_SITE_FOOTER_FOOTER',
  'PIPELINE_SITE_END_SCRIPTS',
  'PIPELINE_SITE_END_HTML',

  'PIPELINE_AFTER_WORK',
);

// we don't need to necessarily call this here
definePipelines($frontEndPipelines);

?>