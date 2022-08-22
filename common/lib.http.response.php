<?php

// common response utilities

// requires config (frontend?)

// 304 just decreases bandwidth (for slow link/cost)

// returns if we outputed or false if 304
function sendJson($arr, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    //'contentType' => 'application/json',
    'code' => 200,
    //'mtime'  => 0, // 0 is don't set
    //'fileSize' => 0,
    //'etag' => false,
  ), $options));
  if ($code !== 200) http_response_code($code);

  // 304 should check should have already happened...
  /*
  // just a last stop, to prevent just in case we already have this
  if (checkCacheHeaders($mtime, array(
    'contentType' => $contentType,
    'fileSize' => $fileSize,
    'etag' => $etag,
  ))) {
    return false;
  }
  */

  // make sure we describe this resource
  // set should already be set tbh
  /*
  _doHeader($mtime, array(
    'contentType' => $contentType,
    'fileSize' => $fileSize,
    'etag' => $etag,
  ));
  */

  if (getQueryField('prettyPrint')) {
    // we need the HTML for the htmlspecialchars
    // and we need that to stop executing user generate js
    //header('Content-Type: text/html'); // should be the default
    echo '<pre>', htmlspecialchars(json_encode($arr, JSON_PRETTY_PRINT)), "</pre>\n";
  } else {
    header('Content-Type: application/json');
    echo json_encode($arr);
  }
  return true;
}

// FIXME: rename make public
// FIXME: change prototype
// mtime === 0 => doesn't set lastmod
// mtime === now => don't cache
function _doHeaders($mtime, $options = false) {
  // no need for headers when generating pages
  if (IN_GENERATE) return;

  // unpack options
  extract(ensureOptions(array(
    'contentType' => 'text/html',
    'lastMod'  => false,
    'fileSize' => 0,
    'etag' => false,
  ), $options));

  global $now;

  // why is this empty?
  // being empty in chrome makes html page not render as html in nginx/php-fpm
  header('Content-Type: ' . $contentType);
  if ($fileSize) {
    header('Content-Length: ' . $fileSize);
  }
  //header('X-Debug-mtime: ' . $mtime);

  // looks like cloudflare is stripping our custom etag...
  //if ($etag) header('X-Debug-etag: ' . $etag);

  // don't cache this
  if ($mtime === $now) {
    // don't cache
    //header('X-Debug-mtimeIsNow: ' . $now);
    header('Expires: ' . gmdate('D M d H:i:s Y', 1)); // old
    header('Cache-Control: no-store, must-revalidate, post-check=1, pre-check=2');
    // this breaks /opt/test/thread/82/lock?prettyPrint=1
    //header('Proxy-Connection: keep-alive');
    return;
  }

  // maybe cache this
  $ssl = false;
  if (isset($_SERVER['HTTPS'])) {
    $ssl = $_SERVER['HTTPS'] === 'on';
  }

  $public = '';
  // but if PHP_AUTH_USER is set, we probably don't want them to cache tbh
  // isset($_SERVER['PHP_AUTH_USER']) ||
  // yea the intent of this function is different than originally imagined
  // we're not always caching
  // we should only be caching if mtime/etag are set maybe...
  // we really need to be informed, and not sure what the default should be
  // something if we're viewing personalized content or not
  if ($ssl) {
    // public says to cache through SSL & httpauth
    // otherwise just cached in memory only
    $public = 'public, ';
  }
  header('Cache-Control: ' . $public . 'must-revalidate');
  header('Vary: Accept-Encoding');

  // CF is also injecting this without checking for it...
  //header('X-Frame-Options: SAMEORIGIN');

  // cache this
  if (!$lastMod && $mtime) {
    $lastMod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
  }
  if ($lastMod) {
    header('Last-Modified: ' . $lastMod);
  }

  // convert fileSize && mtime into eTag
  if ($fileSize && !$etag && $mime) { // don't generate if not needed
    $etag = dechex($mtime) . '-' . dechex($fileSize);
  }

  // etag available?
  if ($etag) {
    // set etag
    header('ETag: "' . $etag . '"');
  }

}

