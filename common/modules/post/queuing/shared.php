<?php

// this data and functions used for all module php code

// function are automatically exported

// allow export of data as $shared in your handlers and modules

/*
FIXME: logging results

potential settings:
queue new threads/replies
- made on clearnet / made on tor
- high spam assassin score
- with links
queue reports

who can vote on queues (for each queue): clearnet-anons, all-anons, users, users that follow the board, jannies only

who can see the denials (logging)

scoring for logged in accounts

===

if (type && from && filter) then queue

type from filter
(thread/reply) (clearnet/tor)

new_threads_all
new_threads_spam
new_threads_hasLink
new_reply_all
new_reply_spam
new_reply_hasLink


typically
thread/reply
clearnet/tor
clearnet

captcha/queue

from tor: no queue / queue
OR
high spam score: no queue / queue
has link: no queue / queue
OR
is new thread: no queue / queue
is new reply: no queue / queue


when to queue: checkbox


when to captcha

===

but BO creates custom tags? no they set the board settings
+be: software defines the names of possible tags
+be: post gets tagged
and then we process flags

tags posts:
+conditions, set this tag


queueing:
// get a list of tags
// if has this tag set queue to on/off
// what about order?
queue post with these tags, checkmarks:

*/
// specific ones are copied out of here..
return array(
  'board_settings_fields' => array(
    //
    'queueing_mode' => array(
      'label' => 'New Posts (Clearnet)',
      'type'  => 'select',
      'options' => array(
        '' => 'immediate',
        'community' => 'community queue',
        //'moderator' => 'moderator queue',
      )
    ),
    'queueing_mode_hs' => array(
      'label' => 'New Posts (Hidden Service)',
      'type'  => 'select',
      'options' => array(
        '' => 'immediate',
        'community' => 'community queue',
        //'moderator' => 'moderator queue',
      )
    ),
    'queueing_mode_link' => array(
      'label' => 'New Posts (with links)',
      'type'  => 'select',
      'options' => array(
        '' => 'immediate',
        'community' => 'community queue',
        //'moderator' => 'moderator queue',
      )
    ),
  ),
  'community_moderate_fields' => array(
    'captcha' => array(
      'label' => 'CAPTCHA',
      'type'  => 'captcha',
    ),
  ),
);

?>