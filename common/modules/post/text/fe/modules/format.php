<?php

$params = $getModule();

// non-quote stuff...

// spoiler
$io['safeCom'] = preg_replace('/\[spoiler\]([\s\S]+?)\[\/spoiler\]/', '<span class="spoiler">\1</span>',$io['safeCom']);
$io['safeCom'] = preg_replace('/\|\|([\s\S]+?)\|\|/', '<span class="spoiler">\1</span>',$io['safeCom']);

// redText / title
$io['safeCom'] = preg_replace('/==([\s\S]+?)==/', '<span class="title">\1</span>',$io['safeCom']);
// bold
$io['safeCom'] = preg_replace('/\'\'\'([\s\S]+?)\'\'\'/', '<span class="bold">\1</span>',$io['safeCom']);
// italics/em
$io['safeCom'] = preg_replace('/\*\*([\s\S]+?)\*\*'.'/', '<span class="em">\1</span>',$io['safeCom']);
$io['safeCom'] = preg_replace('/\'\'([\s\S]+?)\'\'/', '<span class="em">\1</span>',$io['safeCom']);
// underline
$io['safeCom'] = preg_replace('/__([\s\S]+?)__/', '<span class="underline">\1</span>',$io['safeCom']);
// strike
$io['safeCom'] = preg_replace('/~~([\s\S]+?)~~/', '<span class="strike">\1</span>',$io['safeCom']);
// endchan memes
$io['safeCom'] = preg_replace('/\[meme\]([\s\S]+?)\[\/meme\]/', '<span class="meme">\1</span>',$io['safeCom']);
$io['safeCom'] = preg_replace('/\[autism\]([\s\S]+?)\[\/autism\]/', '<span class="autism">\1</span>',$io['safeCom']);
// aa
$io['safeCom'] = preg_replace('/\[aa\]([\s\S]+?)\[\/aa\]/', '<span class="aa">\1</span>',$io['safeCom']);
// code
$io['safeCom'] = preg_replace('/\[code\]([\s\S]+?)\[\/code\]/', '<span class="code">\1</span>',$io['safeCom']);

// greentext
// \d+
$io['safeCom'] = preg_replace('/^&gt; ?((?!&gt;\/?|&gt;&gt;\/?\w+()?|&gt;&gt;#\/).*)/m', '<span class="greentext">&gt; \1</span>',$io['safeCom']);

// orangetext
$io['safeCom'] = preg_replace('/^&lt; ?((?!&gt;\/?|&gt;&gt;\/?\w+()?|&gt;&gt;#\/).*)/m', '<span class="orangetext">&lt; \1</span>',$io['safeCom']);
// monospaced
$io['safeCom'] = preg_replace('/`(.+?)`/m', '<span class="mono">\1</span>',$io['safeCom']);
// detected
$io['safeCom'] = preg_replace('/\(\(\((.+?)\)\)\)/m', '<span class="detected">\1</span>',$io['safeCom']);

// <a class="quote" href="/test/thread/121.html#124">&gt;&gt;124</a>

// skip quote processing if we can
if (strpos($io['safeCom'], '&gt;&gt;') === false) {
  return;
}

/*
$replaces = array(
// catalogSearchQuotes
  '/' . preg_quote('&gt;&gt;&gt;#') . '\/?(\w+)\/?(\s+)/m' => '<a href="'.$io['boardUri'].'/catalog#'.$io['boardUri'].'-/\1/">&gt;&gt;&gt;#/\1/</a>\2',
// crossQuote
// we need the thread number for that post on that board
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/(\d+)\/?(\s+)/m' => '<a class="quote" href="\1/thread/#\2">&gt;&gt;&gt;/\1/\2</a>\3',
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/?(\s+)/m' => '<a class="quote" href="\1/">&gt;&gt;&gt;/\1/\2</a>\3',
// quote/reply
// hrm could verify the post exists...
  '/' . preg_quote('&gt;&gt;') . '(\d+)\/?(\s+)/m' => '<a class="quote" href="#\1">&gt;&gt;\1</a>\2',
);

$io['safeCom'] = preg_replace(array_keys($replaces), array_values($replaces), $io['safeCom']);
*/

