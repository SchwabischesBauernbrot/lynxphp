<?php

// a wiring harness that takes things to the backend
// while providing a bridge from the router
/*
class query_thread {
  function __constructor($request) {
    $this->portals = $request['portals'];
  }
}
// could just be an array...
*/
function request2QueryThread($request) {
  $obj = array(
    'portals' => array(),
  );
  if (!empty($request['portals'])) {
    $obj['portals'] = $request['portals'];
  }
  return $obj;
}
// additional functions to automatically handle the header/footer of portals
// probably hooked from the results

// we're a small webpage that's cacheable
function getInlineBoardsLoaderHandler() {
  $row = wrapContentData(array());
  $head_html = wrapContentGetHeadHTML($row);
  // index already puts a header on this based on router config
  global $BASE_HREF;
  echo <<<EOB
<!DOCTYPE html>
<html>
<head id="settings">
  <base href="$BASE_HREF">
  $head_html
</head>
<body id="top">
<a class="nojsonly-block" style="line-height: 100vh; text-align: center; width: 100vw; height: 100vh;" target="boardFrame" href="/boards_inline.html">Please click to load all the html for full board list</a>
EOB;

  // index already did headers
  // nothing more we can set because we're in immediate mode
  /*
  checkCacheHeaders(array(
    'fileSize' => strlen($str), // for etag
    'contentType' => 'text/html',
  ));
  */
}

// /:uri
function getBoardFileRedirect($request) {
  $boardUri = $request['params']['uri'];
  if ($boardUri) {
    $boardUri .= '/';
  }
  // FIXME: only redir if the board exists...
  global $BASE_HREF;
  redirectTo($BASE_HREF . $boardUri);
  //echo "Would redirect to [$boardUri]\n";
}

// moved out to base/board/view
/*
function getBoardThreadListingHandler($request) {
  $boardUri = $request['params']['uri'];
  // transform req => q
  $q = request2QueryThread($request);
  //echo "<pre>", print_r($request['portals'], 1), "</pre>\n";
  getBoardThreadListing($q, $boardUri);
}

function getBoardThreadListingPageHandler($request) {
  $boardUri = $request['params']['uri'];
  $page = $request['params']['page'] ? $request['params']['page'] : 1;
  $q = request2QueryThread($request);
  getBoardThreadListing($q, $boardUri, $page);
}
*/

function getBoardCatalogHandler($request) {
  $boardUri = $request['params']['uri'];
  renderBoardCatalog($boardUri);
}

function getBlockBypass($boardUri, $row) {
  // regenerate post form...
  // how did $row get threadId = 1
  // FIXME: need a shorthand for files

  $formfields = array(
    'post'   => array('type' => 'hidden'),
    'captcha'    => array('type' => 'captcha', 'label' => 'Bypass CAPTCHA'),
  );
  //

  /*
  $postform = renderPostFormHTML($boardUri, array(
    'reply' => $row['threadId'],
    'formId' => 'bottom_postform',
    'showClose' => false,
    'values' => $row,
  ));
  */
  $values = array('post' => json_encode($row));
  $formOptions = array();
  $bypassForm = generateForm('/bypass', $formfields, $values, $formOptions);

  //echo "<pre>", htmlspecialchars(print_r($postform, 1)), "</pre>\n";
  // 'Thread #' . $row['thread'] . '<br>'. "\n"
  wrapContent('blockBypass was invalid, please try again: <br>' . "\n" . $bypassForm);
}

function askWithCaptcha($boardUri, $row) {
  // generate form with post data hidden
  $data = array(
    //'reply' => $row['threadId'],
    'formId' => 'bottom_postform',
    'showClose' => false,
    'values' => $row,
  );
  if (!empty($row['threadId'])) {
    $data['reply'] = $row['threadId'];
  }
  //$postform = renderPostFormHTML($boardUri, $data);
  $formfields = array(
    'post'   => array('type' => 'hidden'),
    'captcha'    => array('type' => 'captcha', 'label' => 'Bypass CAPTCHA'),
  );
  $values = array('post' => json_encode($row));
  $formOptions = array();
  $captchaForm = generateForm('/' . $boardUri, $formfields, $values, $formOptions);
  wrapContent('a CAPTCHA is required, please answer: <br>' . "\n" . $captchaForm);
}

