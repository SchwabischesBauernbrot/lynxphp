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
      this.fileLabelText = this.fileLabel.childNodes[0];
      this.fileLabel.addEventListener('dragover', e => this.fileLabelDrag(e));
      this.fileLabel.addEventListener('drop', e => this.fileLabelDrop(e));
      this.fileInput.addEventListener('change', e => this.fileInputChange(e));
      this.fileLabel.addEventListener('auxclick', e => this.fileLabelAuxclick(e));
    }
    this.messageBox && this.messageBox.addEventListener('keydown', e => this.controlEnterSubmit(e));
    form.addEventListener('paste', e => this.paste(e));
    form.addEventListener('submit', e => this.formSubmit(e));
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
  }

  controlEnterSubmit(e) {
    if (e.ctrlKey && e.key === 'Enter') {
      this.formSubmit(e);
    }
  }

  // only submits one file
  formSubmit(e) {
    const xhr = new XMLHttpRequest();
    // inject captcha if we have it
    const captchaResponse = recaptchaResponse;
    // setup postData based on type of form
    let postData;
    if (this.enctype === 'multipart/form-data') {
      // lib.form always uses this
      this.fileInput && (this.fileInput.disabled = true);
      postData = new FormData(this.form);
      if (captchaResponse) {
        postData.append('captcha', captchaResponse);
      }
      // the input is in the named keys of this list more thanonce
      // but the count seems sane
      // we can map if there's only one file field
      // what do we do if there's more than one?
      //console.log('form', this.form.elements)
      this.fileInput && (this.fileInput.disabled = false);
      if (this.files && this.files.length > 0) {
        //console.log('fileInput', this.fileInput, this.fileInput.name)
        //add files to file input element
        for (let i = 0; i < this.files.length; i++) {
          postData.append(this.fileInput.name, this.files[i]);
        }
      }
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
    }
    // ban filter
    if (this.banned
      || this.minimal
      || (postData instanceof URLSearchParams && postData.get('edit') === '1')) {
      return true;
    } else {
      e.preventDefault();
    }
    this.submit.disabled = true;
    this.submit.value = 'Processing...';
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
        this.submit.disabled = false;
        this.submit.value = this.originalSubmitText;
        let json;
        if (xhr.responseText) {
          try {
            json = JSON.parse(xhr.responseText);
          } catch (e) {
            //wasnt json response
            console.log('not json', e)
          }
        }
        console.log('response status code', xhr.status)
        if (xhr.status == 200) {
          console.log('json', json)
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
              if (redirectBoard && redirectPostId) {
                appendLocalStorageArray('yous', `${redirectBoard}-${redirectPostId}`);
              }
            }
            if (json.message || json.messages || json.error || json.errors) {
              doModal(json);
            } else if (socket && socket.connected) {
              window.location.hash = json.postId
            } else {
              // success
              // assumes redirect...
              if (!isThread) {
                console.debug('redirect to', json.redirect)
                return //window.location = json.redirect;
              }
              // used to set hash
              setLocalStorage('myPostId', json.postId);
              // live.js
              forceUpdate();
            }
          }
          if (this.form.getAttribute('action') !== '/forms/editpost'
            && !this.form.getAttribute('action').endsWith('/settings')) { //dont reset on edit, keep the new values in there. todo: add exceptions/better handling for this situation
            this.reset();
          }
        } else {
          if (xhr.status === 413) {
            this.clearFiles();
            //not json, must be nginx response
            doModal({
              'title': 'Payload Too Large',
              'message': 'Your upload was too large',
            });
          } else if (json) {
            if (!this.captchaField && json.message === 'Incorrect captcha answer') {
              captchaController.addMissingCaptcha();
              this.captchaField = true;
            } else if (json.message === 'Captcha expired') {
              const captcha = this.form.querySelector('.captcharefresh');
              if (captcha) {
                captcha.dispatchEvent(new Event('click'));
              }
            }
            doModal(json, () => {
              this.formSubmit(e);
            });
          } else {
//for bans, post form to show TODO: make modal support bans json and send dynamicresponse from it (but what about appeals, w/ captcha, etc?)
            this.clearFiles(); //dont resubmit files
            this.banned = true;
            this.form.dispatchEvent(new Event('submit'));
          }
        }
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
    xhr.open(this.form.getAttribute('method'), this.form.getAttribute('action'), true);
    if (!this.minimal) {
      xhr.setRequestHeader('x-using-xhr', true);
    }
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
      console.log('doItem', fileHash)
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
      console.log('hidding fileUploadList')
      this.fileUploadList.style.display = 'none';
      this.fileLabelText.nodeValue = `Select/Drop/Paste file${this.multipleFiles ? 's' : ''}`;
    } else {
      this.fileLabelText.nodeValue = `${this.files.length} file${this.files.length > 1 ? 's' : ''} selected`;
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
    console.log('jumping to', myPostId)
    window.location.hash = myPostId;
    localStorage.removeItem('myPostId');
  }

  window.addEventListener('addPost', (e) => {
    console.log('adding post', e.detail)
    if (e.detail.hover) {
      return; //dont need to handle hovered posts for this
    }
    if (window.myPostId == e.detail.postId) {
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
