<?php

// common response utilities

// requires config (frontend?)

// 304 just decreases bandwidth (for slow link/cost)

// FIXME: rename make public
// FIXME: change prototype
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
  //if ($etag) header('X-Debug-etag: ' . $etag);

  // don't cache this
  if ($mtime === $now) {
    // don't cache
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
function checkCacheHeaders($mtime, $options = false) {
  //header('X-Debug-checkCacheHeaders-mtime: ' . $mtime);
  extract(ensureOptions(array(
    'contentType' => '',
    'fileSize' => 0,
    'etag' => false,
  ), $options));

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
  if (0) {
    header('X-Debug-checkCacheHeaders-cc: ' . $headers['Cache-Control']);
    header('X-Debug-checkCacheHeaders-in: ' . $headers['If-None-Match']);
    header('X-Debug-checkCacheHeaders-im: ' . $headers['If-Modified-Since']);
  }
  if ((!isset($headers['Cache-Control']) || (isset($headers['Cache-Control']) && $headers['Cache-Control'] !== 'no-cache'))
      && (
        ($etag && !empty($headers['If-None-Match'])
              // do we need a string search instead of a compare?
           && strpos($headers['If-None-Match'], $etag) !== false) ||
        ($lastmod && !empty($headers['If-Modified-Since'])
           && $lastmod == $headers['If-Modified-Since']))
      ) {
    header('HTTP/1.1 304 Not Modified');
    _doHeaders($mtime, array('contentType' => $contentType, 'lastMod' => $lastmod, 'etag' => $etag));
    // maybe just exit?
    return true;
  }
  _doHeaders($mtime, array('contentType' => $contentType, 'lastMod' => $lastmod, 'etag' => $etag));
  // maybe return etag so it doesn't have to be generated?
  return false;
}

?>