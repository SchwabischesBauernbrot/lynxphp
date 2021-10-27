var hoverCacheList
var hoverHtmlCacheList
window.addEventListener('DOMContentLoaded', (event) => {

  const quotes = document.getElementsByClassName('quote');
  let hoverLoading = {};
  let hoverHTMLLoading = {};
  let hovering = false;
  let lastHover;

  const toggleDottedUnderlines = (hoveredPost, id) => {
    let uniqueQuotes = new Set();
    const posts = hoveredPost.querySelectorAll('.post-message .quote')
    //console.log('posts', posts)
    for(var i = 0; i < posts.length; i++) {
      uniqueQuotes.add(posts[i].href)
    }
    if (uniqueQuotes.size > 1) {
      const matchingQuotes = hoveredPost.querySelectorAll(`.post-message .quote[href$="${id}"]`);
      for (let i = 0; i < matchingQuotes.length; i++) {
        const mq = matchingQuotes[i];
        mq.style.borderBottom = mq.style.borderBottom == '' ? '1px dashed' : '';
        mq.style.textDecoration = mq.style.textDecoration == '' ? 'none' : '';
      }
    }
  }

  const isVisible = (e) => {
    const top = e.getBoundingClientRect().top;
    const bottom = e.getBoundingClientRect().bottom;
    const height = window.innerHeight;
    return top >= 38 && bottom <= height;
  }

  const setFloatPos = (quote, float, xpos, ypos) => {
    const quotepos = quote.getBoundingClientRect();
    const post = float.firstChild;
    const iw = document.body.offsetWidth-10;
    const ih = window.innerHeight;
    const left = xpos < iw/2;
    if (left) {
      float.style.left = `${quotepos.right+10}px`;
      if (quotepos.right+10+post.offsetWidth >= iw) {
        float.style.right = '5px';
      }
    } else {
      float.style.right = `${iw-quotepos.left+15}px`;
      if (quotepos.left-15 < post.offsetWidth) {
        float.style.left = '5px';
      }
    }
    const top = ypos < ih/2;
    if (top && quotepos.bottom+post.offsetHeight < ih) {
      float.style.top = `${quotepos.top}px`;
    } else if (!top && post.offsetHeight < ypos) {
      float.style.top = `${quotepos.bottom-post.offsetHeight}px`;
    } else {
      float.style.top = '42px';
    }
  }

  const floatPost = (quote, post, xpos, ypos) => {
    const clone = document.createElement('div');
    clone.id = 'float';
    clone.classList.remove('hoverhighlighted');
    clone.appendChild(post.cloneNode(true));
    document.body.appendChild(clone);
    setFloatPos(quote, clone, xpos, ypos);
  };

  const toggleHighlightPost = function (e) {
    hovering = e.type === 'mouseover';
    //console.log('jsonParts - pathname', this.pathname)
    let jsonParts = this.pathname.replace(/\.html$/, '.json').split('/');
    //console.log('jsonParts - pathname to json', jsonParts)
    let jsonPath;
    if ((isManage || isModView) && jsonParts.length === 5) {
      jsonParts.splice(2,1); //remove manage from json url
    }
    jsonPath = '/opt' + jsonParts.join('/');
    //console.log('jsonPath - add opt', jsonPath)
    if (!this.hash) {
      return; //non-post number board quote
    }
    const float = document.getElementById('float');
    if (float) {
      document.body.removeChild(float);
    }
    const parentPost = this.closest('.post-container');
    let thisId = 0;
    if (parentPost) {
      thisId = parentPost.dataset.postId;
    }
    const loading = Date.now();
    lastHover = loading;
    const hash = this.hash.substring(1);
    const anchor = document.getElementById(hash);

    // need the extra nextSibling because of the whitespace
    // FIXME: make this smarter
    let hoveredPost;
    if (anchor && jsonPath.split('/')[1] === anchor.nextSibling.nextSibling.dataset.board) {
      hoveredPost = anchor.nextSibling.nextSibling;
    } else {
      //let hovercache = localStorage.getItem(`hovercache-${jsonPath}`);
      const htmlParts = jsonPath.replace('.json', '.html').replace('/thread/', '/preview/').replace('/opt', '').split('/')
      htmlParts.pop()
      const htmlPath = htmlParts.join('/') + '/' + hash
      const id = htmlPath.replace('.html', '').split('/')[3]

      let hoverHTMLcache = localStorage.getItem(`hoverhtmlcache-${htmlPath}`);
      let postJson
      let postHTML
      if (0 && hovercache) {
        hovercache = JSON.parse(hovercache);
        postJson = hovercache.posts.filter(p => p.no == hash)[0]
        //console.log('postJson', postJson, 'hash', hash)
        /*
        if (hovercache.postId == hash) {
          postJson = hovercache;
        } else if (hovercache.replies && hovercache.replies.length > 0) {
          postJson = hovercache.replies.find(r => r.postId == hash);
        } else {
          console.warn('hovercache missing replies?', postJson)
        }
        */
      }

      function processHTML(html, thisId, e, ref, hovering) {
        postHTML = html

        const wrap = document.createElement('div');
        wrap.innerHTML = postHTML;
        // set hoveredPost
        //console.log('wrap', wrap)
        // need extra nextSibling because of whitespace
        // FIXME: make this smarter
        //hoveredPost = wrap.firstChild.nextSibling.nextSibling.nextSibling.nextSibling;
        hoveredPost = wrap.querySelector('.post-container')
        if (hoveredPost === null) {
          console.log('no post-container', wrap)
        }
        //console.log('hoveredPost', hoveredPost)
        //need this event so handlers like post hiding still apply to hover introduced posts

        //console.log('postid', id)
        const newPostEvent = new CustomEvent('addPost', {
           detail: {
            //json: postJson,
            post: hoveredPost,
            postId: id,
            hover: true
          }
        });
        window.dispatchEvent(newPostEvent);
        //console.log('thisId', thisId)
        toggleDottedUnderlines(hoveredPost, thisId);

        if (hovering && !isVisible(hoveredPost)) {
          floatPost(ref, hoveredPost, e.clientX, e.clientY);
        } else {
          hovering ? hoveredPost.classList.add('hoverhighlighted') : hoveredPost.classList.remove('hoverhighlighted');
        }
      }

      if (hoverHTMLcache) {
        return processHTML(hoverHTMLcache, thisId, e, this, hovering)
      } else {//wasnt cached or cache outdates
        this.style.cursor = 'wait';
        try {
          // we need the lock to prevent multiple inflight
          if (!hoverHTMLLoading[htmlPath]) {
            //console.log('htmlPath', htmlPath, 'hash', hash)
            //console.log('asking for', htmlPath.substr(1))
            hoverHTMLLoading[htmlPath] = fetch(htmlPath.substr(1)).then(res => res.text());
          }
          hoverHTMLLoading[htmlPath].then(html => {
            //console.log('html', html)
            if (html) {
              setLocalStorage(`hoverhtmlcache-${htmlPath}`, html);
              // update valuelist
              /*
              if (hoverHtmlCacheList) {
                hoverCacheList.value = Object.keys(localStorage).filter(k => k.startsWith('hovercache'));
              }
              */
              //console.log('has html')
              processHTML(html, thisId, e, this, hovering)
            } else {
              console.log('no html?', html, typeof(html))
              return localStorage.removeItem(`hoverhtmlcache-${htmlPath}`); //thread deleted
            }
          })
        } catch (e) {
          return console.error(e);
        } finally {
          this.style.cursor = '';
        }
      }
      if (0) {
        if (!postJson) {//wasnt cached or cache outdates
          this.style.cursor = 'wait';
          let json;
          try {
            if (!hoverLoading[jsonPath]) {
              console.log('jsonPath fetch', jsonPath)
              hoverLoading[jsonPath] = fetch(BACKEND_PUBLIC_URL + jsonPath.substr(1)).then(res => res.json());
            }
            hoverLoading[jsonPath].then(json => {
              //console.log('hoverLoading', json)
              json = json.data // strip data off
              if (json) {
                setLocalStorage(`hovercache-${jsonPath}`, JSON.stringify(json));
                if (hoverCacheList) {
                  hoverCacheList.value = Object.keys(localStorage).filter(k => k.startsWith('hovercache'));
                }
                //console.log('posts', json.posts)
                postJson = json.posts.filter(p => p.no == hash)[0]
                //console.log('postJson', postJson, 'hash', hash)
                /*
                if (json.postId == hash) {
                  postJson = json;
                } else {
                  if (json.replies) {
                    postJson = json.replies.find(r => r.postId == hash);
                  } else {
                    console.warn('json replies missing', json)
                  }
                }
                */
              } else {
                return localStorage.removeItem(`hovercache-${jsonPath}`); //thread deleted
              }
            })
          } catch (e) {
            return console.error(e);
          } finally {
            this.style.cursor = '';
          }
        }
      }
      if (lastHover !== loading) {
        console.warn('dont show for ones not hovering')
        return; //dont show for ones not hovering
      }
      /*
      if (!postJson) {
        console.warn('post', this.hash, 'was deleted or missing')
        return; //post was deleted or missing
      console.log('postJson', postJson)
      if (!postJson.date) {
        postJson.date = new Date()
        postJson.reportDate = new Date()
      }
      }
      */
      if (!postHTML) {
        return; //post not loaded yet but we can't call toggleDottedUnderlines
      }
      /*
      console.log('postHTML', postHTML)
      if (!postHTML) {
        console.warn('post', this.hash, 'was deleted or missing')
        return; //post was deleted or missing
      }
      //const postHtml = post({ post: postJson });
      const postHtml = postHTML
      const wrap = document.createElement('div');
      wrap.innerHTML = postHtml;
      hoveredPost = wrap.firstChild.nextSibling;
      //need this event so handlers like post hiding still apply to hover introduced posts
      const newPostEvent = new CustomEvent('addPost', {
         detail: {
          json: postJson,
          post: hoveredPost,
          postId: postJson.postId,
          hover: true
        }
      });
      window.dispatchEvent(newPostEvent);
      */
    }
    toggleDottedUnderlines(hoveredPost, thisId);
    if (hovering && !isVisible(hoveredPost)) {
      floatPost(this, hoveredPost, e.clientX, e.clientY);
    } else {
      hovering ? hoveredPost.classList.add('hoverhighlighted') : hoveredPost.classList.remove('hoverhighlighted');
    }
  }

  // from top to bottom, posts are created in order
  // so a reference here means the post may exist before it
  const toAdd = []
  for (let i = 0; i < quotes.length; i++) {
    // get the board and post
    const parts = quotes[i].href.split(/\/#?/)

    //console.log('parts', parts)
    const postNum = parts[5]
    // is this post / board on the page?
    const elem = document.querySelector('.post-container[data-post-id="' + postNum + '"]')
    //console.log('searching for', postNum, 'found', elem)
    if (elem) {
      // find it's replies and put this link in there
      const repliesElem = elem.querySelector('.replies')
      //console.log('replies', repliesElem)
      const newSpan = document.createElement('span')
      const postData = quotes[i].parentNode.parentNode.parentNode.dataset
      const parts2 = (window.location.pathname).split('#')
      //console.log('postData', postData, window.location.pathname, parts2[0])
      const link = parts2[0] + '#' + postData.postId
      newSpan.innerHTML = '&nbsp;<a class="quote" href="' + link + '">&gt;&gt;' + postData.postId + '</a>';
      toAdd.push([repliesElem, newSpan])
      //repliesElem.appendChild(newSpan)
    }
    quotes[i].addEventListener('mouseover', toggleHighlightPost, false);
    quotes[i].addEventListener('mouseout', toggleHighlightPost, false);
  }
  console.log('quotes processed')
  for(var i in toAdd) {
    const elem = toAdd[i][0]
    const span = toAdd[i][1]
    elem.appendChild(span)
    const link = span.querySelector('a')
    link.addEventListener('mouseover', toggleHighlightPost, false);
    link.addEventListener('mouseout', toggleHighlightPost, false);
  }
  console.log('backlinks added')

  window.addEventListener('addPost', function(e) {
    if (e.detail.hover) {
      return; //dont need to handle hovered posts for this
    }
    const post = e.detail.post;
    const newquotes = document.getElementsByClassName('quote'); //to get backlinks from replying posts. just an easy way. could make more efficient and only do necessary ones later.
    for (let i = 0; i < newquotes.length; i++) {
      newquotes[i].removeEventListener('mouseover', toggleHighlightPost);
      newquotes[i].removeEventListener('mouseout', toggleHighlightPost);
      newquotes[i].addEventListener('mouseover', toggleHighlightPost, false);
      newquotes[i].addEventListener('mouseout', toggleHighlightPost, false);
    }
  });

  window.addEventListener('updatePostMessage', function(e) {
    const newquotes = e.detail.post.getElementsByClassName('quote');
    for (let i = 0; i < newquotes.length; i++) {
      newquotes[i].addEventListener('mouseover', toggleHighlightPost, false);
      newquotes[i].addEventListener('mouseout', toggleHighlightPost, false);
    }
  });

});

window.addEventListener('settingsReady', function(e) {
  console.log('settingsReady')
  hoverCacheList = document.getElementById('hovercachelist-setting');
  hoverCacheList.value = Object.keys(localStorage).filter(k => k.startsWith('hovercache'));
  const hoverCacheListClearButton = document.getElementById('hovercachelist-clear');
  const clearHoverCacheList = () => {
    deleteStartsWith('hovercache');
    hoverCacheList.value = '';
    console.log('cleared cache');
  }
  hoverCacheListClearButton.addEventListener('click', clearHoverCacheList, false);
});