function retryCaptcha($boardUri, $row) {
  // regenerate post form...
  // how did $row get threadId = 1
  // FIXME: need a shorthand for files
  $data = array(
    //'reply' => $row['threadId'],
    'formId' => 'bottom_postform',
    'showClose' => false,
    'values' => $row,
    'pipelineOptions' => array('showCAPTCHA' => true)
  );
  if (!empty($row['threadId'])) {
    $data['reply'] = $row['threadId'];
  }
  // this isn't going to inject a CAPTCHA now
  // so we got to hack it in?
  // hard to do if it closes the form
  // we'd need like a option...
  // an option that hits a pipeline...
  $postform = renderPostFormHTML($boardUri, $data);

  //echo "<pre>", htmlspecialchars(print_r($postform, 1)), "</pre>\n";
  // 'Thread #' . $row['thread'] . '<br>'. "\n"
  wrapContent('Your CAPTCHA was invalid, please try again: <br>' . "\n" . $postform);
}

// requestValid should be renamed to success
function makePostHandlerEngine($request) {
  global $pipelines, $max_length;
  $boardUri = $request['params']['uri'];

  //echo '<pre>_POST: ', print_r($_POST, 1), "</pre>\n";
  //echo "max_length[$max_length]<br>\n";
  //echo '<pre>_SERVER: ', print_r($_SERVER, 1), "</pre>\n";
  //echo '<pre>_FILES: ', print_r($_FILES, 1), "</pre>\n";

  //$res = processFiles();
  $res = processPostFiles();
  //echo '<pre>res: ', print_r($res, 1), "</pre>\n";

  //if ($res && count($res['errors'])) {
  $errors = array();
  if ($res['hasErrors']) {
    // we shoud abort completely
    // informing the user is good, so they can fix it
    // and better than leaving crap on the site
    // we could let validation run and compound the errors
    /*
    return array(
      'requestValid' => false,
      'boardUri' => $boardUri,
      'errors'    => $res,
    );
    */
    if (isset($res['handles']['files']) && is_array($res['handles']['files'])) {
      foreach($res['handles']['files'] as $i => $row) {
        $errors[] = 'file #' . $i . ' had error: ' . $row['error'];
      }
    } else {
      $errors[] = 'file handle is missing or wrong type, let a dev know';
    }
  }

  // one field called files, get that list of files
  // it's not always files? getting file... form.js causes this...
  $files = isset($res['handles']['files']) ? $res['handles']['files'] : array();
  //echo '<pre>files: ', print_r($files, 1), "</pre>\n";

  // should we take count of _FILES and compare to count of files?

  $endpoint = 'lynx/newThread';
  global $BASE_HREF;
  $redir = $BASE_HREF . $boardUri . '/';
  $headers = array(
    // was HTTP_X_FORWARDED_FOR
    // but this is the actual header on the wire...
    'x-forwarded-for' => getip(),
    'sid' => getCookie('session'),
  );
  // spoilers/strip_filenames might be an array
  $row = array(
    // noFlag
    'name'     => getOptionalPostField('name'),
    'email'    => getOptionalPostField('email'),
    'message'  => getOptionalPostField('message'),
    'subject'  => getOptionalPostField('subject'),
    'boardUri' => $boardUri,
    'password' => getOptionalPostField('postpassword'),
    // captcha
    //'spoiler'  => empty($_POST['spoiler_all']) ? '' : $_POST['spoiler_all'],
    'files'    => json_encode($files),
    // flag
  );
  // has thread
  if (!empty($_POST['thread'])) {
    // make a reply
    $row['threadId'] = $_POST['thread'];
    $endpoint = 'lynx/replyThread';
    $redir .= 'thread/' . $_POST['thread'];
  }
  // future support?
  if (!empty($_POST['files_already_uploaded'])) {
    $already = json_decode($_POST['files_already_uploaded'], true);
    if (!is_array($already)) {
      echo "boards::makePostHanlder - Can't decode[", htmlspecialchars($_POST['files_already_uploaded']), "]<br>\n";
      $already = array();
    }
    $files = json_decode($row['files']);
    if (!is_array($files)) $files = array();
    // don't do anything about duplicates
    // you could make patterns...
    $row['files'] = json_encode(array_merge($already, $files));
  }

  // basic validation that we have some type of new content of value
  // empty message AND empty file
  $noFiles = (!$row['files'] || $row['files'] === '[]');
  if (!$row['message'] && $noFiles) {
    /*
    return array(
      'requestValid' => false,
      'boardUri' => $boardUri,
      'row'      => $row,
      'error'    => 'content (message/comment or file) required',
    );
    */
    $errors[] = 'content (message/comment or file) required';
  }


  $io = array(
    'boardUri' => $boardUri,
    'endpoint' => $endpoint,
    'headers'  => $headers,
    'values'   => $row,
    'redir'    => $redir,
    'error'    => false,
    'redirNow' => false,
  );
  // validate results
  $pipelines[PIPELINE_POST_VALIDATION]->execute($io);
  //print_r($io);
  $row     = $io['values'];
  $headers = $io['headers'];
  // FIGURE OUT: is this right?
  $redir   = $io['redir'];

  if (!empty($io['error'])) {
    /*
    // should just push the new errors onto errors array
    return array(
      'requestValid' => false,
      'boardUri' => $boardUri,
      'row'      => $row,
      'error'    => $io['error'],
    );
    */
    if (is_array($io['error'])) {
      foreach($io['error'] as $e) {
        $errors[] = $e;
      }
    } else {
      $errors[] = $io['error'];
    }
  }
  // ensure no errors
  if (count($errors)) {
    return array(
      'requestValid' => false,
      'boardUri' => $boardUri,
      'row'      => $row,
      'errors'   => $errors,
    );
  }

  // what's this used for? nothing right now
  if (!empty($io['redirNow'])) {
    return array(
      'requestValid' => false,
      'boardUri' => $boardUri,
      'row'      => $row,
      'redirect' => $io['redirNow'],
    );
    //echo "redirNow";
    //redirectTo($io['redirNow']);
    //return;
  }

  // make post...
  // we could queue this for a worker but then any be validation couldn't be passed to the user
  $json = request(array(
    'url' => BACKEND_BASE_URL . $endpoint,
    'body' => $row, // an array
    'headers' => $headers,
  ));
  //$json = curlHelper(BACKEND_BASE_URL . $endpoint, $row, $headers);

  // backend errors?
  // can't use this because we need better handling of results...
  //$result = expectJson($json, $endpoint)
  //echo "json[$json]<br>\n";
  $results = json_decode($json, true);

  $retval = array(
    'requestValid' => true,
    'boardUri' => $boardUri,
    'result'   => $results,
  );

  // maybe should have a better name like created id...
  if ($redir) {
    // needed for (yous) apparently on the json side
    // looks like "/test/#5730"
    $retval['redirect'] = $redir;
  }
  if (DEV_MODE) {
    $retval['debug'] = array(
      // we could get the hashes from $res (handles.files[].hash)
      // nah it's a refresh issue...
      // this is really handy
      // but maybe not as handy as
      // backend/opt/boards/test/posts/295/media_debug?prettyPrint=1
      //'filesDebugs' => $res,
      //'_FILES' => $_FILES,
      // good for debug/dev
      'backendInput' => $row, // validation and what's sent to the BE
      'filesForBE' => $files, // should be in row but it'll be jsonencoded, so less useful
    );
    if (empty($results)) {
      // json decoding errors if result is messed..
      $retval['debug']['json'] = $json;
    }
  }

  // usually file issues, plugins or bridge (CAPTCHA)
  // probably should escalate them to errors
  if (!empty($results['data']['issues'])) {
    // is the request still valid? probably not...
    // manage type
    if (is_array($results['data']['issues'])) {
      $retval['errors'] = $results['data']['issues'];
    } else {
      $retval['errors'] = array($results['data']['issues']);
    }
  }
  //print_r($retval);

  return $retval;
}

