
// options to change the scoping limits
// maybe we can passed in a container element
function makeDomDraggable(clickElem, moveElem, options) {
  let isDragging = false
  let initialX, initialY, mouseX, mouseY
  let offsetX, offsetY
  let animationFrameId
  let h
  let parentRect

  // are we bound to viewport or the page
  if (options.bound !== 'viewport') {
    // bind to page
    moveElem.parentNode.style.position = "relative"
  }
  moveElem.style.position = "absolute"
  clickElem.style.cursor = 'grab'

  function startDrag_onmousedown(e) {
    isDragging = true
    //console.log('draggable::startDrag_onmousedown - start', moveElem.style.left, moveElem.style.top)
    var r = moveElem.getBoundingClientRect()
    initialX = moveElem.offsetLeft
    initialY = moveElem.offsetTop
    mouseX = e.clientX
    mouseY = e.clientY
    offsetX = e.clientX - r.left
    offsetY = e.clientY - r.top
    //console.log('draggable - client', e.clientX, e.clientY, 'move', r)
    clickElem.style.cursor = 'grabbing'
    parentRect = moveElem.parentNode.getBoundingClientRect()
    // marginLeft marginTop
  }

  function doc_onmouseup() {
    isDragging = false
    cancelAnimationFrame(animationFrameId)
    clickElem.style.cursor = 'grab'
    // reset zIndex
    
    // report relative position
    //let elementRect = moveElem.getBoundingClientRect()
    //console.log('draggable::doc_onmouseup - pos', moveElem.style.left, moveElem.style.top, 'initial', initialX, initialY)
    if (options.onmouseup) {
      options.onmouseup()
    }
  }

  function doc_onmousemove(e) {
    if (isDragging) {
      if (animationFrameId) cancelAnimationFrame(animationFrameId)
      animationFrameId = requestAnimationFrame(() => {
        if (options.bound === 'viewport') {
          let newX = e.clientX - offsetX
          let newY = e.clientY - offsetY
          const rect = moveElem.getBoundingClientRect()
          //console.log('rect', rect)
        
          if (newX < 0) newX = 0
          if (newY < 26) newY = 26
          if (newX + rect.width > window.innerWidth) newX = window.innerWidth - rect.width
          if ((newY + rect.height) > window.innerHeight - 41) newY = (window.innerHeight - 41) - rect.height

          moveElem.style.left = `${newX}px`
          moveElem.style.top = `${newY}px`
        } else {
          let deltaX = e.clientX - mouseX
          let deltaY = e.clientY - mouseY

          let newLeft = initialX + deltaX
          let newTop = initialY + deltaY
          
          // Boundary checks
          let elementRect = moveElem.getBoundingClientRect()
          
          if (newLeft < 0) newLeft = 0
          if (newTop < 0) newTop = 0
          if (newLeft + elementRect.width > parentRect.width) newLeft = parentRect.width - elementRect.width
          if (newTop + elementRect.height > parentRect.height) newTop = parentRect.height - elementRect.height


          moveElem.style.left = newLeft + 'px'
          moveElem.style.top = newTop + 'px'
        }
      })
    }
  }

  clickElem.addEventListener('mousedown', startDrag_onmousedown)
  document.addEventListener('mouseup', doc_onmouseup)
  document.addEventListener('mousemove', doc_onmousemove)
  //console.log('draggable is setup')
  return {
    cleanUp: () => {
      clickElem.removeEventListener('mousedown', startDrag_onmousedown)
      document.removeEventListener('mouseup', doc_onmouseup)
      document.removeEventListener('mousemove', doc_onmousemove)  
    }
  }
}