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
  fetch(getRefreshUrl(loc)).then(res => { statusCode = res.status; return res.text() } ).then(html => {
    //console.log('refreshPosts - status', statusCode)
    var result
    if (statusCode === 500) {
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
      startTimer((manual || result.foundNewReplies) ? 20 : lastRefresh * 2)
    }
    refreshElem.style.display = 'block'
    refreshingElem.style.display = 'none'
  })
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
  delete originalTitle
}