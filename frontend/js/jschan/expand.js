window.addEventListener('DOMContentLoaded', (event) => {

  const actionFooter = document.querySelector('summary.toggle-summary');
  if (actionFooter) {
    actionFooter.onclick = () => {
      actionFooter.scrollIntoView();
    }
  }

  // grab from settings
  const volumeSetting = document.getElementById('volume-setting');
  let volumeLevel = localStorage.getItem('volume')
  if (volumeLevel === 'undefined' || volumeLevel === '') volumeLevel = 50
  //console.debug('expand.js - volumeLevel', volumeLevel)
  if (volumeSetting) {
    const changeVolume = (change) => {
      volumeLevel = volumeSetting.value;
      const allMedia = document.querySelectorAll('audio,video');
      for (let i = 0; i < allMedia.length; i++) {
        allMedia[i].volume = parseInt(volumeLevel)/100;
      }
      console.log('adjusting default volume', volumeLevel);
      setLocalStorage('volume', volumeLevel);
    }
    volumeSetting.value = volumeLevel;
    volumeSetting.addEventListener('change', changeVolume, false);
  }

  // video vs audio
  let loopEnabled = true // default
  /*
  const loopSetting = document.getElementById('loop-setting');
  let loopEnabled = localStorage.getItem('loop') == 'true';
  if (loopSetting) {
    const toggleLoop = (change) => {
      loopEnabled = loopSetting.checked;
      console.log('toggling video/audio looping', loopEnabled);
      setLocalStorage('loop', loopEnabled);
    }
    loopSetting.checked = loopEnabled;
    loopSetting.addEventListener('change', toggleLoop, false);
  }
  */

  const imageloadingbarsSetting = document.getElementById('imageloadingbars-setting');
  let imageloadingbarsEnabled = localStorage.getItem('imageloadingbars') == 'true';
  if (localStorage.getItem('imageloadingbars') === 'undefined') imageloadingbarsEnabled = 'true'
  if (imageloadingbarsSetting) {
    const toggleImageloadingbars = (change) => {
      imageloadingbarsEnabled = imageloadingbarsSetting.checked;
      console.log('toggling video/audio imageloadingbarsing', imageloadingbarsEnabled);
      setLocalStorage('imageloadingbars', imageloadingbarsEnabled);
    }
    imageloadingbarsSetting.checked = imageloadingbarsEnabled;
    imageloadingbarsSetting.addEventListener('change', toggleImageloadingbars, false);
  }

  if (!isCatalog) { //dont expand on catalog
    const thumbs = document.getElementsByClassName('post-file-src');
    const toggle = function(thumb, expanded, filename, src) {
      if (thumb.style.display === 'none') { //closing
        //console.log('expand.js - closing', thumb, expanded, filename)
        thumb.style.display = '';
        expanded.style.display = 'none';
        filename.style.maxWidth = '';
      } else { //expanding
        //console.log('expand.js - expanding', thumb, expanded, filename)
        thumb.style.display = 'none';
        expanded.style.display = '';
        if (expanded.offsetWidth >= filename.offsetWidth) {
          filename.style.maxWidth = expanded.offsetWidth+'px';
        }
      }

      // should be flexible enough for css/design changes
      const postFileSrcElem = thumb.closest('.post-file-src')
      let videoRealSize = false
      if (postFileSrcElem) {
        const nojsVideoElem = postFileSrcElem.querySelector('video')
        if (nojsVideoElem) {
          // load nojs setting

          //console.log('nojsVideoElem', nojsVideoElem, nojsVideoElem.loop, nojsVideoElem.muted)
          videoRealSize = [nojsVideoElem.width, nojsVideoElem.height]
          loopEnabled = nojsVideoElem.loop
        }
      }
      //handle css thing for play icon on vid/audio
      const close = thumb.nextSibling.textContent === '[Close]' ? thumb.nextSibling : null;
      //console.log('expand.js - close', close)
      if (close) {
        expanded.loop = loopEnabled;
        expanded.volume = volumeLevel/100;
        if (videoRealSize) {
          // but these are capped by viewport
          expanded.width = videoRealSize[0]
          expanded.height = videoRealSize[1]
        }
        //console.debug('expand.js - volume set to', expanded.volume)
        if (src.style.visibility === 'hidden') {
          src.style.visibility = 'visible';
          close.style.display = 'none';
          expanded.pause();
        } else {
          src.style.visibility = 'hidden';
          close.style.display = 'block';
          expanded.play();
        }
      }
    }

    const expand = function(e) {
      if (e.target.nodeName === 'VIDEO' || e.target.nodeName === 'AUDIO') {
        e.stopPropagation();
        return;
      }
      if (this.dataset.attachment == 'true') {
        return;
      }
      const fileAnchor = this.querySelector('a.jsonly')
      //console.log('fileAnchor', fileAnchor)

      // handle if subsystem is off
      if (!fileAnchor) return

      e.preventDefault();
      //console.log('this', this.children)
      //const fileAnchor = this.children[0];

      const fileHref = fileAnchor.href;
      const type = this.dataset.type;
      //const thumbElement = fileAnchor.children[0];
      const thumbElement = fileAnchor.querySelector('img')

      //console.log('thumbElement', thumbElement)
      //const fileName = this.previousElementSibling;
      const fileName = this.parentNode.querySelector('.filename')
      //console.log('fileName', fileName)
      const pfs = this.closest('.post-file-src');
      let expandedElement = type === 'img' ? thumbElement.nextElementSibling : fileAnchor.nextElementSibling;
      //console.log('expandedElement', expandedElement, 'type', type)

      if (expandedElement) {
        toggle(thumbElement, expandedElement, fileName, pfs);
      } else if (thumbElement.style.opacity !== '0.5') {
        let source;
        switch(type) {
          case 'img':
            e.preventDefault();
            fileAnchor.style.minWidth = fileAnchor.offsetWidth+'px';
            fileAnchor.style.minHeight = fileAnchor.offsetHeight+'px';
            thumbElement.style.opacity = '0.5';
            thumbElement.style.cursor = 'wait'
            // why are we loading this instead of using imageloadingbarsEnabled?
            if (localStorage.getItem('imageloadingbars') === 'true' || localStorage.getItem('imageloadingbars') === 'undefined') {
              //console.log('doing bars')
              const request = new XMLHttpRequest();
              request.onprogress = (e) => {
                const progress = Math.floor((e.loaded/e.total)*100);
                const progressWidth = Math.floor((e.loaded/e.total)*thumbElement.offsetWidth);
                //console.log('onprogress', progress, progressWidth)
                if (progress >= 100) {
                  pfs.removeAttribute('data-loading');
                } else {
                  // probably doesn't work in chrome49...
                  pfs.setAttribute('data-loading', progress);
                  pfs.style = `--data-loading: ${progressWidth}px`;
                }
              }
              expandedElement = document.createElement('img');
              source = expandedElement;
              const loaded = function(e) {
                pfs.removeAttribute('data-loading');
                pfs.removeAttribute('style');
                const blob = this.response;
                source.src = window.URL.createObjectURL(blob);
                source.alt = "image for " + fileHref
                thumbElement.style.opacity = '';
                thumbElement.style.cursor = '';
                fileAnchor.appendChild(expandedElement);
                toggle(thumbElement, expandedElement, fileName, pfs);
              }
              request.onload = loaded;
              request.responseType = 'blob';
              request.open('GET', fileHref, true);
              request.send(null);
            } else {
              expandedElement = document.createElement('img');
              source = expandedElement;
              source.onload = function() {
                thumbElement.style.opacity = '';
                thumbElement.style.cursor = '';
                fileAnchor.appendChild(expandedElement);
                toggle(thumbElement, expandedElement, fileName, pfs);
              }
              source.alt = "image for " + fileHref
              source.src = fileHref;
            }
            break;
          case 'video':
          case 'audio':
            e.preventDefault();
            console.debug(type + ' media loading')
            thumbElement.style.opacity = '0.5';
            thumbElement.style.cursor = 'wait'

            expandedElement = document.createElement(type);
            // expandedElement.onloadeddata
            expandedElement.addEventListener('loadeddata', function() {
              console.debug(type + ' media loaded')
              thumbElement.style.opacity = '';
              thumbElement.style.cursor = '';
            })
            const closeSpan = document.createElement('span');
            const openBracket = document.createTextNode('[');
            const closeLink = document.createElement('a');
            const closeBracket = document.createTextNode(']');
            closeSpan.classList.add('noselect', 'bold');
            closeSpan.style.marginBottom = '3px';
            closeSpan.style.display = 'block';
            closeSpan.style.color = 'var(--font-color)';
            closeLink.classList.add('dummy-link');
            closeLink.textContent = 'Close';
            closeSpan.appendChild(openBracket);
            closeSpan.appendChild(closeLink);
            closeSpan.appendChild(closeBracket);
            closeSpan.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              toggle(thumbElement, expandedElement, fileName, pfs);
            }, true);
            expandedElement.controls = 'true';
            expandedElement.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              toggle(thumbElement, expandedElement, fileName, pfs);
            }, true)
            source = document.createElement('source');
            expandedElement.appendChild(source);
            if (type === 'audio' && thumbElement.nodeName === 'IMG') {
              expandedElement.style.backgroundImage =
                `url("${encodeURI(thumbElement.src)}")`;
              expandedElement.style.backgroundRepeat = 'no-repeat';
              expandedElement.style.backgroundPosition = 'top';
              expandedElement.style.backgroundSize = 'contain';
              expandedElement.style.minWidth = thumbElement.width+'px';
              expandedElement.style.paddingTop = thumbElement.height+'px';
              source.alt = "audio for " + fileHref
            } else {
              // FIXME: we need to set an expect w/h to prevent reflow
              // because the file takes some time to load
              // we could keep it the same as the thumbnail
              // until we get the metadata and then resize to what we need
              // wouldn't that also trigger a reflow

              //expandedElement.style.width = fileAnchor.offsetWidth+'px';
              //expandedElement.style.height = fileAnchor.offsetHeight+'px';
              // just inform what we know for now
              // reflow if needed as I don't think it can be avoided
              // UNTIL we know the final size...
              // which is in the span & details tag
              //expandedElement.style.minWidth = fileAnchor.offsetWidth+'px';
              //expandedElement.style.minHeight = fileAnchor.offsetHeight+'px';
              source.alt = "video for " + fileHref
            }
            pfs.appendChild(expandedElement);
            fileAnchor.appendChild(closeSpan);
            toggle(thumbElement, expandedElement, fileName, pfs);
            source.src = fileHref;
            break;
          default:
            console.warn('unknown type', type)
            return;
        }
      } else {
        console.warn('opacity isnt right?')
      }
    };

    const addExpandEvent = (t) => {
      for (let i = 0; i < t.length; i++) {
        t[i].addEventListener('click', expand, false);
      }
    }

    addExpandEvent(thumbs);

    window.addEventListener('addPost', function(e) {
      if (e.detail.hover) {
        return; //dont need to handle hovered posts for this
      }
      const post = e.detail.post;
      const newthumbs = post.getElementsByClassName('post-file-src');
      addExpandEvent(newthumbs);
    });
  }

});
