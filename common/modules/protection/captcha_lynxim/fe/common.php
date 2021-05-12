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

?>