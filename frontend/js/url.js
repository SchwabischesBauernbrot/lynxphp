// maybe JSON should be left in the page to reduce this...
// or we make a fetch call to reduce b/w
function whereAmI() {
  if (window.location.pathname.match(/\/thread\//)) {
    const parts = window.location.pathname.split('/')
    //console.log('thread details', parts)
    return {
      boardUri: parts[1],
      threadNum: parts[3].replace('.html', ''),
      //jsonURL: BACKEND_PUBLIC_URL + 'opt/' + parts[1] + '/thread/' + parts[3].replace('.html', ''),
    }
  } else
  // PIPELINE? logs / banner?
  if (window.location.pathname.match(/\/catalog.html$/)) {
    const parts = window.location.pathname.split('/')
    //console.log('catalog listing', parts)
    return {
      boardUri: parts[1],
      // update thread list...
      jsonURL: BACKEND_PUBLIC_URL + 'opt/' + parts[1] + '/catalog.json',
    }
  } else {
    const parts = window.location.pathname.split('/')
    // refreshing the page has two issues
    // one which threads on that page can change
    // and new posts on those threads
    //console.log('threads listing', parts)
    return {
      boardUri: parts[1],
      // update thread list...
      // updated_at can hint at if these threads have updates...
      // could make a custom URL that we pass board and the onscreen threads
      // and which page it was...
      jsonURL: BACKEND_PUBLIC_URL + 'opt/' + parts[1] + '/catalog.json',
    }
  }
}