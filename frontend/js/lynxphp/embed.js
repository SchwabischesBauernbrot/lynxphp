let YTready = false
let YTreadyQ = []
function onYouTubeIframeAPIReady() {
  //console.log('iframeapiready', YTreadyQ.length)
  // fire all pending function in queue
  for(var i in YTreadyQ) {
    YTreadyQ[i]()
  }
  YTreadyQ = []
  YTready = true
}

function addToPostMedia(postScope, embedder) {
  var postFiles = postScope.querySelector('.post-files')
  var containerElem = document.createElement('div');
  containerElem.className="post-file embeddableMedia"
  containerElem.innerHTML = '<span class="post-file-info"></span>' +
  '<div class="post-file-src" data-type="image" attachment="false">' +
    '<a href="' + embedder.imageHqUrl + '"><img class="file-thumb" src="' + embedder.imageSafeUrl + '" width=120 loading="lazy" preload="no" alt="youtube thumbnail"></a>' +
  '</div>'+
  '<span class="post-file-info"></span>'
  var linkElem = containerElem.querySelector('a')
  linkElem.onclick = function() {
    // find img, remove it
    var img = this.querySelector('img') // find our thumb (child img tag)
    var imgParent = img.parentNode
    imgParent.removeChild(img)
    // drop viewer
    var viewerElem = embedder.toElem()
    imgParent.appendChild(viewerElem)

    // add close link to details...
    var link=document.createElement('a')
    link.class="closeThumb"
    link.appendChild(document.createTextNode('Close'))
    link.href="#"
    link.onclick=function() {
      // this is the close code
      imgParent.removeChild(viewerElem) // remove viewerElem
      imgParent.appendChild(img) // add img back
      // remove self
      this.parentNode.removeChild(this) // remove close link wrapper
      return false
    }
    var details=containerElem.querySelector('.post-file-src')
    //console.log('details', details)
    details.appendChild(document.createTextNode(' '))
    details.appendChild(link)

    return false
  }
  postFiles.appendChild(containerElem)
}

class playlist {

