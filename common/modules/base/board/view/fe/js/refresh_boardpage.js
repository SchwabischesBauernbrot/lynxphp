var lastReplyId = 0

var refreshCallback = function(error, data) {
  foundPosts = false

  if (error) {
    console.error('refreshCallback error', error)
    // stop will be called already
    return
  }

  var receivedData
  try {
    receivedData = JSON.parse(data)
  } catch (e) {
    console.error('refreshCallback parse error', e, data)
    receivedData = {}
  }
  //console.log('receivedData', receivedData)

  // thread mode
  // vs
  // board page mode vs catalog mode

  var posts = receivedData.data && receivedData.data.posts
  if (posts && posts.length) {
    var tLastPost = posts[posts.length - 1]

    if (tLastPost.postId > lastReplyId) {
      foundPosts = true

      for (var i = 0; i < posts.length; i++) {

        var post = posts[i]

        if (post.postId > lastReplyId) {
          addPost(post)
          lastReplyId = post.postId
        }

      }

      document.title = '(' + unreadPosts + ') ' + originalTitle
      // delay until we can get boundingclientrect
      setTimeout(function() {
        if (lastPost && lastPost.getBoundingClientRect) {
          var rect = lastPost.getBoundingClientRect()
          //console.log('update check: doc hidden', document.hidden, 'isActive', isActive)
          if (rect.bottom < window.innerHeight && !document.hidden) {
            unreadPosts = 0

            document.title = originalTitle
          }
        } else {
          console.log('lastPost was still shit', lastPost)
        }
      }, 5000)

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

    // addPost already handles this
    /*
    var postingQuotes = document.getElementsByClassName('linkQuote')

    for (var i = 0; i < postingQuotes.length; i++) {
      processPostingQuote(postingQuotes[i])
    }
    */

    //console.log("updatingIdLabels")

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
  }

  if (autoRefresh) {
    startTimer(manualRefresh || foundPosts ? 60 : lastRefresh * 2)
  }

}

startTimer(5)