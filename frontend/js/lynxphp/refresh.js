var limitRefreshWait = 10 * 60
var refreshTimer = false
var autoRefresh = false
var unreadPosts = 0
var originalTitle = document.title

// manual affects the timer
function refreshPosts(manual) {

  // prevent more clicks...
  var refreshElem = document.getElementById('refresh')
  var refreshingElem = document.getElementById('refreshing')
  refreshElem.style.display = 'none'
  refreshingElem.style.display = 'block'

  // we have to ask the backend, is there anything new...
  const loc = whereAmI()
  let statusCode = false
  if (typeof(getRefreshUrl) === 'undefined') {
    console.error('no getRefreshUrl defined, just doing a full page reload for now')
    window.location.reload()
    return
  }
  try {
    fetch(getRefreshUrl(loc)).then(res => { statusCode = res.status; return res.text() } ).then(html => {
      //console.log('refreshPosts - status', statusCode)
      var result
      if (statusCode !== 200) {
        // we get a 500 warning in the js console already
        //console.warn('backend problem')
        result = { foundNewReplies: false }
      } else {
        result = refreshCallback(null, html)
        //console.log('refresh.js - result', result)
        if (result.foundNewReplies) {
          //console.log('new replies', result.posts.length)
          for(var i in result.posts) {
            //var post = JSON.parse(JSON.stringify(result.posts[i]))
            var post = result.posts[i]
            function scope(post) {
              //console.log('post', post, 'loc', loc)
              // first one is the summary one...
              var images = post.post.querySelectorAll('a.jsonly img.file-thumb')
              var checkPost = false
              for(var i in images) {
                if (!images.hasOwnProperty(i)) continue
                var img = images[i]
                // seems to work fine...
                //console.log(i, 'imgsrc', img.src)
                if (img.src.match('images/awaiting_thumbnail.png')) {
                  // uri and postid, url or hash
                  checkPost = true
                  break
                }
              }
              if (checkPost) {
                //console.log('need to generate', post.postId)
                function waitForThumb(postId, loc) {
                  var uri = loc.boardUri
                  // CF will aggressive cache the 404 though...
                  // also talking to the BE is yucky
                  /*
                  fetch(BACKEND_PUBLIC_URL + 'storage/boards/' + uri + '/THREADID/t_POSTID_MEDIA.jpg', { method: 'HEAD' }).then(res => {
                    if (res.ok) {
                      console.log('need to load image in')
                    } else {
                      // reschedule check
                    }
                  }).catch(function(err) {
                    console.error('waitForThumb err', err)
                  })
                  */
                  doWork() // queue probably needs some help
                  fetch('/' + uri + '/posts/' + postId + '/thumbnail.json').then(res => { statusCode = res.status; return res.text() } ).then(html => {
                    try {
                      var thumbs = JSON.parse(html)
                      //console.log('thumbs', thumbs)
                      var haveAll = true
                      for(var i in thumbs) {
                        var t = thumbs[i]
                        if (t.e) {
                          var thumbUrl = BACKEND_PUBLIC_URL + 'storage/boards/' + uri + '/' + loc.threadNum + '/t_' + postId + '_' + i  + '.jpg'
                          // locate img elem to swap
                          if (images[i]) {
                            //console.log('loading', thumbUrl, 'into', i, t, images[i])
                            images[i].src = thumbUrl
                            // adjust w/h
                            if (t.w) images[i].width = t.w
                            if (t.h) images[i].height = t.h
                          }
                        } else {
                          console.log('post', postId, 'missing thumb', i, t)
                          haveAll = false
                        }
                      }
                      if (!haveAll) {
                        // schedule next
                        setTimeout(function() {
                          waitForThumb(postId, loc)
                        }, 1000)
                      }
                    } catch(err) {
                      console.error('waitForThumb err', html, err)
                      // no need to reschedule
                      // minor UI error instead of hitting the server forever
                    }
                  })
                }
                waitForThumb(post.postId, loc)
              }
              // post.json.files is empty?
              // are there any thumbnails?

              //console.log('firing event')
              const newPostEvent = new CustomEvent('addPost', {
                 detail: post
              })
              //dispatch the event so quote click handlers, image expand, etc can be added in separate scripts by listening to the event
              setTimeout(() => {
                //console.log('dispatching event')
                window.dispatchEvent(newPostEvent)
              }, 50);
            }
            scope(post)
          }
        //} else {
          //console.log('result foundNewReplies is falsish', result)
        }
      }

      // refreshButton.style.display = 'inline'
      //console.log('refreshPosts - autoRefresh', autoRefresh)
      if (autoRefresh) {
        startTimer((manual || result.foundNewReplies) ? 10 : lastRefresh * 2)
      }
      refreshElem.style.display = 'block'
      refreshingElem.style.display = 'none'
    })
  } catch(e) {
    console.error('refresh fetch? err', e)
    if (autoRefresh) {
      startTimer((manual || result.foundNewReplies) ? 10 : lastRefresh * 2)
    }
  }
}