  addtracks(track) {
    //console.log('adding', track)
    if (this.tracks === undefined) this.tracks = []
    this.tracks.push(track)
  }
  prevTrack() {
    this.position--
    if (this.position < 0) {
      this.position = this.tracks.length -1
    }
    this.changeTrack()
  }
  nextTrack() {
    this.position++
    if (this.position >= this.tracks.length) {
      this.position = 0
    }
    this.changeTrack()
  }
  getNavElem() {
    var navElem = document.querySelector('span.userbar-icons-right')
    if (!navElem) {
      // if logged out use the old style
      navElem = document.querySelector('nav.stickynav')
    }
    return navElem
  }
  changeTrack() {
    // we autoplay
    this.playing = true
    var navElem = this.getNavElem()
    if (!navElem) {
      console.warn('disabling playlist player because stickynav not found')
      return
    }
    var hasPl = navElem.querySelector('a.playlist')
    if (!hasPl) return
    hasPl.innerHTML = '[&#10074;&#10074;]';

    var ref = this
    var viewerElem = document.getElementById('mediaplayer')
    if (viewerElem) {
      viewerElem.parentNode.removeChild(viewerElem)
    }

    var mediaFloater = document.querySelector('.mediaFloater')
    if (!mediaFloater) {
      console.warn('no .mediaFloater')
      return
    }

    /*
    viewerElem = document.createElement('iframe')
    viewerElem.className = 'playlistViewer'
    viewerElem.id = "ytplayer"
    viewerElem.align = "top"

    viewerElem.src = this.tracks[this.position]
    */
    // calls toElem to return iframeElem
    viewerElem = this.tracks[this.position].toElem(() => {
      this.nextTrack()
    })
    viewerElem.id = "mediaplayer"
    // update player
    this.player = this.tracks[this.position].player
    /*
    viewerElem.onload = function() {
      console.log('init yt api')
    }
    */
    //navElem.appendChild(viewerElem)
    //viewerElem.style.float = 'left'
    //navElem.insertBefore(viewerElem, navElem.childNodes[0])
    mediaFloater.appendChild(viewerElem)
    //}
  }
  stop() {
    this.playing = false
    if (this.player && this.player.pauseVideo) {
      this.player.pauseVideo()
    } else {
      console.log('cant pause...')
    }
  }
  start() {
    this.playing = true
    this.openViewer()
    this.changeTrack()
  }
  toggle() {
    if (this.playing) {
      this.stop()
      var navElem = this.getNavElem()
      if (!navElem) {
        console.warn('disabling playlist player because stickynav not found')
        return
      }
      var hasPl = navElem.querySelector('a.playlist')
      if (!hasPl) return
      hasPl.innerHTML = '[&#9654;]';
    } else {
      this.start()
    }
  }
  openViewer() {
    var navElem = this.getNavElem()
    if (!navElem) {
      console.warn('disabling playlist player because stickynav not found')
      return
    }
    var hasViewer = navElem.querySelector('.prevTrack')
    if (hasViewer) {
      return
    }

    var newFloatElem = document.createElement('div')
    // FIXME: needs a close handle
    newFloatElem.className = "mediaFloater"
    newFloatElem.style.position = "fixed"
    //newFloatElem.style.width = '300px'
    //newFloatElem.style.height = '150px'
    newFloatElem.style.bottom = '46px'
    newFloatElem.style.right = 5
    newFloatElem.style.zIndex = 2
    //newFloatElem.innerText = 'bob'
    document.body.appendChild(newFloatElem)

    //var spaceElem = document.createElement('div')
    //newFloatElem.insertBefore(spaceElem, navElem.childNodes[0])

    // add new nav buttons
    var ref = this
    var prevTrackElem = document.createElement('a')
    prevTrackElem.className = 'nav-item prevTrack'
    prevTrackElem.innerHTML = '[&#9664;&#9664;]'
    prevTrackElem.style.cursor = 'hand'
    prevTrackElem.onclick = function() {
      ref.prevTrack()
    }
    navElem.appendChild(prevTrackElem)

    var nextTrackElem = document.createElement('a')
    nextTrackElem.className = 'nav-item nextTrack'
    nextTrackElem.innerHTML = '[&#9654;&#9654;]'
    nextTrackElem.style.cursor = 'hand'
    nextTrackElem.onclick = function() {
      ref.nextTrack()
    }
    navElem.appendChild(nextTrackElem)

    /*
    var space2Elem = document.createElement('div')
    //space2Elem.className = 'nav-item'
    //space2Elem.innerHTML = '&nbsp;'
    space2Elem.style.clear = 'both';
    navElem.appendChild(space2Elem)

    var trackListElem = document.createElement('select')
    trackListElem.id = "trackList"
    for(var i in this.tracks) {
      var embedder = this.tracks[i]
      var elem = document.createElement('option')
      elem.value = embedder.videoId
      elem.innerText = embedder.videoId
      trackListElem.appendChild(elem)
    }
    navElem.appendChild(trackListElem)
    */
  }
  enableUI() {
    var navElem = this.getNavElem()
    if (!navElem) {
      console.warn('disabling playlist player because stickynav not found')
      return
    }
    //console.log('navElem', navElem)
    var hasPl = navElem.querySelector('a.playlist')
    if (hasPl) {
      return
    }
    // load player api
    /*
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    */

    // add play button
    var plStartElem = document.createElement('a')
    plStartElem.className = 'nav-item playlist'
    plStartElem.innerHTML = '[&#9654;]'
    plStartElem.style.cursor = 'hand'

    //console.log('loading..')
    var ref = this
    plStartElem.onclick = function() {
      // FIXME: close any open embeds

      // set up api once
      if (!navElem.ytapiLoaded) {
        var ytSrcElem = document.createElement('script')
        ytSrcElem.src = "//www.youtube.com/player_api"
        navElem.appendChild(ytSrcElem)
        navElem.ytapiLoaded = true // script added (not loaded yet...)

        // load iframe when the API is loaded
        YTreadyQ.push(function() {
          ref.toggle()
        })
      } else {
        ref.toggle()
      }
    }
    //plStartElem.href = '#';
    navElem.appendChild(plStartElem)

    // start at beginning of playlist
    this.position = 0
  }
}

