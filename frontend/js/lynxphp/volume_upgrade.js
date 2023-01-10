

window.addEventListener('DOMContentLoaded', () => {
  function check_syncState(muted) {
    if (muted) {
      // hide UI
      var containerElem = document.getElementById('volumeContainer')
      containerElem.style.display = 'none'
      console.log('volume_upgrade.js - set volume to muted')
      localStorage.setItem('volume', 0)
      return
    }
    // show UI
    var containerElem = document.getElementById('volumeContainer')
    containerElem.style.display = 'inline'
    var rangeElem = document.querySelector('#volumeContainer input')
    if (rangeElem.value === '0') {
      // since unchecking, we should set it
      rangeElem.value = 50
      rangeElem.onchange()
    }
    console.log('volume_upgrade.js - set volume to', rangeElem.value)
    localStorage.setItem('volume', rangeElem.value)
  }
  function check_onclick() {
    check_syncState(this.checked)
  }
  function buildUI() {
    // create all the things
    var checkboxElem = document.querySelector('input[name=mute]')
    var containerElem = document.createElement('span')
    containerElem.id = 'volumeContainer'
    var rangeElem = document.createElement('input')
    rangeElem.setAttribute('type', 'range')
    rangeElem.type = 'range'
    rangeElem.step = 1
    rangeElem.min = 0
    rangeElem.max = 100
    // ensure volume
    if (!localStorage.getItem('volume')) {
      // sync check with volume
      // nojs overrides js
      if (!rangeElem.value) {
        setLocalStorage('volume', 50)
      } else {
        setLocalStorage('volume', 0)
      }
    }
    rangeElem.value = localStorage.getItem('volume')
    var textElem = document.createElement('span')
    rangeElem.onchange = function() {
      console.log('volume_upgrade.js - set volume to', this.value)
      textElem.innerText = this.value
      localStorage.setItem('volume', this.value)
    }
    textElem.id = 'volumeLevel'
    textElem.innerText = rangeElem.value
    var perElem = document.createElement('span')
    perElem.innerText = '%'
    containerElem.appendChild(rangeElem)
    containerElem.appendChild(textElem)
    containerElem.appendChild(perElem)
    checkboxElem.parentNode.appendChild(containerElem)
    // needs the elements to exist
    console.log('volume_upgrade.js - build volume', rangeElem.value)
    // js could be zero and nojs be unchecked
    if (rangeElem.value === '0') {
      // we will sync this once and hide range
      checkboxElem.checked = true
      // not needed
      //check_syncState(true)
    }
  }
  if (!DISABLE_JS) {
    var checkboxElem = document.querySelector('input[name=mute]')
    if (checkboxElem) {
      buildUI()
      //console.log('volume_upgrade', checkbox)
      checkboxElem.addEventListener('click', check_onclick)
      check_syncState(checkboxElem.checked)
    }
    /*
    if (checkboxElem.checked) {
      console.log('volume_upgrade.js - muted')
    } else {
      //console.log('volume_upgrade.js - hooking')
    }
    */
  }
})