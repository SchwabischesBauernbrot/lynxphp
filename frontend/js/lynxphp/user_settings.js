function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}

let statusCode = 0
fetch(BACKEND_PUBLIC_URL + 'opt/user/settings', {
  headers: {
    sid: getCookie('session'),
  }
}).then(response => {
  statusCode = response.status; return response.json()
}).then(res => {
  console.debug('settings', res.data)
  var newEvent = new CustomEvent('reconfigure', {
     detail: { user: res.data }
  })
  // do we need a delay?
  window.dispatchEvent(newEvent)
})
