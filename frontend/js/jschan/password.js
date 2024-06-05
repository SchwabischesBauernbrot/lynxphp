const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+/'
const generatePassword = () => {
  if (window.crypto) {
    const buf = new Uint8Array(20) //8 keeps charcodes within range
    window.crypto.getRandomValues(buf)
    return btoa(String.fromCharCode.apply(null, buf))
  } else {
    return new Array(20)
      .fill(null)
      .map(x => charset[Math.floor(Math.random()*charset.length)])
      .join('')
  }
}

setDefaultLocalStorage('postpassword', generatePassword())

class syncedField {
  // oneWay isn't used
  constructor(selector, key, oneWay=false, persistent=true) {
    this.fields = []
    this.selector = selector
    this.key = key
    this.oneWay = oneWay
    this.persistent = persistent
    this.init()
    // listener
    if (persistent) {
      window.addEventListener('formReset', (event) => {
        //console.log('on formReset', key)
        localStorage.removeItem(key)
      })
    }
  }
  init() {
    /*
    const settingsModal = document.getElementById('settingsmodal')
    let settingsFields = []
    if (settingsModal) {
      settingsFields = settingsModal.querySelectorAll(this.selector)
    } else {
      console.log('password.js - settingsmodal is missing')
    }

    for(var i in settingsFields) {
      if (settingsFields.hasOwnProperty(i)) {
        this.fields.push(settingsFields[i])
      }
    }
    */

    const postForm = document.getElementById('postform')
    if (postForm) {
      const postformFields = postForm.querySelectorAll(this.selector)
      for(var i in postformFields) {
        if (postformFields.hasOwnProperty(i)) {
          this.fields.push(postformFields[i])
        }
      }
    } else {
      // non-quick-reply version
      const topPostForm = document.getElementById('top_postform')
      if (topPostForm) {
        const topPostformFields = topPostForm.querySelectorAll(this.selector)
        for(var i in topPostformFields) {
          if (topPostformFields.hasOwnProperty(i)) {
            this.fields.push(topPostformFields[i])
          }
        }
      } else {
        console.log('password.js - has no postform or top_postform?')
      }
    }

    const bottomPostForm = document.getElementById('bottom_postform')
    if (bottomPostForm) {
      const bottomPostformFields = bottomPostForm.querySelectorAll(this.selector)
      for(var i in bottomPostformFields) {
        if (bottomPostformFields.hasOwnProperty(i)) {
          this.fields.push(bottomPostformFields[i])
        }
      }
    } else {
      console.log('password.js - has no bottom_postform')
    }

    // this is a post list
    // so I think this is for saving the checkboxes but
    // nothing clears that
    /*
    const actionForm = document.getElementById('actionform')
    if (actionForm) {
      const actionFields = actionForm.querySelectorAll(this.selector)
      for(var i in actionFields) {
        if (actionFields.hasOwnProperty(i)) {
          this.fields.push(actionFields[i])
        }
      }
    } else {
      console.log('password.js - has no actionform')
    }
    */

    if (this.oneWay) {
      settingsFields[0].addEventListener('input', (e) => { this.update(e) }, false)
    } // else two way (default)

    if (!this.fields.length) {
      // ok not to have the password field when logged in
      console.log('password.js - has no fields', this.key)
    }
    //console.log('password.js - fields', this.fields)

    // sync field data with UI
    for (let field of this.fields) {
      field.value = localStorage.getItem(this.key) // load
      // updateUI to new value
      !this.oneWay && field.addEventListener('input', (e) => { this.update(e) }, false)
    }
  }
  update(e) {
    if (this.persistent) {
      // save the value to local storage
      //console.log('syncedField - setting', this.key, 'to', e.target.value)
      setLocalStorage(this.key, e.target.value)
    }
    // sync the fields
    for (let field of this.fields) {
      field.value = e.target.value
    }
  }
}

//window.addEventListener('settingsReady', () => {
window.addEventListener('DOMContentLoaded', (event) => {
  //console.log('password.js - start')
  new syncedField('input[name="name"]', 'name')
  new syncedField('input[name="email"]', 'email')
  // sage?
  new syncedField('input[name="subject"]', 'subject')
  new syncedField('textarea[name="message"]', 'message')
  // files?
  new syncedField('input[name="postpassword"]', 'postpassword')
})
