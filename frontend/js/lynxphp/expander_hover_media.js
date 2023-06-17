var hoverEnabled

/*
function parseCssRules(cssText) {
  var tokenizer = /\s*([a-z\-]+)\s*:\s*((?:[^;]*url\(.*?\)[^;]*|[^;]*)*)\s*(?:;|$)/gi,
    obj = {},
    token
  while ( (token=tokenizer.exec(cssText)) ) {
    obj[token[1].toLowerCase()] = token[2]
  }
  return obj
}

function getViewerInfoFromCSS(styleElem) {
  // only supports parse one rule from the CSS block
  var parts = styleElem.textContent.split('{', 2)
  var parts2 = parts[1].split('}', 2)
  //console.log('out', parts2[0])
  var rules = parseCssRules(parts2[0])
  //console.log('rules', rules)
  //console.log('bg', rules.background)
  var parts3 = rules.background.split('"', 3)
  //console.log('url', parts3[1])
  return {
    src: parts3[1],
    w: rules.width.replace('px', ''),
    h: rules.height.replace('px', ''),
  }
}
*/

// more universal (video and images...)
function getViewerInfoFromElem(elem) {
  //console.log('url', parts3[1])
  const resText = elem.querySelector('.resolution').innerText
  const parts = resText.split('x')
  // mime type would be good
  return {
    src: elem.querySelector('a.filename').href,
    w: parts[0],
    h: parts[1],
  }
}

function hover_addHandler(info, elem, overlay) {
  //console.log('info in', info)
  var el, isVideo = false
  if (elem.classList.contains('img')) {
    el = document.createElement('img')

  } else
  if (elem.classList.contains('video')) {
    el = document.createElement('video')
    isVideo = true
    el.autoplay = true
    el.muted = true
    el.src = info.src
    // how can we tell loop settings?

  // on 2nd thought, audio on hover is horrible idea
  /*
  } else
  if (elem.classList.contains('audio')) {
    el = document.createElement('audio')
    */
  }
  if (!el) {
    console.log('no hover handler for', elem.classList)
    return
  }
  el.width = info.w
  el.height = info.h
  //el.box = elem.getBoundingClientRect()
  el.isInside = function(x, y) {
    // has to be calculated at the current scroll
    var box = elem.getBoundingClientRect()
    var isInsideX = box.x < x && x < (box.x + box.width)
    var isInsideY = box.y < y && y < (box.y + box.height)
    return isInsideX && isInsideY
  }
  /*
  elem.onmouseover = function(e) {
    //console.log('over')
    //console.log('over', e.target)
    //console.log('info', info)
    // fixme video: volume/autoplay/loop
    if (!el.loaded) {
      el.src = info.src
      el.loaded = true
    }
    overlay.append(el)
    return false
  }
  */
  elem.onmousemove = function(e) {
    //console.log('move over a hover element')
    //console.log('move', e.target)
    var fc = overlay.firstChild
    // detect change
    if (fc !== el) {
      // on new elem hovered
      if (fc) {
        fc.remove()
      }
      if (!el.loaded) {
        console.log('expand_hover_media.js - loading', info.src)
        if (isVideo) {
          el.load()
          // applymedia settings?
        } else {
          el.src = info.src
        }
        el.loaded = true
      }
      if (isVideo) {
        const pp = el.play()
        pp.then(() => {
          //console.log('playing')
        }).catch(e => {
          console.log('video playing aborted', e)
        })
      }
      console.log('expand_hover_media.js - appending')
      overlay.append(el)
    }

    // position hover
    //console.log('positioning', e.x, e.y)

    // client, layer, screen, x
    // not page, offset
    overlay.style.left = e.x + 'px'
    overlay.style.top = e.y + 'px'

    // make sure no leading edge if off screen...
    var pastRight = e.x + el.width > window.innerWidth
    if (pastRight) {
      var xdiff = (e.x + el.width) - window.innerWidth
      //console.log('past right', xdiff)
      overlay.style.left = Math.max(0, e.x - xdiff) + 'px'
    }
    // y/height is relative, right is absolution
    var pastBottom = e.y + el.height > window.innerHeight
    if (pastBottom) {
      var ydiff = (e.y + el.height) - window.innerHeight
      //console.log('past bottom', ydiff)
      overlay.style.top = Math.max(0, e.y - ydiff) + 'px'
    }
    overlay.lastElem = elem
  }
  elem.onmouseout = function(e) {
    //console.log('left a hover element')
    //console.log('out', e.x, e.y)
    if (e.target === elem) {
      //console.log('one')
      //console.log('o', overlay, 'el', el)
      var fc = overlay.firstChild
      if (fc) {
        if (isVideo) {
          //console.log('pause')
          el.pause()
        }
        fc.remove()
      }
    //} else {
      //console.log('two')
    }
    overlay.lastElem = elem
    //console.log('out', e.target)
    //console.log('info', info)
    //overlay.removeChild(el)
    return false
  }
}

