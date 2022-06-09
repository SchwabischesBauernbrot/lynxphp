<?php

function catalogSearchQuote($matches) {
  // this probably won't work..
  global $io;
  return '<a
    href="' . $io['boardUri'] . '/catalog#' . $io['boardUri'] . '-/' .
    $matches[1] . '/">&gt;&gt;&gt;#/' . $matches[1].'/</a>' . $matches[2];
}

function crossQuote($matches) {
  return '<a class="quote"
    href="' . $matches[1] . '/thread/#' . $matches[2] . '">&gt;&gt;&gt;/' .
    $matches[1] . '/' . $matches[2] . '</a>' . $matches[3];
}

function crossBoard($matches) {
  return '<a class="quote"
    href="' . $matches[1] . '">&gt;&gt;&gt;/' .
    $matches[1] . '/</a>' . $matches[3];
}

function replyQuote($matches) {
  return '<a class="quote"
    href="#' . $matches[1] . '">&gt;&gt;/' .
    $matches[1] . '/</a>' . $matches[2];
}

?>