function stopTimer() {
  if (refreshTimer) {
    if (typeof(labelRefresh) !== 'undefined') {
      labelRefresh.innerHTML = ''
    }
    // clear old timer and allow a new one to be set
    clearInterval(refreshTimer)
    refreshTimer = false
  }
}

function startTimer(time) {
  if (time > limitRefreshWait) {
    time = limitRefreshWait
  }

  currentRefresh = time
  lastRefresh = time

  // don't set a timer unless there's a reason
  if (typeof(refreshCallback) !== 'undefined') {
    if (typeof(labelRefresh) !== 'undefined') {
      labelRefresh.innerHTML = currentRefresh
    }
    // since we're above to stomp previous refreshTimer make sure it doesn't fire
    stopTimer()
    refreshTimer = setInterval(function checkTimer() {
      currentRefresh--

      if (!currentRefresh) {
        refreshPosts(false)
        if (typeof(labelRefresh) !== 'undefined') {
          labelRefresh.innerHTML = ''
        }
      } else {
        if (typeof(labelRefresh) !== 'undefined') {
          labelRefresh.innerHTML = currentRefresh
        }
      }

    }, 1000)
  } else {
    console.log('refreshCallback is not defined')
  }
}

// change the autoRefresh timer variable
function changeRefresh(dontSave) {
  //console.log('changeRefresh - start', autoRefresh)
  // if autorefresh is enabled
  if (autoRefresh) {
    // turn it off
    stopTimer()
  } else {
    // start it (turn it on)
    startTimer(5)
  }

  // toggle autoRefresh
  autoRefresh = !autoRefresh
  //console.log('changeRefresh - set', autoRefresh)

  //console.log('changeRefresh', dontSave, 'auto', autoRefresh)
  // FIXME: lynxphp communicates with backend for settings like this...
  /*
  if (!dontSave) {
    var days = 365*10 // remember this setting for 10 years
    //console.log('writing', autoRefresh?'true':'false')
    setCookie('myAutoRefresh', autoRefresh?'true':'false', days)
  }
  */
}

function manual_refresh() {
  stopTimer()
  refreshPosts(true)
}

// we have to wait for refreshCallback to be defined in other script tags
// before we can call changeRefresh
window.addEventListener('DOMContentLoaded', () => {
  if (!DISABLE_JS) {
    // wire up checkbox
    var autoCheckbox = document.getElementById('autoRefreshEnable')
    if (autoCheckbox) {
      autoCheckbox.onclick = function() {
        changeRefresh(false)
      }
      // bring online
      if (autoCheckbox.checked) {
        changeRefresh(true)
      }
    }

    // wire up button
    var updateButton = document.getElementById('updatePage')
    if (updateButton) {
      updateButton.onclick = function() {
        // make sure we can make it do something
        if (typeof(refreshCallback) !== 'undefined') {
          manual_refresh()
          return false
        }
      }
    }

    document.onscroll = function() {

      if (!unreadPosts) {
        return;
      }

      var threadElem = document.getElementById('threadsContainer')
      var rect = threadElem.children[threadElem.children.length - 1].getBoundingClientRect()

      if (rect.bottom < window.innerHeight) {
        // refresh post would set this...
        unreadPosts = 0
        console.log('refresh - restoring original title')
        document.title = originalTitle
      }

    }

    var isActive = true

    window.onfocus = function () {
      isActive = true
      document.onscroll()
    }

    window.onblur = function () {
      isActive = false
    }
  } else {
    originalTitle = ''
  }
})