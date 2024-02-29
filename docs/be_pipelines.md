PIPELINE_NEWPOST_PROCESS - controls what happens to new posts
  p (post)
  addToPostsDB [bool]
  processFilesDB [bool]
  bumpThread [bool]
  returnId [mixed]
  issues [array]
  log [array]
  createPostOptions
    bumpBoard [bool]

PIPELINE_BE_FILE_FIX_MIME - improves the default PHP mime detection
  f tmp upload data
    meta
    hash
    name
    size
  p (srcPath)
  *m (mime)

PIPELINE_BE_POST_EXPOSE_DATA_FIELD - exposing json field data from posts
  fields

PIPELINE_BE_POST_FILTER_DATA_FIELD - transform post exposed json field values
  fields

PIPELINE_BE_FILE_FIX_FILEDATA
  *fileData

PIPELINE_BE_USER_PERMITTED - adjust permissions
  user_id
  target (b/URI p/URI/POSTID)
  permission (delete_post)
  *access
  boardUri (optional)

PIPELINE_POSTTAG_REGISTER
  *tags [array of tag objects] (has a key)

PIPELINE_NEWPOST_TAG
  boardUri [string]
  p [post object]
  priv [private post object]
  files [array of files]
  *tags [array of tag key strings]

PIPELINE_POST_ADD
  boardUri [string]
  p [post object]
  priv [private post object]
  files [array of files]
  inow [integer]
  threadNum [integer]
  id [integer]

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

  PIPELINE_BE_\*_PORTAL - a more pinpointed pipeline (* is a portal name, BOARD_SETTINGS)
  PIPELINE_BE_BOARD_PORTAL
  PIPELINE_BE_BOARD_SETTINGS_PORTAL
    data [array]
    mtime [integer]
    err [string]
    meta [array]
    portals [array]
    out [array]
    resp [array]
    portal [string]
    portalOptions [array]
    *out [object]

PIPELINE_ACCOUNT_DATA
  userid [integer]
  *account
    noCaptchaBan [boolean]
    login [string]
    email [string]
    globalRole [integer]
    boardCreationAllowed [boolean]
    ownedBoards [string[]]
    groups [string[]]
    reportFilter [array]
    username [string]
    publickey [string]
WorkQueue pipelines:
PIPELINE_WQ_POST_ADD
  boardUri [string]
  p [post object]
  priv [private post object]
  files [array of files]
  inow [integer]
  threadNum [integer]
  id [integer]
PIPELINE_WQ_FILE_ADD
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

Module specific ones:
  PIPELINE_BE_ADMIN_QUEUE_DATA (post_queue row)
  base/board/users
  PIPELINE_BOARD_USER_DATA
    * [array]

Defined but not used
  PIPELINE_POST_DATA
  PIPELINE_THREAD_DATA
  PIPELINE_REPLY_DATA
  PIPELINE_USER_DATA
  PIPELINE_POST
