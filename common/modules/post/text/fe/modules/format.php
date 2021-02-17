<?php

$params = $getModule();

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

$replaces = array(
  '/' . preg_quote('&gt;&gt;&gt;#') . '\/?(\w+)\/?(\s+)/m' => function ($matches) use ($io) {
    return '<a
      href="' . $io['boardUri'] . '/catalog#' . $io['boardUri'] . '-/' .
      $matches[1] . '/">&gt;&gt;&gt;#/' . $matches[1].'/</a>' . $matches[2];
  },
// we need the thread number for that post on that board
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/(\d+)\/?(\s*)/m' => function ($matches) use ($io) {
    global $btLookups;
    $threadId = $btLookups[$io['boardUri']][$matches[2]];
    return '<a class="quote"
      href="' . $matches[1] . '/thread/' . $threadId . '#' . $matches[2] . '">&gt;&gt;&gt;/' .
      $matches[1] . '/' . $matches[2] . '</a>' . $matches[3];
  },
  '/' . preg_quote('&gt;&gt;&gt;') . '\/?(\w+)\/?(\s+)/m' => function ($matches) {
    return '<a class="quote"
      href="' . $matches[1] . '">&gt;&gt;&gt;/' .
      $matches[1] . '/</a>' . $matches[2];
  },
// hrm could verify the post exists...
  '/' . preg_quote('&gt;&gt;') . '(\d+)\/?(\s+)/m' => function ($matches) {
    return '<a class="quote"
      href="#' . $matches[1] . '">&gt;&gt;/' .
      $matches[1] . '/</a>' . $matches[2];
  },
);

// have to use anonymous functions
$io['safeCom'] = preg_replace_callback_array($replaces, $io['safeCom']);

?>