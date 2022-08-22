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

  var threadElem = document.getElementById('threadsContainer')
  var divElem = document.createElement('div')
  divElem.innerHTML = html

  var children = Array.from(divElem.children)
  //console.debug('children', children)
  var posts = []
  for(var i in children) {
    const child = children[i]
    //console.debug('checking child', child, 'id', child.dataset.postId)
    if (child.dataset.postId) {
      // we get html back, so not much data

      // hover.js only uses hover
      // postId is a nice display thing tho
      // yous.js uses postId, json [quotes, board, backlicks, nomarkup(optional)]
      // embed.js - hover
      // titlescroll.js - hover
      // time.js - hover
      // quote.js - hover
      // filters.js - hover
      // forms.js - hover and postId
      // threadstat.js - json [files, userId]
      console.log('base/thread/view/refresh_thread.js - pushing', child.dataset.postId)
      //console.log('base/thread/view/refresh_thread.js - pushing', child.dataset)
      posts.push({
        post: child,
        postId: child.dataset.postId,
        json: {
          board: child.dataset.board, // needs board for yous system
          name: child.dataset.name,
          subject: child.dataset.subject,
          quotes: [],
          backlinks: [],
          files: [],
        },
        hover: false
      })
    }
    //console.log('child', child)
    threadElem.appendChild(child)
  }

  // not goingt to be in localStorage cache
  // but now we can put it in there for the hover cache...

  // we'll get 2 divs per post...
  console.log('updating title with', posts.length, 'unread posts')
  unreadPosts = posts.length
  document.title = '(' + unreadPosts + ') ' + originalTitle

  //divElem.className = "post-container"
  // we'll have to build the checkbox unless we request it...
  //threadElem.appendChild(divElem)

  //console.log('last item', threadElem.children[threadElem.children.length - 1])
  // erase container div
  /*
  threadElem.children[threadElem.children.length - 1] = threadElem.children[threadElem.children.length - 1].parentNode

  // threadElem.children[threadElem.children.length - 1].dataset.postId
  while(!threadElem.children[threadElem.children.length - 1].dataset.postId) {
    console.log('trying to fix div again')
    threadElem.children[threadElem.children.length - 1] = threadElem.children[threadElem.children.length - 1].parentNode
  }
  */

  //console.log('last item now', threadElem.children[threadElem.children.length - 1].dataset.postId)
  // mean it's didn't import it...
  // why don't we call this?
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
    foundNewReplies: foundPosts,
    posts: posts,
  }
}

var autoCheckbox = document.getElementById('autoRefreshEnable')
if (autoCheckbox && autoCheckbox.checked) {
  // have to start because callback wasn't defined...
  startTimer(5)
}
