
function doWork() {
  let statusCode = 0
  fetch(BACKEND_PUBLIC_URL + 'opt/work').then(res => { statusCode = res.status; return res.text() } ).then(html => {
    //console.debug('work', html)
  })
}

doWork()

// we need a proper backoff to deploy something like this..
//setInterval(doWork, 60 * 1000)