function hover_addHandlers(overlay) {
  var uvMediaElms = document.getElementsByClassName('post-file')
  //console.log('expander_hover_media.js - scanning for media', uvMediaElms.length)
  for(var i in uvMediaElms) {
    if (uvMediaElms.hasOwnProperty(i)) {
      var elem = uvMediaElms[i]
      // what happens if there's an upgrade where the page didn't have the upgrade
      // this is degenerative
      if (elem.classList.contains('useViewer')) {
        elem.classList.remove('useViewer') // simplify css processing
        var styleElem = elem.querySelector('style')
        //if (!styleElem) continue
        //var info = getViewerInfoFromCSS(styleElem)
        if (styleElem) styleElem.remove() // disable nojs viewer and reduce DOM
        //console.log('id', elem.id)

        var infoElem = elem.querySelector('.post-file-info')
        var info = getViewerInfoFromElem(infoElem)

        //console.log('info', info, 'elem', elem)
        hover_addHandler(info, elem, overlay)
      }
    }
  }
}

var overlay = document.createElement('div')
overlay.id = 'hoverViewer'
//overlay.style.maxWidth  = '90vw'
//overlay.style.maxHeight = '90vh'
overlay.style.maxWidth  = '100%'
overlay.style.maxHeight = '100%'
overlay.style.margin = 'auto auto'
overlay.style.display = 'block'
overlay.style.objectFit = 'contain'

// .top-overlay: bottom: 0, right: 0, position: fixed, z-index: 320
overlay.style.position = 'fixed'
overlay.style.top = 0
overlay.style.left = 0
overlay.style.zIndex = 320
// display: flex, flex-diretion: column, width: 100%, pointer-events: none
// align-items: flex-start, height: ~"calc(100% - 1.5em)";
document.body.appendChild(overlay)

// what if this fires before DOMContentLoaded?
window.addEventListener('reconfigure', function(e) {
  if (e.detail.user.settings.hover === hoverEnabled) {
    return
  }
  //console.log('expander_hover_media.js - reconfigure', e.detail)
  hoverEnabled = e.detail.user.settings.hover === '1'

  // what's the criteria to enable the hover viewer
  // probably .post-file

  if (hoverEnabled) {
    // add handlers
    console.log('expander_hover_media.js - recfg: add hover')
    hover_addHandlers(overlay)
  } else {
    // remove handlers
    console.log('expander_hover_media.js - recfg: remove hover')
  }
})

document.addEventListener("mousemove", function(e) {
  if (!overlay || !overlay.lastElem) return
  /*
  var box = overlay.lastElem.getBoundingClientRect()
  var isInsideX = box.x < e.x && e.x < (box.x + box.width)
  var isInsideY = box.y < e.y && e.y < (box.y + box.height)
  var isInside = isInsideX && isInsideY
  */
  var fc = overlay.firstChild
  if (fc) { // if hover is active
    if (!fc.isInside(e.x, e.y)) {
      fc.remove()
    }
  }
  /*
  console.log(e.x, e.y, 'bound', box, isInside)
  if (e.target !== overlay.lastElem && e.target.id !== overlay.id) {
    var inside = Array.from(overlay.children).includes(e.target)
    if (!inside) {
      //console.log('target', e.target, e.relatedTarget)
      var fc = overlay.firstChild
      if (fc) fc.remove()
    }
  }
  */
}, { passive: true })


window.addEventListener('DOMContentLoaded', (event) => {

  /*
  document.addEventListener("scroll", function(e) {
    //console.log('target', e.target)
    // it's the document that gets scrolled
    var fc = overlay.firstChild
    if (fc) fc.remove()
  })
  */
})