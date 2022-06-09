<?php

$module = $getModule();

$text = $io['post']['com'];

$io['strings_match'] = post_strings_getCount($text);

?>