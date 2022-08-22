<?php

// why are these here?

function getBaseDistorts($w, $h) {
  return array(
    array('origin'=>array('x'=>0,'y'=>0), 'destiny'=>array('x'=>0,'y'=>0)),
    array('origin'=>array('x'=>0,'y'=>$h), 'destiny'=>array('x'=>0,'y'=>$h)),
    array('origin'=>array('x'=>$w,'y'=>0), 'destiny'=>array('x'=>$w,'y'=>0)),
    array('origin'=>array('x'=>$w,'y'=>$h), 'destiny'=>array('x'=>$w,'y'=>$h)),
  );
}
function getDistorts($w, $h) {
  $distorts = getBaseDistorts($w, $h);
  $amountOfDistorts = rand(3, 5);
  $portionSize = $w / $amountOfDistorts;
  for($i = 0; $i < $amountOfDistorts; $i++) {
    $distortOrigin = array(
      'x'=>rand($portionSize * $i, $portionSize * ($i + 1)),
      'y'=>rand(0, $h)
    );
    $minWidthDestiny = $distortOrigin['x'] - 30;
    $minHeightDestiny = $distortOrigin['y'] - 30;
    $distortLimitX = $distortOrigin['x'] + 30;
    $distortLimitY = $distortOrigin['y'] + 30;

    $distortDestination = array(
      'x' => rand($minWidthDestiny,$distortLimitX),
      'y' => rand($minHeightDestiny,$distortLimitY)
    );

    $distort = array('origin' => $distortOrigin, 'destiny' => $distortDestination);
    $distorts[] = $distort;
  }
  return $distorts;
}

function distortImage($w, $h) {
  $distorts = getDistorts($w, $h);
  $command = '-distort Shepards \'';
  foreach($distorts as $i => $distort) {
    if ($i) {
      $command .= ' ';
    }
    $command .= $distort['origin']['x'] . ',' . $distort['origin']['y'] . ' ' .
      $distort['destiny']['x'] . ',' . $distort['destiny']['y'];
  }
  return $command . '\' ';
}

function captcha_generateCode() {
  $text = '';
  for($i = 0; $i < 6; $i++) {
    $text .= chr(rand(0, 100) > 50 ? rand(97, 97 + 6) : rand(48, 57));
  }
  return $text;
}

function captcha_register($text) {
  global $scratch, $now;
  $captcha_id = md5(uniqid());
  $captchas = $scratch->get('captchas');
  if (!is_array($captchas)) $captchas = array();
  $captchas[$captcha_id] = array(
    // probably don't need to restore id, since we're keying by it
    //'id' => $captcha_id,
    'value' => $text,
    'session' => isset($_COOKIE['session']) ? $_COOKIE['session'] : '',
    'ip' => getip(),
    // but needs to be validate for an entire page load...
    // for no js...
    // good for 24 hours => 5 minutes
    'expires' => (int)($now) + (5 * 60),
  );
  $scratch->set('captchas', $captchas);
  return array(
    'id'      => $captcha_id,
    'expires' => $captchas[$captcha_id]['expires'],
  );
}

function captcha_generateImage($text) {
  $level = 1;
  $width = 286;
  $height = 100;
  $imageFont = 'AvantGarde-Book';  // convert -list font

  $lineOffset = rand(-20, 20) / $level;
  $command = '';
  for($i = 0; $i < 5 * $level; $i++) {
    $lineWidth = rand(10, 20) / $level;
    if ($i) {
      $command .= ' ';
    }
    $command .= 'rectangle 0,' . $lineOffset . ' ' . $width . ',';
    $command .= $lineWidth + $lineOffset;
    $lineOffset += rand(20, 30) / $level;
    $lineOffset += $lineWidth;
  }
  //$command .= '\" -write mpr:mask +delete';


  $cli = '/usr/bin/convert -size ' . $width . 'x' . $height . ' xc: -draw "' . $command . '" -write mpr:mask +delete';
  $cli .= ' xc: -pointsize 70 -gravity center -font ' . $imageFont;
  $cli .= ' -draw "text 0,0 \'' . $text . '\'" -write mpr:original +delete';
  $cli .= ' mpr:original -negate -write mpr:negated +delete';
  $cli .= ' mpr:negated mpr:original mpr:mask -composite';
  $cli .= ' ' . distortImage($width, $height) . '-blur 0x1 jpg:-';

  $data = `$cli`;
  return $data;
}

?>