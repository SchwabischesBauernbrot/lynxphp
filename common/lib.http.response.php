<?php

// common response utilities

// requires config (frontend?)

// private
function _doHeaders($mtime, $options = false) {
  // no need for headers when generating pages
  if (IN_GENERATE) return;

  global $now;

  // unpack options
  extract(ensureOptions(array(
    'contentType'  => 'text/html',
    'lastMod'   => '',
    'fileSize' => 0,
  ), $options));

  // why is this empty?
  // being empty in chrome makes html page not render as html in nginx/php-fpm
  header('Content-Type: ' . $contentType);
  if ($mtime === $now) {
    // don't cache
    header('Expires: ' . gmdate('D M d H:i:s Y', 1)); // old
    header('Cache-Control: no-store, must-revalidate, post-check=1, pre-check=2');
    // this breaks /opt/test/thread/82/lock?prettyPrint=1
    //header('Proxy-Connection: keep-alive');
    return;
  }
  // cache this
  if (!$lastMod) {
    $lastMod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
  }
  header('Last-Modified: ' . $lastMod);
  if ($fileSize) {
    header('Content-Length: ' . $fileSize);
  }
  $ssl = false;
  if (isset($_SERVER['HTTPS'])) {
    $ssl = $_SERVER['HTTPS'] === 'on';
  }
  $public='';
  if (isset($_SERVER['PHP_AUTH_USER']) || $ssl) {
    // public says to cache through SSL & httpauth
    // otherwise just cached in memory only
    $public = 'public, ';
  }
  header('Cache-Control: ' . $public . 'must-revalidate');
  header('Vary: Accept-Encoding');
  if ($fileSize) { // don't generate if not needed
    $etag = dechex($mtime) . '-' . dechex($fileSize);
    header('ETag: "' . $etag . '"');
  }
  // CF is also injecting this without checking for it...
  //header('X-Frame-Options: SAMEORIGIN');
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

function checkCacheHeaders($mtime, $options = false) {
  $contentType = '';
  $fileSize = 0;
  if ($options) {
    if (isset($options['contentType'])) $contentType = $options['contentType'];
    if (isset($options['fileSize']))    $fileSize = $options['fileSize'];
  }

  $headers = getallheaders();

  // we don't always know the size
  $etag = false;
  if ($fileSize) { // don't generate if not needed
    $etag = dechex($mtime) . '-' . dechex($fileSize);
  }
  $lastmod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
  if ((!isset($headers['Cache-Control']) || (isset($headers['Cache-Control']) && $headers['Cache-Control'] !== 'no-cache'))
      && (
        ($etag && !empty($headers['If-None-Match'])
           && strpos($headers['If-None-Match'], $etag) !== false) ||
        (!empty($headers['If-Modified-Since'])
           && $lastmod == $headers['If-Modified-Since']))
      ) {
    header('HTTP/1.1 304 Not Modified');
    _doHeaders($mtime, array(
      'contentType' => $contentType, 'lastMod' => $lastmod));
    // maybe just exit?
    return true;
  }
  _doHeaders($mtime, array('contentType' => $contentType, 'lastMod' => $lastmod));
  // maybe return etag so it doesn't have to be generated?
  return false;
}

?>