// FIXME: theme color
// FIXME: reposition in center on reisze
// test on mobile

// options
// animations, draggable
// return close handle
function createModal(options = {}) {
  // Create modal container
  const modal = document.createElement('div')
  modal.id = 'custom-modal'
  modal.style.display = 'none'
  modal.style.position = 'fixed'
  modal.style.zIndex = '1'
  /*
  modal.style.left = '0'
  modal.style.top = '0'
  modal.style.width = '100%'
  modal.style.height = '100%'
  */
  modal.style.left = '50%'
  modal.style.top = '50%'
  //modal.style.overflow = 'auto'
  modal.style.width = '80%' // minimum when less than 700px
  modal.style.maxWidth = '700px' // maximum
  modal.style.maxHeight = '80vh'

  //modal.style.padding = '20px'
  modal.style.border = '1px solid #888'
  modal.style.boxShadow = '0 4px 8px 0 rgba(0,0,0,0.2)'
  modal.style.backgroundColor = '#fefefe'

  modal.style.userSelect = 'none'

  //modal.style.cursor = 'move'

  const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
  if (options.draggable && isTouchDevice) options.draggable = false

  if (options.animations) {
    // Add fade-in effect
    // Add slide and fade-in effect
    modal.style.transition = 'opacity 0.5s ease-in-out, transform 0.5s ease-in-out'
    //modal.style.transform = 'translateY(-30px)'
    modal.style.opacity = '0'
  }

  const modalShadow = document.createElement('div')
  modalShadow.style.zIndex = '0'
  modalShadow.style.width = '100%'
  modalShadow.style.height = '100%'
  modalShadow.style.backgroundColor = 'rgba(0, 0, 0, 0.4)'

  // Create a header for the modal
  const modalHeader = document.createElement('div')
  modalHeader.style.padding = '0 10px'
  modalHeader.style.cursor = 'grab'
  modalHeader.style.backgroundColor = '#f3f3f3' // Example styling
  modalHeader.style.borderBottom = '1px solid #ddd' // Example styling

  // Create modal content
  const modalContent = document.createElement('div')
  // 15% 
  modalContent.style.margin = 'auto'
  modalContent.style.padding = '20px'
  //modalContent.style.border = '1px solid #888'
  modalContent.style.width = '80%'
  modalContent.style.maxWidth = '600px'
  modalContent.style.maxHeight = '80vh' // Adjust as needed
  modalContent.style.overflowY = 'auto'
  modalContent.style.userSelect = 'text'

  // Create close button
  const closeButton = document.createElement('span')
  closeButton.innerHTML = '&times;'
  closeButton.style.color = '#aaa'
  closeButton.style.float = 'right'
  closeButton.style.fontSize = '28px'
  closeButton.style.fontWeight = 'bold'
  closeButton.style.cursor = 'pointer'

  // Tooltip for Close button
  const tooltip = document.createElement('span')
  //tooltip.className = 'tooltip'
  tooltip.textContent = 'Press ESC to close'
  tooltip.style.visibility = 'hidden'
  tooltip.style.width = '120px'
  tooltip.style.backgroundColor = 'black'
  tooltip.style.color = '#fff'
  tooltip.style.textAlign = 'center'
  tooltip.style.borderRadius = '6px'
  tooltip.style.padding = '5px 0'
  tooltip.style.position = 'absolute'
  tooltip.style.zIndex = '1'
  tooltip.style.bottom = '100%'
  tooltip.style.left = '50%'
  tooltip.style.marginLeft = '-60px'
  tooltip.style.opacity = '0'
  tooltip.style.transition = 'opacity 0.3s'
  closeButton.appendChild(tooltip)

  closeButton.onmouseover = () => {
    tooltip.style.visibility = 'visible'
    tooltip.style.opacity = '1'
    closeButton.style.color = 'black' // hover
  }

  closeButton.onmouseout = () => {
    tooltip.style.visibility = 'hidden'
    tooltip.style.opacity = '0'
    closeButton.style.color = '#aaa' // hover
  }

  /*
  // Create text container
  const textContainer = document.createElement('p')
  textContainer.id = 'modal-text'

  // Create button container
  const buttonContainer = document.createElement('div')
  buttonContainer.style.textAlign = 'center'
  buttonContainer.style.marginTop = '20px'

  // Create button
  const button = document.createElement('button')
  button.id = 'modal-button'
  button.style.padding = '10px 20px'
  button.style.cursor = 'pointer'
  button.style.marginTop = '20px'
  button.onmouseover = () => button.style.backgroundColor = '#eaeaea'
  button.onmouseout = () => button.style.backgroundColor = ''
  button.onmousedown = () => button.style.backgroundColor = '#cacaca'
  button.onmouseup = () => button.style.backgroundColor = '#eaeaea'
  */

  // Append elements
  //buttonContainer.appendChild(button) // Append button to the container
  //modalContent.appendChild(textContainer)
  //modalContent.appendChild(buttonContainer)
  var clearElem = document.createElement('br')
  clearElem.clear = "both"
  modalHeader.appendChild(closeButton)
  modalHeader.appendChild(clearElem)
  modal.appendChild(modalHeader)
  modal.appendChild(modalContent)
  document.body.appendChild(modal)

  // Make the modal draggable
  var dragHandle
  if (options.draggable) {
    dragHandle = makeDomDraggable(modalHeader, modal, { bound: 'viewport' })
  }

  // Close functionality
  function closeModal () {
    if (options.animations) {
      modal.style.opacity = '0'
      modal.style.transform = 'translateY(-30px)'
      setTimeout(() => {
        modal.style.display = 'none'
      }, 500) // Match this delay to the CSS fade-out duration
    } else {
      modal.style.display = 'none'
    }
    if (options.draggable) {
      dragHandle.cleanUp()
    }
    document.removeEventListener('keydown', handleEscape)
    window.removeEventListener('click', win_onclick)
  }
  closeButton.onclick = closeModal

  // background clicks
  function win_onclick (event) {
    if (event.target === modal) closeModal()
  }
  //window.addEventListener('click', win_onclick)

  function adjustModalSize() {
    const viewportWidth = window.innerWidth
    const viewportHeight = window.innerHeight
    
    // allow more content, less padding on smaller screens
    if (viewportWidth < 700) {
      modal.style.width = '95%'
      modal.style.maxWidth = '95%'
    } else {
      modal.style.width = '80%'
      modal.style.maxWidth = '700px'
    }
  
    if (viewportHeight < 500) {
      modal.style.maxHeight = '95vh'
    } else {
      modal.style.maxHeight = '80vh'
    }

    // recenter?
  }  
  window.addEventListener('resize', adjustModalSize)

  // Close functionality with Escape key
  function handleEscape (event) {
    if (event.key === 'Escape') {
      closeModal()
    }
  }
  document.addEventListener('keydown', handleEscape)  

  modal.close = closeModal
  modal.modalContent = modalContent
  modal.hasAnimations = options.animations
  return modal
}

