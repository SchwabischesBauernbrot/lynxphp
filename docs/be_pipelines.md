PIPELINE_NEWPOST_PROCESS - controls what happens to new posts
  p (post)
  addToPostsDB [bool]
  processFilesDB [bool]
  bumpBoard [bool]
  bumpThread [bool]
  returnId [mixed]

PIPELINE_POSTTAG_REGISTER
  *tags [array of tag objects] (has a key)

PIPELINE_NEWPOST_TAG
  boardUri
  p [post object]
  priv [private post object]
  files [array of files]
  *tags [array of tag key strings]

PIPELINE_REPLY_ALLOWED
  p (post)
  *allowed [bool]

PIPELINE_PORTALS_DATA - pass BE data to FE to use in various portals (index:::sendResponse2)
  data [mixed]
  mtime [int]
  err [string]
  meta [object]
  portals [array of strings]
  *out.PORTAL [object]

WorkQueue pipelines:
PIPELINE_FILE
  boardUri
  fileid
  sha256
  path
  ext
  browser_type
  mime_type
  type
  filename
  size
  w
  h
  tn_w
  tn_h
  filedeleted
  spoiler

Banners/logs started to mock this out:
  PIPELINE_BOARD_DATA

Queueing started to mock these out:
  PIPELINE_BOARD_QUERY_MODEL <- shied away because smaller cachable queries maybe prefered
  PIPELINE_BOARD_PAGE_DATA
  PIPELINE_BOARD_CATALOG_DATA

Defined but not used
  PIPELINE_POST_DATA
  PIPELINE_THREAD_DATA
  PIPELINE_REPLY_DATA
  PIPELINE_USER_DATA
  PIPELINE_POST

