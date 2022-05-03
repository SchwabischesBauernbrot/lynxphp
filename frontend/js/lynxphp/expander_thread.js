function hookThreadExpander(summaryElem, url) {
  console.log('setting up expander', summaryElem, 'for', url)
  summaryElem.notLoaded = true
  summaryElem.onclick = function() {
    if (this.hasAttribute('open')) {
      //console.log('closing')
    } else {
      //console.log('opening')
      if (summaryElem.notLoaded) {
        // find iframe
        const iframe = summaryElem.querySelector('iframe')
        // remove contents
        iframe.removeAttribute('srcdoc')
        // trigger loading of new contents
        iframe.src = url
        // lock
        summaryElem.notLoaded = false
      }
    }
  }
}

// boards
if (typeof(boardsNav) !== 'undefined') {
  hookThreadExpander(boardsNav, 'boards_inline.html')
}

window.addEventListener('DOMContentLoaded', (event) => {
  const threadExpanders = document.getElementsByClassName('threadExpander')
  //if (!threadExpanders.length) console.warn('expander.js - no thread expanders')
  //console.log('expander.js - scanning for thread expanders', threadExpanders.length)
  for(var i in threadExpanders) {
    if (threadExpanders.hasOwnProperty(i)) {
      const summaryElem = threadExpanders[i]
      const iframe = summaryElem.querySelector('iframe')
      //console.log('iframe', iframe.contentDocument)
      const linkElem = iframe.contentDocument.getElementById('link')
      if (!linkElem) {
        setTimeout(function() {
          //console.log('trying again')
          const linkElem = iframe.contentDocument.getElementById('link')
          if (linkElem) {
            hookThreadExpander(summaryElem, linkElem.href)
          } else {
            console.warn('failed to hook', summaryElem)
          }
        }, 1000)
      } else {
        //console.log('linkElem', linkElem)
        hookThreadExpander(summaryElem, linkElem.href)
      }
    }
  }
})