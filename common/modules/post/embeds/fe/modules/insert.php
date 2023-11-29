<?php

$params = $getModule();

// normalize
// youtube.com/watch?v= format
// (?:^|\s)
/* These were failing the above
https://youtube.com/watch?v=qabmngqxPtc
https://youtube.com/watch?v=XfgDZhknv7g
*/
$io['safeCom'] = preg_replace('/https?:\/\/(:?www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9-_]+)[a-zA-Z0-9=&]*(#t=[0-9a-zA-z]+)?(?:(\s)|$)/i', '[youtube]\2[/youtube]\4', $io['safeCom']);
// yotu.be format
$io['safeCom'] = preg_replace('/https?:\/\/(:?www\.)?youtu\.be\/([a-zA-Z0-9-_]+)[a-zA-Z0-9=&]*(#t=[0-9a-zA-z]+)?(?:(\s)|$)/i', '[youtube]\2[/youtube]\4', $io['safeCom']);
// niconico
// tiktok

// so the nojs version should just be a normal (normalized) link
// could could slap an image on here...
// JS can enhance this further
$ytUrl = 'https://youtube.com/watch?v=';
// FIXME: we could communicate with the YT API and get the title
// though should be store in the db on the BE
$io['safeCom'] = preg_replace('/\[youtube\]([^\[]+)\[\/youtube\]/i',
  '<span class="youtube_wrapper"><a rel="nofollow noreferrer" target=_blank href="' . $ytUrl . '\1" title="YouTube Link">' . $ytUrl . '\1</a></span>',
  $io['safeCom']);
?>