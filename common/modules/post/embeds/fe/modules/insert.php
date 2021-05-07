<?php

$params = $getModule();

// normalize
// youtube.com/watch?v= format
$io['safeCom'] = preg_replace('/(?:^|\s)https?:\/\/(:?www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9-_]+)[a-zA-Z0-9=&]*(#t=[0-9a-zA-z]+)?(?:(\s)|$)/i', '[youtube]\2[/youtube]\4', $io['safeCom']);
// yotu.be format
$io['safeCom'] = preg_replace('/(?:^|\s)https?:\/\/(:?www\.)?youtu\.be\/([a-zA-Z0-9-_]+)[a-zA-Z0-9=&]*(#t=[0-9a-zA-z]+)?(?:(\s)|$)/i', '[youtube]\2[/youtube]\4', $io['safeCom']);
// niconico
// tiktok

// so the nojs version should just be a normal (normalized) link
// could could slap an image on here...
// JS can enhance this further
$ytUrl = 'https://youtube.com/watch?v=';
$io['safeCom'] = preg_replace('/\[youtube\]([^\[]+)\[\/youtube\]/i',
  '<span class="youtube_wrapper"><a rel=nofollow target=_blank href="' . $ytUrl . '\1">' . $ytUrl . '\1</a></span>',
  $io['safeCom']);
?>