// nojs handler
function makePostHandlerHtml($request) {
  $arr = makePostHandlerEngine($request);
  $boardUri = $arr['boardUri'];
  if (!empty($arr['redirect'])) {
    // redirection handling
    //echo "redirNow";
    redirectTo($arr['redirect']);
    return;
  }
  if (!$arr['requestValid']) {
    // didn't pass validation

    // error handling
    $errors = $arr['errors'];
    //print_r($io);

    // FIXME: clean this up better

    // 230408 do we still need this?
    // special error handlers:
    if (in_array('CAPTCHA is required', $errors)) {
      // was there a CAPTCHA present in the previous form?
      retryCaptcha($boardUri, $arr['row']);
      return;
    }

    // default error
    //echo "error";
    wrapContent("Error:\n" . join("\n", $errors));
    return;
  }
  $result = $arr['result'];
  if ($result === false) {
    $json = $arr['json'];
    // invalid json
    wrapContent('Post Error: <pre>' . $json . '</pre>');
  } else {
    //echo "<pre>", $endpoint, '[', print_r($result, 1), "]</pre>\n";
    //echo "redir[$redir]<br>\n";
    //return;
    if ($result && is_array($result) && isset($result['data']) && is_numeric($result['data'])) {
      // now we have a post id, we should set the you
      $slug = $boardUri . '-' . $result['data'];
      echo <<< EOB
<script>
var storedArray = JSON.parse(localStorage.getItem('yous'))
storedArray.push('$slug')
localStorage.setItem('yous', JSON.stringify(storedArray))
</script>
EOB;
/*
// backup for window.myPostId
// used to set hash...
//setLocalStorage('myPostId', json.postId);
*/
      // success
      $redir = $arr['redirect'];
      redirectTo($redir);
    } else
    if ($result && is_array($result) && isset($result['data']) && is_array($result['data']) && $result['data']['status'] === 'queued') {
      // success (queued)
      $redir = $arr['redirect'];
      redirectTo($redir);
    } else
    if ($result && is_array($result) && isset($result['data']) && is_array($result['data']) && $result['data']['status'] === 'refused') {
      wrapContent('Post Refused');
    } else {
      // valid json
      if ($result['data'] === 'Expired captcha.' || $result['data'] === 'Wrong captcha.') {
        //print_r($row);
        retryCaptcha($boardUri, $row);
      } else
      if ($result['data']['status'] === 'bypassable') {
        //wrapContent('Block Bypass Expired');
        getBlockBypass($boardUri, $row);
      } else
      if ($result['data'] === 'Thread not found.') {
        wrapContent('Thread ' . $_POST['thread'] . ' not found' . "\n");
      } else {
        wrapContent('Post Error: ' . print_r($result, 1));
      }
    }
  }
}

