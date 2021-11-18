var lastReplyId = 0

function updateLastReply() {
  var threadElem = document.getElementById('threadsContainer')
  // just get last on the page
  lastReplyId = parseInt(threadElem.children[threadElem.children.length - 1].dataset.postId)
  //console.log('page last reply', lastReplyId)
}

function getRefreshUrl(loc) {
  //return BACKEND_PUBLIC_URL + 'opt/' + loc.boardUri + '/thread/' + loc.threadNum
  // /:uri/thread/:num/refresh
  updateLastReply()
  return loc.boardUri + '/thread/' + loc.threadNum + '/refresh?last=' + lastReplyId
}

function refreshCallback(error, html) {
  //console.log('refreshCallback', lastReplyId)
  var foundPosts = false

  if (!html) {
    return {
      foundNewReplies: foundPosts
    }
  }

  if (error) {
    console.error('refreshCallback error', error)
    // stop will be called already
    return {
      foundNewReplies: foundPosts
    }
  }

  foundPosts = true

  var divElem = document.createElement('div')
  divElem.innerHTML = html

  // not goingt to be in localStorage cache
  // but now we can put it in there for the hover cache...

  // we'll get 2 divs per post...
  unreadPosts = divElem.children.length / 2
  document.title = '(' + unreadPosts + ') ' + originalTitle

  //divElem.className = "post-container"
  // we'll have to build the checkbox unless we request it...
  var threadElem = document.getElementById('threadsContainer')
  threadElem.appendChild(divElem)

  //console.log('last item', threadElem.children[threadElem.children.length - 1])
  // erase container div
  threadElem.children[threadElem.children.length - 1] = threadElem.children[threadElem.children.length - 1].parentNode
  //console.log('last item now', threadElem.children[threadElem.children.length - 1])
  //updateLastReply()

  var rect = threadElem.children[threadElem.children.length - 1].getBoundingClientRect()
  //console.log('update check: doc hidden', document.hidden, 'isActive', isActive)
  if (rect.bottom < window.innerHeight && !document.hidden) {
    unreadPosts = 0
    document.title = originalTitle
  }

  // call things
  // these 2 are now done in posts...
  //updateTimes() // adjTimezone
  //hookShowHideUi() // showHide
  // embed.js is likely
  // expandAll in the future

  // well we could move these into addPost
  // most of these are best done in batch
  // tho in a future refactor that could help

  // hook quotes

  // FIXME: adapt to jschan
  /*
  var ids = document.getElementsByClassName('labelId')
  //console.log('threadjs found', ids.length, 'labelIds')
  // build idsRelation
  for (i = 0; i < ids.length; i++) {
    processIdLabel(ids[i]) // brings lookup up to date
  }
  updateIdLabels()

  // update any embeds added to make their links work
  if (typeof(updateEmbedElements) !== 'undefined') {
    updateEmbedElements()
  }
  */

  return {
    foundNewReplies: foundPosts
  }
}

var autoCheckbox = document.getElementById('autoRefreshEnable')
if (autoCheckbox && autoCheckbox.checked) {
  // have to start because callback wasn't defined...
  startTimer(5)
}
