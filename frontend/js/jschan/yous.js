let notificationsEnabled = localStorage.getItem('notifications') == 'true';
let notificationYousOnly = localStorage.getItem('notification-yous-only') == 'true';
//let yousEnabled = localStorage.getItem('yous-setting') == 'true';
// enable it for now
let yousEnabled = true
let savedYous = new Set(JSON.parse(localStorage.getItem('yous')));
let yousList;

// hide/show them all
const toggleAllYous = (state) => savedYous.forEach(y => toggleOne(y, state));

// hide/show quotes
const toggleQuotes = (quotes, state) => {
  for(let i = 0; i < quotes.length; i++) {
    var q = quotes[i]
    q.classList[state?'add':'remove']('you');
  }
}

const toggleOne = (you, state) => {
  const [board, postId] = you.split('-');
  //console.log('yous::toggleOne -', board, postId, state)
  const post = document.querySelector(`[data-board="${board}"][data-post-id="${postId}"]`);
  if (post) {
    const postName = post.querySelector('.post-name');
    if (postName) {
      postName.classList[state?'add':'remove']('you');
    }
    /*
    const replies = post.querySelector('.replies');
    if (replies) {
    }
    */
    const postInfo = post.querySelector('.post-info');
    if (postInfo) {
      // detect if we're already there or not...
      var detect = postInfo.querySelector('span.you')
      if (!detect) {
        var elem = document.createElement('span')
        // class adds the text if you add this...
        elem.className = 'you'
        //elem.innerText = '(you)'
        postInfo.appendChild(elem)
      }
    }
  }
  const quotes = document.querySelectorAll(`.quote[href^="${board}/"][href$="#${postId}"]`);
  if (quotes && quotes.length) {
    toggleQuotes(quotes, state);
  }
}

if (yousEnabled) {
  toggleAllYous(yousEnabled);
}

const handleNewYous = (e) => {
  const postYou = `${e.detail.json.board}-${e.detail.postId}`;
  // is this event actually our post
  const isYou = window.myPostId == e.detail.postId
  if (isYou) {
    //save you
    savedYous.add(postYou);
    const arrayYous = [...savedYous];
    yousList.value = arrayYous.toString();
    setLocalStorage('yous', JSON.stringify(arrayYous));
  }
  if (savedYous.has(postYou)) {
    //toggle forn own post for name field
    toggleOne(postYou, yousEnabled);
  }
  const quotesYou = e.detail.json.quotes
    .map(q => `${e.detail.json.board}-${q.postId}`)
    .filter(y => savedYous.has(y))
    .length > 0;
  const youHoverQuotes = e.detail.json.quotes
    .concat(e.detail.json.backlinks)
    .map(q => `${e.detail.json.board}-${q.postId}`)
    .filter(y => savedYous.has(y))
    .map(y => {
      const [board, postId] = y.split('-');
      return e.detail.post.querySelectorAll(`.quote[href^="/${board}/"][href$="#${postId}"]`)
    }).reduce((acc, array) => {
      return acc.concat(Array.from(array)); //handle duplicate of same quote
    }, []);
  //toggle for any quotes in a new post that quote (you)
  toggleQuotes(youHoverQuotes, yousEnabled);
  //if not a hover newpost, and enabled/for yous, send notification
  if (!e.detail.hover && notificationsEnabled && !isYou) {
    if (notificationYousOnly && !quotesYou) {
      return; //only send notif for (you) if setting
    }
    try {
      console.log('attempting to send notification', postYou);
      const postData = e.detail.json;
      new Notification(`${quotesYou ? 'New quote in: ' : ''}${document.title}`, {
        body: postData.nomarkup ? postData.nomarkup.substring(0,100) : ''
      });
    } catch (e) {
      // notification cant send for some reason -- user revoked perms in browser?
      console.log('failed to send notification', e);
    }
  }
}

window.addEventListener('addPost', handleNewYous, false);
window.addEventListener('updatePostMessage', handleNewYous, false);

window.addEventListener('settingsReady', () => {

  // load current values into settings
  /*
  yousList = document.getElementById('youslist-setting');
  yousList.value = [...savedYous];
  */

  // wire up clear button
  /*
  const yousListClearButton = document.getElementById('youslist-clear');
  const clearYousList = () => {
    if (yousEnabled) {
      toggleAllYous(false);
    }
    savedYous = new Set();
    yousList.value = '';
    setLocalStorage('yous', '[]');
    console.log('cleared yous');
  }
  yousListClearButton.addEventListener('click', clearYousList, false);
  */

  // setting
  /*
  const yousSetting = document.getElementById('yous-setting');
  const toggleYousSetting = () => {
    yousEnabled = !yousEnabled;
    setLocalStorage('yous-setting', yousEnabled);
    toggleAllYous(yousEnabled);
    console.log('toggling yous', yousEnabled);
  }
  yousSetting.checked = yousEnabled;
  yousSetting.addEventListener('change', toggleYousSetting, false);

  const notificationYousOnlySetting = document.getElementById('notification-yous-only');
  const toggleNotificationYousOnlySetting = () => {
    notificationYousOnly = !notificationYousOnly;
    setLocalStorage('notification-yous-only', notificationYousOnly);
    console.log('toggling notification only for yous', yousEnabled);
  }
  notificationYousOnlySetting.checked = notificationYousOnly;
  notificationYousOnlySetting.addEventListener('change', toggleNotificationYousOnlySetting, false);

  const notificationSetting = document.getElementById('notification-setting');
  const toggleNotifications = () => {
    notificationsEnabled = !notificationsEnabled;
    if (notificationsEnabled) {
      Notification.requestPermission().then(result => {
        if (result != 'granted') {
          //user denied permission popup
          notificationsEnabled = false;
          notificationSetting.checked = false;

          setLocalStorage('notifications', notificationsEnabled);
          return;
        }
      })
    }
    console.log('toggling notifications', notificationsEnabled);
    setLocalStorage('notifications', notificationsEnabled);
  }
  notificationSetting.checked = notificationsEnabled;
  notificationSetting.addEventListener('change', toggleNotifications, false);
  */

});