// when version is specified in the URL
function cachePageContentsForever($mtime = 0) {
  global $now;
  if ($mtime) {
    $lastMod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    header('Last-Modified: ' . $lastMod);
  }
  header('Expires: '. gmdate('D, d M Y H:i:s',$now + 31536000).' GMT'); // 10 years
  header('Cache-Control: max-age=31536000');
  header('Vary: Accept-Encoding');
  // we should still do 304 processing
  // so we don't waste bandwidth...
}

// used in router to check incoming request to see if it's cached or not
// FIXME: redo these parameters
// really an incoming request thing but drives responses
// we always set headers here
// FIXME: allow expires param

// mtime === 0 => doesn't set lastmod
// mtime === now => set lastmod
function checkCacheHeaders($mtime, $options = false) {
  //header('X-Debug-checkCacheHeaders-mtime: ' . $mtime);
  extract(ensureOptions(array(
    'contentType' => '',
    'fileSize' => 0,
    'etag' => false,
  ), $options));

  if ($etag !== false) {
    $sendEtag = $etag;
    // are we in the W/"" format?
    if ($etag[0] !== 'W') {
      $sendEtag = 'W/"' . $etag . '"';
    }
  }

  $headers = getallheaders();

  // we don't always know the size
  if ($fileSize && !$etag && $mtime) { // don't generate if not needed
    $etag = dechex($mtime) . '-' . dechex($fileSize);
  }

  // mtime isn't always valid
  $lastmod = false;
  if ($mtime) {
    $lastmod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
  }

  // 304 processing
  $allowCacheCheck = !isset($headers['Cache-Control']) || (isset($headers['Cache-Control']) && $headers['Cache-Control'] !== 'no-cache');
  $etagPass = $etag && !empty($headers['If-None-Match'])
              // do we need a string search instead of a compare?
           && strpos($headers['If-None-Match'], $etag) !== false;
  // FIXME: datetime comparison!
  $mtimePass = $lastmod && !empty($headers['If-Modified-Since'])
           && $lastmod == $headers['If-Modified-Since'];
  if (0) {
    //header('X-Debug-checkCacheHeaders-cc: ' . $headers['Cache-Control']);
    header('X-Debug-checkCacheHeaders-acc: ' . ($allowCacheCheck ? 'allowed' : 'blocked'));
    //header('X-Debug-checkCacheHeaders-in: ' . isset($headers['If-None-Match']) ? $headers['If-None-Match'] : '');
    //header('X-Debug-checkCacheHeaders-im: ' . isset($headers['If-Modified-Since']) ? $headers['If-Modified-Since'] : '');
    if ($etag) {
      header('X-Debug-checkCacheHeaders-ep: ' . ($etagPass ? 'accepted' : 'unmatched'));
      header('X-Debug-checkCacheHeaders-server: ' . $etag);
      header('X-Debug-checkCacheHeaders-browser: ' . isset($headers['If-None-Match']) ? $headers['If-None-Match'] : '');
    }
    if ($lastmod) {
      header('X-Debug-checkCacheHeaders-me: ' . ($mtimePass ? 'accepted' : 'unmatched'));
    }
  }
  // CF will hide these
  if ($allowCacheCheck && ($etagPass || $mtimePass)) {
    header('HTTP/1.1 304 Not Modified');
    header('X-Debug-checkCacheHeaders-HIT: 304');
    // weird chrome can't make etag match here
    // shift-reload gives one but this always give another formating of a string
    _doHeaders($mtime, array('contentType' => $contentType, 'lastMod' => $lastmod, 'etag' => $etag));
    // maybe just exit?
    return true;
  }
  // you know if this is a 304 or not, this path is not 304
  _doHeaders($mtime, array('contentType' => $contentType, 'lastMod' => $lastmod, 'etag' => $etag));
  // maybe return etag so it doesn't have to be generated?
  return false;
}

?>