const pl = new playlist

//isCatalog = false
if (!isCatalog) { //dont show embed buttons in catalog
  window.addEventListener('DOMContentLoaded', (event) => {

    const linkSelector = '.post-message a:not(.quote)'; //get links that arent quotes
    const supportedEmbeds = [
      {
        linkRegex: /^https?\:\/\/(?:www\.|m\.)?(?:youtube\.com|youtu\.?be)\//i,
        toEmbedder: (url) => {
          const urlObject = new URL(url);
          // urlObject.search is chrome 49 support
          var pairs = urlObject.search.substr(1).split(/&/)
          var searchParams = {}
          for(var i in pairs) {
            var pair = pairs[i]
            var parts = pair.split(/=/)
            searchParams[parts.shift()] = parts.join('=')
          }
          //const videoId = searchParams.get('v') || (urlObject.hostname === 'youtu.be' ? urlObject.pathname.substring(1) : null);
          const videoId = searchParams['v']
          //console.log('videoId', videoId)

          // it's not always 11 I think...
          if (videoId && videoId.length === 11) {
            return {
              // maybe these should be functions for maximum flexibility
              videoId: videoId,
              imageSafeUrl: '/.youtube/vi/' + videoId + '/default.jpg',
              imageHqUrl: '//img.youtube.com/vi/' + videoId + '/hqdefault.jpg',
              toElem: function(nextTrack) {
                var iframeElem = document.createElement('iframe')
                iframeElem.frameBorder = 0
                iframeElem.allow = "autoplay" // newer chrome needs this to autoplay
                iframeElem.src = 'https://www.youtube.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&enablejsapi=1'
                if (nextTrack) {
                  this.player = new YT.Player(iframeElem, { events: {
                    onReady: function(event) {
                      //console.log('ytapi ready')
                      //event.target.playVideo()
                    },
                    onError: function(e) {
                      console.error('err', e)
                    },
                    onStateChange: function(event) {
                      //console.log('ytapi state change', event)
                      // -1 ? loaded?
                      if (event.data === -1) {
                        // allow="autoplay"
                        console.warn('not autoplaying')
                        //event.target.playVideo()
                      }
                      // event.data = 1 playing/resume
                      // 2 pause
                      // 3 scrub
                      // 0 endded
                      if (event.data === 0) { // ended
                        nextTrack()
                      }
                    }
                  }, playerVars: { autoplay: 1 } });
                }
                return iframeElem
              }
            };
          } else {
            console.error('toEmbedder', videoId, 'length', videoId.length)
          }
        },
        toHtml: (url) => {
          try {
            const urlObject = new URL(url);
            // urlObject.search is chrome 49 support
            var pairs = urlObject.search.substr(1).split(/&/)
            var searchParams = {}
            for(var i in pairs) {
              var pair = pairs[i]
              var parts = pair.split(/=/)
              searchParams[parts.shift()] = parts.join('=')
            }
            //const videoId = searchParams.get('v') || (urlObject.hostname === 'youtu.be' ? urlObject.pathname.substring(1) : null);
            const videoId = searchParams['v']
            //console.log('videoId', videoId)
            if (videoId && videoId.length === 11) {
              //pl.addtracks('https://www.youtube.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&enablejsapi=1')
              return `<iframe class="embed-video" src="https://www.youtube.com/embed/${encodeURIComponent(videoId)}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" style="display:block;" allowfullscreen></iframe>`;
            }
          } catch (e) { /*invalid url*/ }
          return null;
        }
      },
      {
        linkRegex: /^https?\:\/\/(?:www\.)?bitchute\.com\/video\/[a-z0-9]{12}\//i,
        toVideoId: (url) => {
          return null;
        },
        toHtml: (url) => {
          try {
            const urlObject = new URL(url);
            const videoId = urlObject.pathname.split('/')[2];
            if (videoId) {
              return `<iframe class="embed-video" src="https://www.bitchute.com/embed/${encodeURIComponent(videoId)}/" frameborder="0" scrolling="no" style="display:block;" allowfullscreen></iframe>`;
            }
          } catch (e) { /*invalid url*/ }
          return null;
        }
      },
      //TODO: add more of these
    ];

    const toggleEmbed = (embedSpan, embedElem) => {
      if (embedSpan.dataset.open === 'true') {
        embedSpan.nextSibling.remove();
        embedSpan.firstElementChild.textContent = 'Embed';
      } else {
        //embedSpan.insertAdjacentHTML('afterend', embedHtml);
        embedSpan.parentNode.appendChild(embedElem)
        embedSpan.firstElementChild.textContent = 'Close';
      }
      embedSpan.dataset.open = embedSpan.dataset.open === 'true' ? 'false' : 'true';
    }

    const addEmbedButtons = (l) => {
      for (let i = 0; i < l.length; i++) {
        const embedHandler = supportedEmbeds.find(handler => handler.linkRegex.test(l[i].href));
        if (!embedHandler) {
          //console.log('not supported link', l[i].href)
          continue;
        }
        const embedder = embedHandler.toEmbedder(l[i].href)
        if (!embedder) {
          console.warn('toEmbedder failure, link', l[i].href)
          continue
        }
        addToPostMedia(l[i].parentNode.parentNode.parentNode.parentNode, embedder)
        pl.addtracks(embedder)
        //const embedHtml = embedHandler.toHtml(l[i].href);
        const embedElem = embedder.toElem()
        //console.log('embedHtml', embedHtml)
        if (embedElem) {
          const embedSpan = document.createElement('span');
          const openBracket = document.createTextNode('[');
          const embedLink = document.createElement('a');
          const closeBracket = document.createTextNode(']');
          embedSpan.classList.add('ml-5', 'noselect', 'bold');
          embedLink.classList.add('dummy-link');
          embedLink.textContent = 'Embed';
          embedSpan.appendChild(openBracket);
          embedSpan.appendChild(embedLink);
          embedSpan.appendChild(closeBracket);
          l[i].parentNode.insertBefore(embedSpan, l[i].nextSibling);
          embedLink.addEventListener('click', () => toggleEmbed(embedSpan, embedElem), false);
        }
      }
    };

    const links = Array.from(document.querySelectorAll(linkSelector));
    addEmbedButtons(links);

    var l = links
    var embeds = 0
    for (let i = 0; i < l.length; i++) {
      const embedHandler = supportedEmbeds.find(handler => handler.linkRegex.test(l[i].href));
      if (!embedHandler) {
        //console.log('not supported link', l[i].href)
        continue;
      }
      embeds++
    }
    if (embeds > 1) {
      console.log('enable playlist')
      pl.enableUI()
    }

    const updateEmbedLinks = (e) => {
      if (e.detail.hover) {
        return;
      }
      const newlinks = Array.from(e.detail.post.querySelectorAll(linkSelector))
        .filter(link => {
          //dont add for existing or during updatepostmessage
          return !(link.nextSibling
            && link.nextSibling.classList
            && link.nextSibling.classList.contains('dummy-link'));
        });
      addEmbedButtons(newlinks);
    }

    window.addEventListener('addPost', updateEmbedLinks);
    window.addEventListener('updatePostMessage', updateEmbedLinks);

  });
}