function setContent(modal, contentElements) {
  // maybe identify first tabstop
  for(const c in contentElements) {
    // layout information? maybe it's a layout container? block vs inline
    // grid or justified rows
    modal.modalContent.appendChild(contentElements[c])
  }
  // select first tabstop
  // Display modal
  if (modal.hasAnimations) {
    modal.style.display = 'block'
    requestAnimationFrame(() => {
      modal.style.opacity = '1'
      // translate(-50%, -50%)
      modal.style.transform = 'translateY(0)'
    })
  }

  var leftpixel = window.innerWidth / 2 - (modal.offsetWidth / 2)
  var toppixel = window.innerHeight / 2 - (modal.offsetHeight / 2)

  modal.style.left = `${leftpixel}px` 
  modal.style.top = `${toppixel}px`

  const focusableElements = modal.modalContent.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
  const firstFocusableElement = focusableElements[0]
  const lastFocusableElement = focusableElements[focusableElements.length - 1]

  modal.addEventListener('keydown', function(e) {
    const isTab = (e.key === 'Tab' || e.keyCode === 9)

    if (!isTab) {
      return
    }

    if (e.shiftKey) {
      // if Shift + Tab
      if (document.activeElement === firstFocusableElement) {
        lastFocusableElement.focus()
        e.preventDefault()
      }
    } else {
      // if Tab
      if (document.activeElement === lastFocusableElement) {
        firstFocusableElement.focus()
        e.preventDefault()
      }
    }
  })

}

function clearContent(modal) {
  // Clear previous content except the close button
  while (modal.modalContent.children.length > 1) {
    modal.modalContent.removeChild(modal.modalContent.lastChild)
  }
}

function createCenteredButton(text, onClick) {
  const button = document.createElement('button')
  button.textContent = text
  button.style.margin = '0 auto'
  button.style.display = 'block'
  button.style.cursor = 'pointer'

  // Focus styles
  button.onfocus = function() {
    button.style.outline = '2px solid blue' // Example focus style
    button.style.backgroundColor = '#eaeaea' // Example focus style
  }

  button.onblur = function() {
    button.style.outline = 'none'
    button.style.backgroundColor = '' // Revert to original style
  }

  button.onclick = onClick
  // Additional button styles
  return button
}

function createCenteredText(text) {
  const textElement = document.createElement('p')
  textElement.textContent = text
  textElement.style.textAlign = 'center'
  // Additional text styles
  return textElement
}

// Example usage:
// showModal("This is a custom message", { buttonText: "Go to Google", redirectUrl: "https://www.google.com" })
// showModal("This is another message") // Default 'OK' button with no redirection
function showModal(message, options = {}) {
  /*
  let modal = document.getElementById('custom-modal')
  if (!modal) {
    modal = createModal()
  }
  */
  var modal = document.getElementById('custom-modal') || createModal({ draggable: true, animations: true })

  var text = createCenteredText(message)
  function okHandler() {
    if (options.redirectUrl) {
      text.textContent = 'Loading...' // Update message to show loading state
      setTimeout(() => window.location.href = options.redirectUrl, 500) // allow them to read the message above
    } else {
      modal.close()
    }
  }
  var ok = createCenteredButton(options.buttonText || 'OK', okHandler)
  setContent(modal, [text, ok])
  ok.focus()

  /*
  // Set message
  const text = modal.querySelector('#modal-text')
  text.textContent = message

  // Configure button
  const button = modal.querySelector('#modal-button')
  button.textContent = options.buttonText || 'OK'

  // Button click action
  button.onclick = function() {
    if (options.redirectUrl) {
      text.textContent = 'Loading...' // Update message to show loading state
      window.location.href = options.redirectUrl
    } else {
      modal.style.display = 'none'
    }
  }
  */
}

//showModal("This works")