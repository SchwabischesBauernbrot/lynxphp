window.addEventListener('DOMContentLoaded', (event) => {

  function hookMessageBox(messageBox) {
    const maxLength = messageBox.getAttribute('maxlength')
    const minLength = messageBox.getAttribute('minlength')
    let currentLength = messageBox.value.length

    //const messageBoxLabel = messageBox.previousSibling
    //const counter = document.createElement('small')
    //messageBoxLabel.appendChild(counter)

    const updateCounter = (counter) => {
      counter.innerText = `(${currentLength}/${maxLength})`
      if (currentLength >= maxLength || currentLength < minLength) {
        counter.style.color = 'red'
      } else {
        counter.removeAttribute('style')
      }
    }

    const updateCounters = () => {
      const counters = document.getElementsByClassName('messageCounter')
      for(var i in counters) {
        if (counters.hasOwnProperty(i)) {
          updateCounter(counters[i])
        }
      }
    }

    const updateState = function(e) {
      // normalize/validate input
      if (messageBox.value.length > maxLength) {
        messageBox.value = messageBox.value.substring(0, maxLength)
      }
      currentLength = messageBox.value.length

      const messageBoxes = document.querySelectorAll('textarea[name=message]')
      for(var i in messageBoxes) {
        if (messageBoxes.hasOwnProperty(i)) {
          // sync value
          messageBoxes[i].value = messageBox.value
        }
      }

      updateCounters()
    }

    if (maxLength || minLength) {
      updateCounters()
      messageBox.addEventListener('input', updateState)
    } else {
      //console.log('counter.js - no message length limits set')
    }
  }

  const messageBoxes = document.querySelectorAll('textarea[name=message]')
  //if (!messageBoxes.length) console.warn('counter.js - no message boxes')
  for(var i in messageBoxes) {
    if (messageBoxes.hasOwnProperty(i)) {
      hookMessageBox(messageBoxes[i])
    }
  }

})
