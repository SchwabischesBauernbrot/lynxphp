<?php

$params = $getModule();

// io is:
$details = $io['details'];
$fieldName = $io['field'];
$value = $io['value'];

if (!$value) {
  $value = theme_getDefault();
}
//echo "value[$value]<br>\n";

$io['html'] = '
<style>
.dummie-themes-ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
}

/* wrap on mobile when we\'re out of width */
.dummie-themes-li {
  float: left;
}

  .thumbnail-container {
    text-align: center;
    font-family: arial, helvetica, sans-serif;
    padding-left: auto;
    padding-right: auto;
    width: 180px;
    /* desired res / 4  + 30px for the text stuff and padding */
    height: 160px;
    overflow: hidden;
    position: relative;
  }


  .thumbnail {
    position: relative;
    /* desired res / 4 */
    width: 180px;
    height: 130px;
    display: inline-block;
    transform: scale(0.25);
    transform-origin: 0 0;
  }

  .thumbnail:after {
    content: "";
    /* cover the entire thumbnail */
    width: 720px;
    height: 520px;
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
  }

  .thumbnail iframe {
    /* desire res to render at */
    width: 720px;
    height: 520px;
  }
</style>

<ul class="dummie-themes-ul">
';

//$first = array_shift($details['options']);
asort($details['options']);
//array_unshift($details['options'], $first);

//global $shared;
//echo "<pre>shared[", print_r($shared, 1), "]</pre>\n";
//$themes = array_keys($shared['themes']);

//global $now;
foreach($details['options'] as $v => $l) {
  $sel = $v === $value ? 'checked ' : '';
  /*
  $mtime = $now;
  if (file_exists('css/themes/' . $v . '.css')) {
    $mtime = filemtime('css/themes/' . $v . '.css');
  }
  // ?v=' . $mtime . '
  */
  //if ($v === 0) $v = theme_getDefault();
  //echo "file[", $v, "] value[$value] sel[$sel]<br>\n";
  $io['html'] .= '
<li class="dummie-themes-li">
  <div class="thumbnail-container" title="' . $l . '">
    <label>
      <h5><input type="radio" name="' . $fieldName . '" value="' . $v. '"' . $sel . '>' . $l . '</h5>
      <div class="thumbnail">
        <iframe src="/user/settings/themedemo/' . $v . '.html" frameborder="0" scroll="no" loading="lazy"></iframe>
      </div>
    </label>
  </div>
</li>
';
}

$io['html'] .= '
</ul>
<br clear="both">
';

?>