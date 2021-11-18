PIPELINE_BOARD_HEADER_TMPL
  *tags
    tag => value
  boardUri
PIPELINE_BOARD_FOOTER_TMPL
  *tags
    tag => value
PIPELINE_BOARD_NAV
  *Label => url
PIPELINE_BOARD_STICKY_NAV*
  html string
PIPELINE_BOARD_DETAILS_TMPL
  boardUri
  *tags
    tag => value
PIPELINE_BOARD_SETTING_NAV
  *navItems
  boardUri
PIPELINE_BOARD_SETTING_GENERAL* (fields)
  name
    label
    type
PIPELINE_FORM_CAPTCHA
  fields
  details
  *html
PIPELINE_FORM_WIDGET_THEMETHUMBNAILS
  field
  details
  value
  *html
PIPELINE_THREAD_ACTIONS
  boardUri
  p
  *actions
PIPELINE_THREAD_ICONS
  boardUri
  p
  *icons
PIPELINE_POST_PREPROCESS (p)
PIPELINE_POST_POSTPREPROCESS
  posts
  boardThreads
  pagenum
PIPELINE_POST_TEXT_FORMATTING (p)*
  safeCom
  boardUri
PIPELINE_POST_FORM_FIELDS
  boardUri
  type
  *formfields
PIPELINE_POST_FORM_OPTIONS*
  buttonLabel
  formId
  postFormTag
PIPELINE_POST_FORM_TAGS*
  tag => value
PIPELINE_POST_FORM_VALUES*
  thread
PIPELINE_POST_VALIDATION
  boardUri
  endpoint
  *headers
  *values
  *redir
  error
  redirNow
PIPELINE_POST_ACTIONS
  boardUri
  p
  *actions
PIPELINE_POST_ICONS
  boardUri
  p
  *icons

PIPELINE_ADMIN_NAV
PIPELINE_ADMIN_HEADER_TMPL
PIPELINE_ADMIN_SETTING_GENERAL

PIPELINE_GLOBALS_NAV
PIPELINE_GLOBALS_HEADER_TMPL

// SETTINGS be included in the next 2?
PIPELINE_USER_NAV
PIPELINE_USER_HEADER_TMPL

PIPELINE_SITE_HEAD
// we can have one pipeline adjust all this data
// and likely would be less overhead
PIPELINE_SITE_FOOTER_HEADER
PIPELINE_SITE_FOOTER_NAV
PIPELINE_SITE_FOOTER_FOOTER
PIPELINE_SITE_END_SCRIPTS
  *scripts
PIPELINE_SITE_END_HTML
  siteSettings
  userSettings
  *end_html
PIPELINE_AFTER_WORK


Defined but not used
  PIPELINE_HOMEPAGE_BOARDS_FIELDS
  PIPELINE_BOARD_SETTING_TMPL