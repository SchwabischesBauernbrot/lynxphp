const modalClasses = ['modal', 'modal-bg'];
function removeModal() {
  modalClasses.forEach(c => document.getElementsByClassName(c)[0].remove());
}

function pad(n, width, z) {
  z = z || '0';
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

function doModal(data, postcallback) {
  //console.log('doModal', data)
  try {
    // modal isn't defined
    const modalHtml = modal({ modal: data });
    let checkInterval;
    document.body.insertAdjacentHTML('afterbegin', modalHtml);
    document.getElementById('modalclose').onclick = () => {
      removeModal();
      clearInterval(checkInterval);
    };
    document.getElementsByClassName('modal-bg')[0].onclick = () => {
      removeModal();
      clearInterval(checkInterval);
    };
    const modalframe = document.getElementById('modalframe');
    if (modalframe) {
      //if theres a modal frame and user has default theme, style it
      if (localStorage.getItem('theme') === 'default') {
        modalframe.onload = () => {
          const currentTheme = document.head.querySelector('#theme').href;
          modalframe.contentDocument.styleSheets[1].ownerNode.href = currentTheme;
        }
      }
      if (postcallback) {
        checkInterval = setInterval(() => {
          if (modalframe && modalframe.contentDocument.title == 'Success') {
            clearInterval(checkInterval);
            removeModal();
            postcallback();
          }
        }, 100);
      }
    }
  } catch(e) {
    console.error(e)
  }
}

const checkTypes = ['checkbox', 'radio'];
function isCheckBox(element) {
  return checkTypes.includes(element.type)
}

function formToJSON(form) {
  const data = {};
  for (element of form.elements) {
    if (element.name /*&& element.value*/ && (!isCheckBox(element) || element.checked)) {
      if (isCheckBox(element) && data[element.name]) {
        if (Array.isArray(data[element.name])) {
          data[element.name] = data[element.name].push(element.value);
        } else {
          data[element.name] = [data[element.name], element.value];
        }
      } else {
        data[element.name] = element.value;
      }
    }
  }
  return JSON.stringify(data);
}

let recaptchaResponse = null;
function recaptchaCallback(response) {
  recaptchaResponse = response;
}
class formHandler {

  constructor(form) {
    this.form = form;
    console.log('forms.js - enhancing', form)
    this.enctype = this.form.getAttribute('enctype');
    this.messageBox = form.querySelector('#message');
    this.captchaField = form.querySelector('.captchafield') || form.querySelector('.g-recaptcha') || form.querySelector('.h-captcha');
    this.submit = form.querySelector('input[type="submit"]');
    if (this.submit) {
      this.originalSubmitText = this.submit.value;
    }
    // what's minimal view?
    // has something do with a nojs block bypass in jschan
    this.minimal = this.form.elements.minimal;
    this.files = [];
    this.fileInput = form.querySelector('input[type="file"]');
    if (this.fileInput) {
      //console.log('fileInput', this.fileInput)
      this.fileRequired = this.fileInput.required;
      // input should be inside the label?
      this.fileLabel = this.fileInput.parentNode;
      if (!this.fileLabel) {
        console.warn('fileLabel is missing')
      }
      this.fileUploadList = this.fileLabel.parentNode.querySelector('.upload-list');
      if (!this.fileUploadList) {
        console.warn('fileUploadList is missing')
      }
      //console.log('fileLabel', this.fileLabel)
      //console.log('fileUploadList', this.fileUploadList)
      //
      //this.multipleFiles = this.fileLabel.parentNode.previousSibling.firstChild.textContent.endsWith('s[]');
      this.multipleFiles = this.fileLabel
      //this.fileLabelText = this.fileLabel.childNodes[0];
      this.fileLabelText = this.fileLabel.querySelector('.fileLabelText')
      this.fileLabel.addEventListener('dragover', e => this.fileLabelDrag(e));
      this.fileLabel.addEventListener('drop', e => this.fileLabelDrop(e));
      this.fileInput.addEventListener('change', e => this.fileInputChange(e));
      // middle click clears files
      this.fileLabel.addEventListener('auxclick', e => this.fileLabelAuxclick(e));
    }
    this.messageBox && this.messageBox.addEventListener('keydown', e => this.controlEnterSubmit(e));
    form.addEventListener('paste', e => this.paste(e));
    form.addEventListener('submit', e => this.formSubmit(e));
    // just add .json
    // why? could do it around line 621...
    //this.form.setAttribute('action', this.form.getAttribute('action') + '.json')
  }

  reset() {
    const savedName = this.form.elements.name && this.form.elements.name.value;
    this.form.reset();
    if (this.form.elements.name) {
      this.form.elements.name.value = savedName
    }
    if (this.form.elements.postpassword) {
      this.form.elements.postpassword.value = localStorage.getItem('postpassword');
    }
    this.updateMessageBox();
    this.files = [];
    this.updateFilesText();
    const captcha = this.form.querySelector('.captcharefresh');
    if (captcha) {
      captcha.dispatchEvent(new Event('click'));
    }
    // ensure it's unlocked
    this.setFormLock(false)
  }

  controlEnterSubmit(e) {
    if (e.ctrlKey && e.key === 'Enter') {
      this.formSubmit(e);
    }
  }

  setFormLock(s) {
    this.form.elements.name.disabled = s
    this.form.elements.email.disabled = s
    this.form.elements.sage.disabled = s
    this.form.elements.subject.disabled = s
    this.form.elements.message.disabled = s
    this.fileInput.disabled = s
    this.submit.disabled = s
  }

  // only submits one file
  formSubmit(e) {
    this.setFormLock(true) // lock this fucker

    // do we require capctha?
    // https://dev.wrongthink.net/test/post
    const u = new URL(this.form.action)
    const parts = u.pathname.split('/')
    const boardUri = parts[1]
    //console.log('board', boardUri, 'settings', boardData[boardUri])

    // FIXME: move out to module...
    let captchaEnable = false
    if (boardData[boardUri] && boardData[boardUri].captcha_mode) {
      //console.log('CAPTCHA mode:', boardData[boardUri].captcha_mode)
      if (boardData[boardUri].captcha_mode === 'posts') {
        captchaEnable = true
      } else
      if (boardData[boardUri].captcha_mode === 'threads') {
        // or maybe read the button?
        // are we in a thread?
        const loc = whereAmI()
        if (!loc.threadNum) {
          captchaEnable = true
        }
      }
      // else 'no' just leave as false
    }
    //console.log('does this board require CAPTCHA?', captchaEnable)
    if (captchaEnable) {
      if (!this.inCaptcha) {
        // prompt user for captcha
        // UI decisions?
        this.inCaptcha = 1
        // lock form, insert captcha until it's fine
        var container = document.createElement('section')
        container.className = "row captchaRow"
        // get fresh captcha
        // we need img.src, fieldname _id for the captcha id
        container.innerHTML = '<span class="col"><img class="captcha" src="images/awaiting_thumbnail.png"><input style="font-size: 2em;" type=text maxlength=6 size=6 name="captcha"></span>' +
          '<span class="col"><button class="active-captcha-reload">reload</button><button class="active-captcha-cancel">cancel</button></span>'
        // insert before the submit button
        this.submit.parentNode.insertBefore(container, this.submit)
        //this.form.appendChild(container)

        const cInputElem = this.form.querySelector('.col input[name=captcha]')
        const img = this.form.querySelector('.col img.captcha')
        var ref = this
        function reloadCaptcha() {
          //console.log('CAPTCHA loading')
          img.src = 'images/awaiting_thumbnail.png'
          fetch('/CAPTCHA/json').then(function(response) {
            return response.json()
          }).then(function(data) {
            //console.log('captcha data', data)
            img.src = 'data:image/jpeg;base64, ' + data.img
            // save id somewhere
            ref.captchaId = data.id
            cInputElem.value = ''
            cInputElem.focus()
            //console.log('data.ex', data.ex, 'seconds')
            ref.captchaRefreshTimer = setTimeout(function() {
              reloadCaptcha()
            }, data.ex * 1000)
          })
        }
        reloadCaptcha()
        const rlButElem = this.form.querySelector('.col button.active-captcha-reload')
        rlButElem.onclick = function() {
          reloadCaptcha()
          return false;
        }
        const cxlButElem = this.form.querySelector('.col button.active-captcha-cancel')
        cxlButElem.onclick = function() {
          // remove captcha rows
          /*
          var rows = document.querySelectorAll('.col.captcha')
          for (var j = rows.length-1; j >= 0; j--) {
            if (rows[j].parentNode) {
              rows[j].parentNode.removeChild(rows[j]);
            }
          }
          */
          ref.inCaptcha = 0 // update state that the form is removed
          clearTimeout(ref.captchaRefreshTimer)
          const crowElem = ref.submit.parentNode.querySelector('.captchaRow')
          if (crowElem) {
            crowElem.remove()
          } else {
            console.warn('cant find captcahRow')
          }
          ref.setFormLock(false) // unlock
          return false;
        }
        /*
        img.onload = function() {
          console.log('captcha loaded')
        }
        */
        this.submit.disabled = false
        e.preventDefault()
        return false
      } else
      if (this.inCaptcha === 1) {
        //console.log('processing captcha response...')
        const cInputElem = this.form.querySelector('.col input[name=captcha]')
        if (!cInputElem) {
          console.warn('cInputElem not found')
          alert("cant find CAPTCHA field")
          e.preventDefault()
          return false
        }
        // make sure it's acceptable before we upload files...
        const fd = new FormData()
        fd.append('captcha', cInputElem.value)
        //fd.append('captcha_id', this.captchaId)
        var ref = this
        fetch('/CAPTCHAs/' + this.captchaId + '/solve', { method: 'POST', body: fd }).then(function(response) {
          return response.json()
        }).then(function(data) {
          //console.debug('solve', data)
          if (data.ok) {
            ref.inCaptcha = 2
            ref.captcha = cInputElem.value
            clearTimeout(ref.captchaRefreshTimer)
            const crowElem = ref.submit.parentNode.querySelector('.captchaRow')
            if (crowElem) {
              crowElem.remove()
            } else {
              console.warn('cant find captcahRow')
            }
            // and resubmit
            ref.formSubmit(e)
          } else {
            /*
            doModal({
              'title': 'Error',
              'message': 'incorrect captcha'
            });
            */
            alert('incorrect captcha')
            // unlock form
            ref.submit.disabled = false
          }
        })
        e.preventDefault()
        return false
      }
    }
    //console.debug('attempting to make post')
    // js pipelines?

    // inject captcha if we have it
    const captchaResponse = recaptchaResponse;
    // setup postData based on type of form
    let postData;
    let addMode = false
    //console.log('enctype', this.enctype)
    if (this.enctype === 'multipart/form-data') {
      // lib.form always uses this
      //this.fileInput && (this.fileInput.disabled = true); // disable it

      // probably have to unlock the fields, so we can read them
      this.setFormLock(false)
      postData = new FormData(this.form);

      /*
      console.log('postData', postData)
      for(var k in data) {
        console.log('test', k, postData.get(k))
      }
      */
      if (captchaResponse) {
        postData.append('captcha', captchaResponse);
      }
      // the input is in the named keys of this list more than once
      // but the count seems sane
      // we can map if there's only one file field
      // what do we do if there's more than one?
      //console.log('form', this.form.elements)
      //console.log('postData', postData)

      // we don't want to add it twice...
      //this.fileInput && (this.fileInput.disabled = false); // enable it
      // well it depends how they were added
      // if you click on the button and add, they'll be in the files input
      // but if you drag and drop, it won't..
      //console.log('postData', postData)
      // this is the drag and drop managed list
      //console.log('files', this.files, 'count', this.files.length)
      // sync this.files and this.fileInput
      // this is the drag and drop managed list
      //console.log('files', this.files, 'count', this.files.length)
      //console.log('this.forms.controls', this.form.elements)
      //console.log('value', this.form.elements["files[]"].files)
      //console.log('test', this.fileInput.files)

      if (this.files && this.files.length > 0) {
        //console.log('fileInput', this.fileInput, this.fileInput.name)

        //add files to file input element that aren't already there
        for (let i = 0; i < this.files.length; i++) {
          //console.log('dnd file info', this.files[i])
          let have = false
          const df = this.files[i]
          const dkey = df.name + '_' + df.size + '_' + df.type + '_'  + df.lastModified
          for(let j = 0; j < this.fileInput.files.length; j++) {
            //console.log('files info', this.fileInput.files[j])
            const cf = this.fileInput.files[j]
            const ckey = cf.name + '_' + cf.size + '_' + cf.type + '_'  + cf.lastModified
            if (ckey === dkey) {
              have = true
              break
            }
          }
          if (!have) {
            console.log(i, 'adding', this.files[i], 'to', this.fileInput.name)
            postData.append(this.fileInput.name, this.files[i]);
          }
        }
      }

      this.setFormLock(true) // relock hopefully now it's build
    } else {
      var data = Object.keys(this.form.elements).reduce((obj, field) => { if (isNaN(field)) obj[field] = this.form.elements[field].value; return obj; }, {});
      postData = new URLSearchParams();
      for(var k in data) {
        postData.append(k, data[k])
      }
      /*
      console.log('postData', postData)
      for(var k in data) {
        console.log('test', k, postData.get(k))
      }
      */
      //postData = new URLSearchParams([...(new FormData(this.form))]);
      if (captchaResponse) {
        postData.set('captcha', captchaResponse);
      }
      //console.log('postData', postData)
    }
    if (this.inCaptcha === 2) {
      // we had a valid CAPTCHA solved
      const cInputElem = this.form.querySelector('.col input[name=captcha]')
      // set isn't available in chrome 49
      postData.append('captcha_id', this.captchaId)
      postData.append('captcha', this.captcha)
      //console.debug('solving', this.captchaId, 'with', this.captcha)
      this.inCaptcha = 0 // reset captcha mode if we make it this far
    }

    // ban filter
    if (this.banned
      || this.minimal
      || (postData instanceof URLSearchParams && postData.get('edit') === '1')) {
      return true;
    } else {
      e.preventDefault();
    }

    //this.submit.disabled = true;
    this.submit.value = 'Processing...';

    const xhr = new XMLHttpRequest();

    // enable extra file handling
    if (this.files && this.files.length > 0) {
      //show progress on file uploads
      xhr.onloadstart = () => {
        this.submit.value = '0%';
      }
      xhr.upload.onprogress = (ev) => {
        const progress = Math.floor((ev.loaded / ev.total) * 100);
        this.submit.value = `${progress}%`;
      }
      xhr.onload = () => {
        this.submit.value = this.originalSubmitText;
      }
    }
    xhr.onreadystatechange = () => {
      if (xhr.readyState === 4) {
        if (captchaResponse && grecaptcha) {
          grecaptcha.reset();
        } else if(captchaResponse && hcaptcha) {
          hcaptcha.reset();
        }
        if (xhr.getResponseHeader('x-captcha-enabled') === 'false') {
          //remove captcha if it got disabled after you opened the page
          captchaController.removeCaptcha();
          this.captchaField = null;
        }
        this.setFormLock(false) // unlock
        //this.submit.disabled = false;
        // onload does this too
        this.submit.value = this.originalSubmitText;
        let json;
        if (xhr.responseText) {
          try {
            json = JSON.parse(xhr.responseText);
          } catch (e) {
            //wasnt json response
            console.log('not json', e, xhr.responseText)
          }
        }
        //console.debug('response status code', xhr.status)
        //console.log('json', json)
        if (xhr.status == 200) {
          //console.log('json', json)
          if (!json) {
            // interesting constraint...
            let action = this.form.getAttribute('action')
            if (!action.match(/:\/\//)) {
              action = location.origin + action
            }
            if (xhr.responseURL
              && xhr.responseURL !== action) {
              console.debug('redirect to', xhr.responseURL)
              //window.location = xhr.responseURL;
              return;
            } else if (xhr.responseText) {
              //
              console.log('responseText', xhr.responseText)
              document.documentElement.innerHTML = xhr.responseText
            }
            //console.log('no responseText', xhr, xhr.response, xhr.getAllResponseHeaders())
            // maybe a metatag refresh
            //console.log('no responseText, loading html', xhr.response)
            //document.innerHTML = xhr.response
          } else {
            // has json
            if (json.postId) {
              /*
              var storedArray = JSON.parse(localStorage.getItem('yous'))
              storedArray.push(json.boardUri + '-' + json.postId)
              localStorage.setItem('yous', JSON.stringify(storedArray))
              */

              // yous.js use this
              // something to coordinate with addPost events
              // forms set window.location.hash
              // you uses to detect if it's ours or not
              window.myPostId = json.postId;
            }
            // add to yous
            if (json.redirect) {
              const redirectBoard = json.redirect.split('/')[1];
              const redirectPostId = json.redirect.split('#')[1];
              //console.debug('redirectBoard', redirectBoard, 'redirectPostId', redirectPostId)
              if (redirectBoard && redirectPostId) {
                appendLocalStorageArray('yous', `${redirectBoard}-${redirectPostId}`);
              }
              // if new thread, we need to either clear the message
              // or redirect to the new thread?
            }
            if (json.message || json.messages || json.error || json.errors) {
              //doModal(json);
              alert(JSON.stringify(json))
            // we're not likely going to use websockets
            // however some ajax polling might be good
            //} else if (socket && socket.connected) {
              //window.location.hash = json.postId
            } else {
              // success

              // clear all message boxes
              const messageBoxes = document.querySelectorAll('textarea[name=message]')
              for(var i in messageBoxes) {
                if (messageBoxes.hasOwnProperty(i)) {
                  // clear value
                  messageBoxes[i].value = ''
                }
              }

              // assumes redirect...
              console.log('isThread', isThread)
              if (!isThread) {
                // we end up here if we're making a new thread from board page
                console.debug('redirect to', json.redirect)
                // stay on board thread listing and refresh?
                // or do we jump to the new thread you made?
                //window.location = json.redirect;
                return
              }
              // used to set hash
              setLocalStorage('myPostId', json.postId);
              // live.js
              //forceUpdate();

              // we need to trigger a JS refresh
              // do this last because BE (lynxchan) needs some time to write the files
              // FIXME: maybe a delay value could be returned of how long to delay this
              if (typeof(manual_refresh) !== 'undefined') {
                console.log('refresh.js detected, calling')
                manual_refresh()
              }
            }
          }
          if (this.form.getAttribute('action') !== '/forms/editpost'
            && !this.form.getAttribute('action').endsWith('/settings')) { //dont reset on edit, keep the new values in there. todo: add exceptions/better handling for this situation
            this.reset();
          }
        } else {
          if (xhr.status === 413) {
            // maybe not clear all of them...
            // let them decide
            //this.clearFiles()

            //not json, must be nginx response
            alert('Your upload was too large')
            /*
            doModal({
              'title': 'Payload Too Large',
              'message': 'Your upload was too large',
            });
            */
          } else if (json) {
            var ref = this
            function checkMsg(err) {
              if (!ref.captchaField && err === 'Incorrect captcha answer') {
                captchaController.addMissingCaptcha();
                ref.captchaField = true;
              } else if (err === 'Captcha expired') {
                const captcha = ref.form.querySelector('.captcharefresh');
                if (captcha) {
                  captcha.dispatchEvent(new Event('click'));
                }
              }
            }

            if (json.errors) {
              for(var i in json.errors) {
                checkMsg(json.errors[i])
              }
              /*
              doModal(json, () => {
                this.formSubmit(e);
              });
              */
              alert("Error(s):\n" + json.errors.join("\n"))
            }
            //this.formSubmit(e)
          } else {
            // call it at least once per file tbh
            for(var i = 0; i < this.files.length; i++) {
              doWork() // generate thumb
            }
            //for bans, post form to show TODO: make modal support bans json and send dynamicresponse from it (but what about appeals, w/ captcha, etc?)
            this.clearFiles(); //dont resubmit files
            this.banned = true;
            // does this submit the form
            this.form.dispatchEvent(new Event('submit'));
          }
        }
        // done on ajax onload too
        this.submit.value = this.originalSubmitText;
      }
    }
    xhr.onerror = (err) => {
      console.error(err); //why is this error fucking useless
      doModal({
        'title': 'Error',
        'message': 'Something broke'
      });
      this.submit.disabled = false;
      this.submit.value = this.originalSubmitText;
    }
    xhr.open(this.form.getAttribute('method'), this.form.getAttribute('action') + '.json', true);
    //if (!this.minimal) {
      //xhr.setRequestHeader('x-using-xhr', true);
    //}
    const isLive = localStorage.getItem('live') == 'true' && socket && socket.connected;
    if (isLive) {
      xhr.setRequestHeader('x-using-live', true);
    }
    if (this.enctype !== 'multipart/form-data') {
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    xhr.send(postData);
  }

  updateMessageBox() {
    // for counter
    this.messageBox && this.messageBox.dispatchEvent(new Event('input'));
  }

  removeFile(fileElem, name, size) {
    fileElem.remove();
    let fileIndex;
    this.files.find((f, index) => {
      if (f.name === name && f.size === size) {
        fileIndex = index;
      }
    })
    this.files.splice(fileIndex, 1);
    this.updateFilesText();
  }

  addFile(file) {
    if (this.fileRequired) { //prevent drag+drop issues by removing required
      this.fileInput.removeAttribute('required');
    }
    this.files.push(file);
    //console.log('got file', file.name);

    var ref = this
    function doStuff(fileBuffer) {
      window.crypto.subtle.digest('SHA-256', fileBuffer).then(function(fileDigest) {
        fileHash = Array.from(new Uint8Array(fileDigest))
        var fileHashStr = ''
        for(var i in fileHash) {
          fileHashStr += pad(fileHash[i].toString(16), 2)
        }
        doItem(fileHashStr)
      })
    }

    let fileHash;
    if (window.crypto.subtle) {
      let fileBuffer;
      if (file.arryaBuffer) {
        file.arrayBuffer().then(function(fileBuffer) {
          doStuff(fileBuffer)
        });
      } else {
        //can old browsers just fuck off please?
        const bufferFileReader = new FileReader();
        new Promise((res, rej) => {
          bufferFileReader.addEventListener('loadend', res);
          bufferFileReader.readAsArrayBuffer(file);
        }).then(function() {
          if (bufferFileReader.result) {
            fileBuffer = bufferFileReader.result;
          }
          doStuff(fileBuffer)
        })
      }

    } else {
      doItem(fileHash)
    }

    function doItem(fileHash) {
      //console.log('doItem', fileHash)
      const item = {
        spoilers: ref.fileUploadList.dataset.spoilers === 'true',
        name: file.name,
        hash: fileHash,
      }
      // set temp thumbnail
      switch (file.type.split('/')[0]) {
        case 'image':
          item.url = URL.createObjectURL(file);
          break;
        case 'audio':
          item.url = 'images/img/audio.png'
          break;
        case 'video':
          item.url = 'images/img/video.png'
          break;
        default:
          item.url = 'images/img/attachment.png'
          break;
      }
      const uploadItemHtml = uploaditem({ uploaditem: item });
      ref.fileUploadList.insertAdjacentHTML('beforeend', uploadItemHtml);
      const fileElem = ref.fileUploadList.lastChild;
      const lastClose = fileElem.querySelector('.close');
      lastClose.addEventListener('click', () => {
        ref.removeFile(fileElem, file.name, file.size);
      })
      ref.fileUploadList.style.display = 'unset';
    }
  }

  //show number of files on new label
  updateFilesText() {
    if (!this.fileLabelText) {
      return;
    }
    if (this.files && this.files.length === 0) {
      this.fileUploadList.textContent = '';
      this.fileUploadList.style.display = 'none';
      //console.log('fileLabelText', this.fileLabelText, this.fileLabelText.innerText)
      this.fileLabelText.innerText = `Select/Drop/Paste file${this.multipleFiles ? 's' : ''}`;
    } else {
      this.fileLabelText.innerText = `${this.files.length} file${this.files.length > 1 ? 's' : ''} selected`;
    }
  }

  //remove all files from this form
  clearFiles() {
    if (!this.fileInput) {
      return;
    }
    this.files = []; //empty file list
    this.fileInput.value = null; //remove the files for real
    if (this.fileRequired) { //reset to required if clearing files
      this.fileInput.setAttribute('required', true)
    }
    this.updateFilesText();
  }

  //paste file from clipboard
  paste(e) {
    const clipboard = e.clipboardData;
    if (clipboard.items && clipboard.items.length > 0) {
      const items = clipboard.items;
      for (let i = 0; i < items.length; i++) {
        const item = items[i];
        if (item.kind === 'file') {
          const file = new File([item.getAsFile()], 'ClipboardImage.png', { type: item.type });
          this.addFile(file);
        }
      }
      this.updateFilesText();
    }
  }

  //change cursor on hover
  fileLabelDrag(e) {
    e.stopPropagation();
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
  }

  //add file on drag+drop
  fileLabelDrop(e) {
    e.stopPropagation();
    e.preventDefault();
    const newFiles = e.dataTransfer.files;
    for (let i = 0; i < newFiles.length; i++) {
      this.addFile(newFiles[i]);
    }
    this.updateFilesText();
  }

  //add file by normal file form, but add instead of replacing files
  fileInputChange(e) {
    const newFiles = this.fileInput.files;
    for (let i = 0; i < newFiles.length; i++) {
      this.addFile(newFiles[i]);
    }
    this.updateFilesText();
  }

  //middle click to clear files
  fileLabelAuxclick(e) {
    if (e.button !== 1) { //middle click only
      return;
    }
    this.clearFiles();
  }

}

window.addEventListener('DOMContentLoaded', () => {

  const myPostId = localStorage.getItem('myPostId');
  if (myPostId) {
    // we don't always want to jump
    // say you're reading a large thread and slowly reading all the responses
    // may encourage reply-guying but sometimes needed
    //console.log('jumping to', myPostId)
    //window.location.hash = myPostId;
    localStorage.removeItem('myPostId');
  }

  window.addEventListener('addPost', (e) => {
    if (e.detail.hover) {
      return; //dont need to handle hovered posts for this
    }
    if (window.myPostId == e.detail.postId) {
      console.debug('jumping to newly added post', e.detail.postId)
      window.location.hash = e.detail.postId;
    }
  });

});

//window.addEventListener('settingsReady', () => {
  // we don't want all forms....
  //const forms = document.getElementsByTagName('form');
  const forms = document.querySelectorAll('form.enable_formjs');
  for(let i = 0; i < forms.length; i++) {
    //if (forms[i].method === 'post' /*&& forms[i].encoding === 'multipart/form-data'*/) {
      new formHandler(forms[i]);
    //}
  }

//})