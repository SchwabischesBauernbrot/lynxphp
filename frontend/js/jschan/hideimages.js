let imageSources = new Set(JSON.parse(localStorage.getItem('hiddenimages')));
let imageSourcesList = {};

const toggleAllHidden = (state) => imageSources.forEach(i => toggleSource(i, state));

const toggleSource = (source, hide) => {
  const images = document.querySelectorAll(`img.file-thumb[src="${source}"]`);
  //console.log('hiddenimages::toggleSource(', source, hide, ') - images', images)
  for(var i in images) {
    if (images.hasOwnProperty(i)) {
      const links = images[i].parentNode.parentNode.parentNode.parentNode.getElementsByClassName('hide-image')
      if (links && links.length) {
        const actionElem = links[0]
        actionElem.innerText = hide ? 'Show' : 'Hide'
      }
      // vh doesn't work on details or it's parent div
      images[i].classList[hide ? 'add' : 'remove']('vh')
    }
  }
}

// build UI
const actions = document.getElementsByClassName('jsMediaActions')
//console.log('actions', actions.length)
for(var i in actions) {
  if (actions.hasOwnProperty(i)) {
    const elem = actions[i]
    // go from post-file-info to post-file-src
    const filenames = elem.parentNode.parentNode.getElementsByClassName('file-thumb')
    const span = document.createElement('span')
    const b1 = document.createElement('b')
    b1.innerText = '['
    const b2 = document.createElement('b')
    b2.innerText = ']'
    const aElem = document.createElement('a')
    aElem.className = 'dummy-link hide-image'
    aElem.title = 'hide this media'
    aElem.innerText = 'Hide'
    if (filenames && filenames.length) {
      const filenameElem = filenames[0]
      //console.log('setting src', filenameElem.src, 'vs', imageSources)
      const hidden = imageSources.has(filenameElem.src)
      if (hidden) aElem.innerText = 'Show'
      aElem.dataset.src = filenameElem.src
    }
    span.appendChild(b1)
    span.appendChild(aElem)
    span.appendChild(b2)

    // data-src="{{thumbpath}}"
    //span.innerHTML = '<b>[</b><a class="dummy-link hide-image" title="hide this media">Hide</a><b>]</b>'

    elem.appendChild(span)
  }
}

//console.log('hiddenimages imageSources', imageSources)
toggleAllHidden(true);

const toggleHandler = (e) => {
  const thumbSource = e.target.dataset.src

  const hidden = imageSources.has(thumbSource);
  //console.log('hideimage::toggleHandler - start', hidden, e, thumbSource)
  imageSources[hidden?'delete':'add'](thumbSource);
  imageSourcesList.value = [...imageSources]; // convert set to array
  setLocalStorage('hiddenimages', JSON.stringify([...imageSources]));
  toggleSource(thumbSource, !hidden);
}

// activate [hide] links
const images = document.querySelectorAll('.hide-image')
for(var i in images) {
  if (images.hasOwnProperty(i)) {
    images[i].addEventListener('click', toggleHandler, false);
  }
}

const handleHiddenImages = (e) => {
  //console.log('handleHiddenImages', e.detail)
  //hide any images from this post that should already be hidden
  if (e.detail.json && e.detail.json.files) {
    const hasHiddenImages = e.detail.json.files.forEach(f => {
      if (imageSources.has(f.filename)) {
        toggleSource(f.filename, true);
      }
    });
  } else {
    // hover sometimes don't have images...
    console.warn('hideimages::handleHiddenImages - no files in JSON', e.detail)
  }
  //add the hide toggle link and event listener
  if (!e.detail.hover) {
    e.detail.post.querySelector('.hide-image').addEventListener('click', toggleHandler, false);
  }
}

window.addEventListener('addPost', handleHiddenImages, false);

window.addEventListener('settingsReady', () => {
  // is is the settings modal
  imageSourcesList = document.getElementById('hiddenimages-setting');
  imageSourcesList.value = [...imageSources];
  const imageSourcesListClearButton = document.getElementById('hiddenimages-clear');
  const clearImageSources = () => {
    toggleAllHidden(false);
    imageSources = new Set();
    imageSourcesList.value = '';
    setLocalStorage('hiddenimages', '[]');
    console.log('cleared hidden images list');
  }
  imageSourcesListClearButton.addEventListener('click', clearImageSources, false);

});