// both routes have the same input interface
// just different output...
function makePostHandlerJson($request) {
  $arr = makePostHandlerEngine($request);
  $code = $arr['requestValid'] ? 200 : 400;
  // postId
  if (!empty($arr['result'])) {
    $result = $arr['result'];
    if ($result && is_array($result) && isset($result['data']) && is_numeric($result['data'])) {
      // just reuse JSChan's format for now
      $arr['postId'] = $result['data'];
      // though redirect seems more helpful
      $arr['redirect'] = '/' . $arr['boardUri'] . '/#' . $result['data'];
    }
  }
  // maybe move into sendJson?
  if (DEV_MODE) {
    // for XHR (we're not going to get this in the page report because there is no page report)
    $arr['POST'] = $_POST;
  }
  // the status code is enough
  unset($arr['requestValid']);
  sendJson($arr, array('code' => $code));
}

function renderBoardCatalog($boardUri) {
  $data = getBoardCatalog($boardUri);
  $catalog = $data['pages'];
  $boardData = $data['board'];
  if (!empty($catalog['meta']['err'])) {
    if ($catalog['meta']['err'] === 'Board not found') {
      wrapContent("Board not found");
    } else {
      wrapContent("Unknown board error");
    }
    return;
  }
  $templates = loadTemplates('catalog');

  $tmpl = $templates['header'];

  $boardnav_html  = $templates['loop0'];
  $image_template = $templates['loop1'];
  $tile_template  = $templates['loop2'];

  $maxPage = 0;
  $posts = array();
  if (is_array($catalog)) {
    foreach($catalog as $i=>$obj) {
      if (isset($obj['page'])) {
        $maxPage = max($obj['page'], $maxPage);
      } else {
        echo "<pre>No page set in [", print_r($obj, 1), "]</pre>\n";
      }
      foreach($obj['threads'] as $j => $post) {
        preprocessPost($catalog[$i]['threads'][$j]);
        $posts[] = $post;
      }
    }
  }

  global $pipelines;
  $data = array(
    'posts'    => $posts,
    'catalog'  => $catalog,
    'boardUri' => $boardUri,
  );
  $pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);
  unset($posts);

  //$boardnav_html = renderBoardNav($boardUri, $maxPage, '[Catalog]');
  $boardnav_html = '';

  $tiles_html = '';
  if (is_array($catalog)) {
    global $BASE_HREF;
    $tile_tags = array('uri' => $boardUri);
    foreach($catalog as $pageNum => $page) {
      foreach($page['threads'] as $thread) {
        /*
        $tile_image = '<a href="' . BASE_HREF . $boardUri . '/thread/' .
          $thread['no']. '.html#' . $thread['no'] .
          '"><img src="images/imagelessthread.png" width=209 height=64></a><br>';
        */
        //echo "<pre>thread[", print_r($thread, 1), "]</pre>\n";

        // update thread number
        $tile_tags['no'] = $thread['no'];
        //$tile_image = str_replace('{{file}}', 'backend/' . $thread['files'][0]['path'], $tile_image);
        // filename, size, w, h
        // thumb to be set
        if (isset($thread['files']) && count($thread['files'])) {
          $tile_tags['thumb'] = getThumbnail($thread['files'][0], array('maxW' => 209));
        } else {
          $tile_tags['thumb'] = '<img src="images/imagelessthread.png" width=209 height=64>';
        }
        // need $BASE_HREF..
        // do we? we have it in the base tag...
        //echo "page[$pageNum]<br>\n";
        $tags = array(
          'uri' => $boardUri,
          'subject' => htmlspecialchars($thread['sub']),
          'message' => htmlspecialchars($thread['com']),
          'name' => htmlspecialchars($thread['name']),
          'no' => $thread['no'],
          'jstime' => gmdate('Y-m-d', $thread['created_at']) . 'T' . gmdate('H:i:s.v', $thread['created_at']) . 'Z',
          'human_created_at' => gmdate('n/j/Y H:i:s', $thread['created_at']),
          'replies' => $thread['reply_count'],
          'files' => $thread['file_count'],
          // starts at 0
          'page' => $pageNum + 1,
          'tile_image' => replace_tags($image_template, $tile_tags),
        );
        $tiles_html .= replace_tags($tile_template, $tags);
      }
    }
  }
  //$boardData = getBoard($boardUri);
  //$boardData['pageCount'] = $boardThreads['pageCount'];
  $boardData['pageCount'] = $maxPage;
  // but no footer...
  $boardHeader = renderBoardPortalHeader($boardUri, $boardData, array(
    'isCatalog' => true,
  ));

  $p = array(
    'boardUri' => $boardUri,
    'tags' => array(
      'uri' => $boardUri,
      'description' => htmlspecialchars($boardData['description']),
      'tiles' => $tiles_html,
      'boardNav' => $boardnav_html,
      // mixin
      //'postform' => renderPostForm($boardUri, $boardUri . '/catalog'),
      'postactions' => renderPostActions($boardUri),
    ),
  );
  global $pipelines;
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  $tmpl = replace_tags($tmpl, $p['tags']);
  wrapContent($boardHeader . $tmpl);
}

?>