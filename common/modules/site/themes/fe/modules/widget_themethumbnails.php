<?php

$params = $getModule();

// io is:
$details = $io['details'];
$fieldName = $io['field'];
$value = $io['value'];

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

$first = array_shift($details['options']);
asort($details['options']);
array_unshift($details['options'], $first);
foreach($details['options'] as $v => $l) {
  $sel = $v === $value ? 'checked ' : '';
  $io['html'] .= '
<li class="dummie-themes-li">
  <div class="thumbnail-container" title="' . $l . '">
    <label>
      <h5><input type="radio" name="' . $fieldName . '" value="' . $v. '"' . $sel . '>' . $l . '</h5>
      <div class="thumbnail">
        <iframe src="/user/settings/themedemo/' . $v . '/" frameborder="0" scroll="no"></iframe>
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