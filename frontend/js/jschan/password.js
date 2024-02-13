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
  constructor(selector, key, oneWay=false, persistent=true) {
    this.fields = []
    this.selector = selector
    this.key = key
    this.oneWay = oneWay
    this.persistent = persistent
    this.init()
  }
  init() {
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

    const postForm = document.getElementById('postform')
    if (postForm) {
      const postformFields = postForm.querySelectorAll(this.selector)
      for(var i in postformFields) {
        if (postformFields.hasOwnProperty(i)) {
          this.fields.push(postformFields[i])
        }
      }
    } else {
      console.log('password.js - has no postForm')
    }

    const bottomPostForm = document.getElementById('bottom_postform')
    if (postForm) {
      const bottomPostformFields = bottomPostForm.querySelectorAll(this.selector)
      for(var i in bottomPostformFields) {
        if (bottomPostformFields.hasOwnProperty(i)) {
          this.fields.push(bottomPostformFields[i])
        }
      }
    } else {
      console.log('password.js - has no bottom_postform')
    }

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

    if (this.oneWay) {
      settingsFields[0].addEventListener('input', (e) => { this.update(e) }, false)
    } // else two way (default)

    if (!this.fields.length) {
      console.log('password.js - has no fields', this.key)
    }
    console.log('password.js - fields', this.fields)
    for (let field of this.fields) {
      field.value = localStorage.getItem(this.key)
      !this.oneWay && field.addEventListener('input', (e) => { this.update(e) }, false)
    }
  }
  update(e) {
    if (this.persistent) {
      //console.log('syncedField - setting', this.key, 'to', e.target.value)
      setLocalStorage(this.key, e.target.value)
    }
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