// php 5.x
// not a fan of the loop
// and the io capture is going to be wonky
/*
$replaces = array(
  '/' . preg_quote('&gt;&gt;&gt;#') . '\/?(\w+)\/?(\s+)/m' => 'catalogSearchQuotes',
// we need the thread number for that post on that board
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/(\d+)\/?(\s+)/m' => 'crossQuote',
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/?(\s+)/m' => 'crossBoard',
// hrm could verify the post exists...
  '/' . preg_quote('&gt;&gt;') . '(\d+)\/?(\s+)/m' => 'replyQuote',
);
foreach($replaces as $regex => $cb) {
  $io['safeCom'] = preg_replace_callback($regex, $cb, $io['safeCom']);
}
*/

/*
//echo "<hr>\n";
preg_match_all('/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/(\d+)\/?(\s+)/m', $io['safeCom'], $quotes, PREG_SET_ORDER);
$btLookups = array();
foreach($quotes as $i=>$q) {
  //echo "<pre>$i => ", print_r($quote, 1), "</pre>\n";
  $btLookups[$q[1]][$q[2]] = true;
}
echo "<hr>\n";
if (count($btLookups)) {
  echo "<pre>", print_r($btLookups, 1), "</pre>\n";
}
*/

//echo "<pre>", print_r($io['safeCom'], 1), "</pre>\n";

$replaces = array(
  // >>>/malform/35@ThreadNum
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/(\d+)@(\d+)(\s*)/m' => function ($matches) use ($io) {
    global $btLookups;
    $btLookups[$matches[1]][$matches[2]] = $matches[3];
    // obsecure output so it's not re-interpreted
    // &sol; is a "/"

    $addBoard = false;
    $targetUri = $matches[1];
    //if ($io['inMixedBoards']) $addBoard = true;
    if ($targetUri !== $io['boardUri']) {
      $addBoard = true;
    }

    $board = '';
    if ($addBoard) {
      $board = $targetUri . '&sol;';
    }
    // &sol; is /
    $str = '<a class="quote"
      href="' . $targetUri . '/thread/' . $matches[3] . '.html#' . $matches[2] . '">&gt;&gt;&sol;' .
      $board . $matches[2] . '/</a>' . $matches[4];
    //echo "<pre>matches[", htmlspecialchars($str), "]", print_r($matches, 1), "</pre>\n";

    return $str;
  },
  '/' . preg_quote('&gt;&gt;&gt;#') . '\/?(\w+)\/?(\s+)/m' => function ($matches) use ($io) {
    return '<a
      href="' . $io['boardUri'] . '/catalog.html#' . $io['boardUri'] . '-/' .
      $matches[1] . '/">&gt;&gt;&gt;#/' . $matches[1].'/</a>' . $matches[2];
  },
// we need the thread number for that post on that board
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/(\d+)\/?(\s*)/m' => function ($matches) {
    global $btLookups;
    $threadId = $btLookups[$matches[1]][$matches[2]];
    // quote by threadId/postId
    return '<a class="quote"
      href="' . $matches[1] . '/thread/' . $threadId . '#' . $matches[2] . '">&gt;&gt;&gt;/' .
      $matches[1] . '/' . $matches[2] . '</a>' . $matches[3];
  },
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/?(\s+)/m' => function ($matches) {
    // board reference
    return '<a class="quote"
      href="' . $matches[1] . '">&gt;&gt;&gt;/' .
      $matches[1] . '/</a>' . $matches[2];
  },
// hrm could verify the post exists...
  '/' . preg_quote('&gt;&gt;') . '(\d+)\/?(\s+)/m' => function ($matches) use ($io) {
    global $btLookups;
    $threadId = isset($btLookups[$io['boardUri']][$matches[1]]) ? $btLookups[$io['boardUri']][$matches[1]] : '';
    // quote by postid
    return '<a class="quote"
      href="' . $io['boardUri'] . '/thread/' . $threadId . '#' . $matches[1] . '">&gt;&gt;' .
      $matches[1] . '</a>' . $matches[2];
  },
  // >>/malform/35
  '/' . preg_quote('&gt;&gt;') . '\/?(\w+)\/(\d+)\/?(\s*)/m' => function ($matches) {
    global $btLookups;
    $threadId = $btLookups[$matches[1]][$matches[2]];
    return '<a class="quote"
      href="' . $matches[1] . '/thread/' . $threadId . '#' . $matches[2] . '">&gt;&gt;/' .
      $matches[1] . '/' . $matches[2] . '/</a>' . $matches[3];
  },
);

// have to use anonymous functions
$io['safeCom'] = preg_replace_callback_array($replaces, $io['safeCom']);

?>