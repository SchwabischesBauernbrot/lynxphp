<?php

include '../frontend_lib/lib/lib.listing.php'; // component_post_listing

$params = $getHandler();

global $boardUri;
$boardUri = $request['params']['uri'];

global $boardData;
if (!$boardData) {
  $boardData = getter_getBoard($boardUri);
}

// get a list of all posts
global $floodData;
$floodData = $pkg->useResource('list', array('uri' => $boardUri));

global $baseUrl;
$baseUrl = $boardUri . '/settings/flood';

$action = getQueryField('action');
if ($action === 'view') {
  $hash = getQueryField('hash');
  if ($hash) {
    $html = showPostsDetails($floodData['hash2posts'][$hash]);
    $html .= '
    <a href="' . $baseUrl .'?action=yes&hash=' . $hash .'">Sure...</a>
    ';
    return wrapContent($html);
  }
} else
if ($action === 'nukeall') {
  $hash = getQueryField('hash');
  if ($hash) {
    global $packages;
    //echo "<pre>", print_r($floodData['hash2posts'][$hash], 1), "</pre>\n";
    $postFields = array();
    // knock off first
    foreach($floodData['hash2posts'][$hash] as $pno) {
      //echo "pno[$pno]<br>\n";
      $postFields[$boardUri . '-ThreadNum-' . $pno] = true;
    }
    //print_r($postFields);
    $res = $packages['post_actions']->useResource('content_actions',
      array('action' => 'delete'), array('addPostFields' => $postFields)
    );
    print_r($res);
    echo '<a href="/', $baseUrl, '">back</a>';
    return;
  }
} else
if ($action === 'nuke') {
  $hash = getQueryField('hash');
  if ($hash) {
    global $packages;
    //echo "<pre>", print_r($floodData['hash2posts'][$hash], 1), "</pre>\n";
    $postFields = array();
    // knock off first
    array_shift($floodData['hash2posts'][$hash]);
    foreach($floodData['hash2posts'][$hash] as $pno) {
      //echo "pno[$pno]<br>\n";
      $postFields[$boardUri . '-ThreadNum-' . $pno] = true;
    }
    //print_r($postFields);
    $res = $packages['post_actions']->useResource('content_actions',
      array('action' => 'delete'), array('addPostFields' => $postFields)
    );
    print_r($res);
    echo '<a href="/', $baseUrl, '">back</a>';
    return;
  }
}

// we should do compound smarter things first
$post2hash = array();
foreach($floodData['hash2posts'] as $hash => $posts) {
  foreach($posts as $pno) {
    if (!isset($post2hash[$pno])) $post2hash[$pno] = array();
    $post2hash[$pno][] = $hash;
  }
}

global $pno2ip;
$pno2ip = array();
foreach($floodData['ip2posts'] as $ip => $posts) {
  //echo "[$ip] count[", count($posts), "]<br>\n";
  foreach($posts as $pno) {
    if (isset($pno2ip[$pno])) echo "two ips new[$ip] old[", $pno2ip[$pno], "] on [$pno]?<br>\n";
    //echo "[$pno] => [$ip]<br>\n";
    $pno2ip[$pno] = $ip;
  }
}

global $pno2msg;
$pno2msg = array();
foreach($floodData['com2posts'] as $msg => $posts) {
  foreach($posts as $pno) {
    if (isset($pno2msg[$pno])) echo "two msgs new[$ip] old[", $pno2msg[$pno], "] on [$pno]?<br>\n";
    $pno2msg[$pno] = $msg;
  }
}

if ($action === 'analysis') {
  $hash = getQueryField('hash');
  if ($hash) {
    $html = showHashDetails($hash);
    // number of threads?
    $html .= '<a href="' . $baseUrl . '?action=nuke&hash=' . $hash . '">nuke all but one</a><br>';
    $html .= '<a href="' . $baseUrl . '?action=nukeall&hash=' . $hash . '">nuke all</a><br>';
    $html .= '<a href="' . $baseUrl . '?action=view&hash=' . $hash . '">view</a><br>';

    $html .= showPostsDetails($floodData['hash2posts'][$hash]);

    wrapContent($html);
    return;
  }
}

$html = '';

function analyzeList($posts) {
  global $pno2ip, $pno2msg;
  $ips = array();
  $msgs = array();
  foreach($posts as $pno) {
    if (isset($pno2ip[$pno])) {
      $ip = $pno2ip[$pno];
      if (!isset($ips[$ip])) $ips[$ip] = 0;
      $ips[$ip] ++;
    } else {
      // tor or we just don't have...
      //echo "no ip for [$pno]<br>\n";
    }
    if (isset($pno2msg[$pno])) {
      $msg = $pno2msg[$pno];
      if (!isset($msgs[$msg])) $msgs[$msg] = 0;
      $msgs[$msg] ++;
    } else {
      // 0 or deleted?
      //echo "no msg for [$pno]<br>\n";
    }
  }
  return array(
    'ips' => count($ips),
    'msgs' => count($msgs),
    'iplist' => $ips,
    'msglist' => array_keys($msgs),
  );
}

function showPostsDetails($pnos) {
  global $floodData, $boardUri, $boardData;

  $posts = array();
  foreach($pnos as $pno) {
    if (!isset($floodData['posts'][$pno])) {
      echo "missing <a href=\"/$boardUri/preview/$pno.html\" target=_blank>$pno</a><br>\n";
      continue;
    }
    $posts[] = $floodData['posts'][$pno];
  }
  return component_post_listing($posts, $boardUri, $boardData);
}

function showPostDetails($pno) {
  global $floodData;
  $score = empty($floodData['postScores'][$pno]) ? 0 : $floodData['postScores'][$pno];
  return '<li>' . $pno . ' (Score: ' . $score . ')' . "\n";
}

//
function showHashDetails($hash) {
  global $floodData, $baseUrl;
  $html = '';
  //echo "<pre>", print_r($floodData['hash2posts'][$hash], 1), "</pre>\n";
  /*
  $html .= '<ul>';
  foreach($floodData['hash2posts'][$hash] as $pno) {
    $html .= showPostDetails($pno);
  }
  $html .= '</ul>';
  */
  // tbh we don't need a complete list
  // we need to know how many unique ips and unique messages
  // and then a link to nuke all but one...
  $uniques = analyzeList($floodData['hash2posts'][$hash]);
  if ($uniques['ips'] <= 1 && $uniques['msgs'] <= 1) {
    $html .= '<a href="' . $baseUrl . '?action=nuke&hash=' . $hash . '">nuke all but one</a><br>';
    $html .= '<a href="' . $baseUrl . '?action=nukeall&hash=' . $hash . '">nuke all</a><br>';
    $html .= '<a href="' . $baseUrl . '?action=view&hash=' . $hash . '">view</a><br>';
  } else {
    $html .= 'Unique IPs: ' . $uniques['ips'] . "<br>\n";
    if ($uniques['ips'] < 10) {
      foreach($uniques['iplist'] as $ip) {
        $html .= "ip: $ip<br>\n";
      }
    }
    $html .= 'Unique Msgs: ' . $uniques['msgs'] . "<br>\n";
    if ($uniques['msgs'] < 10) {
      foreach($uniques['msglist'] as $msg) {
        $html .= "msg: $msg<br>\n";
      }
    }
    $html .= '<a href="' . $baseUrl . '?action=analysis&hash=' . $hash . '">view network</a><br>';
    $html .= '<a href="' . $baseUrl . '?action=ban&hash=' . $hash . '">ban all ip</a><br>';
  }
  //$html .= showPostsDetails($floodData['hash2posts'][$hash]);

  return $html;
}

// then dumber tools
$html .= '<h2>Popular files</h2>';
$html .= '<ul>';
$samples = $floodData['samples'];
unset($floodData['samples']);
foreach($floodData['hashes'] as $hash => $cnt) {
  // getThumbnail expects a complete file row (probably processed)
  $html .= '<li style="float: left">
    <details>
      <summary>
    <img height=100 src="' . BACKEND_PUBLIC_URL . $samples[$hash] . '"><br>
    ' . $cnt . ' posts
      </summary>
      ';
    $html .= showHashDetails($hash);
    $html .= '
    </details>
    ' ."\n";
}
unset($floodData['hashes']);
$html .= '</ul><br clear="both">';

$html .= '<h2>Popular Ips</h2>';
$html .= '<ul>';
arsort($floodData['ips']);
foreach($floodData['ips'] as $ip => $cnt) {
  $html .= '<li>
    <details>
      <summary>' . $ip . ': ' . $cnt . '</summary>
  ';

  $html .= '<ul>';
  foreach($floodData['ip2posts'][$ip] as $pno) {
    $html .= showPostDetails($pno);
  }
  $html .= '</ul>';

  $html .= '
    </details>

  ' . "\n";
}
unset($floodData['ips']);
unset($floodData['ip2posts']);
$html .= '</ul><br clear="both">';

$html .= '<h2>Messages</h2>' . "\n";
$html .= '<ul>';
arsort($floodData['messages']);
foreach($floodData['messages'] as $msg => $cnt) {
  $html .= '<li>
    <details>
      <summary>' . $msg . ': ' . $cnt . '</summary>
  ';
  // is all the media unique?
  $posts = $floodData['com2posts'][$msg];
  $html .= '<ul>';
  foreach($posts as $pno) {
    $html .= showPostDetails($pno);
  }
  $html .= '</ul>';

  $html .= '
    </details>

  ' . "\n";
}
$html .= '</ul>';
unset($floodData['com2posts']);
unset($floodData['messages']);

// ranges

$html .= '<h2>Ranges</h2>' . "\n";
$html .= '<ul>';
foreach($floodData['ranges'] as $r) {
  //print_r($r);
  $html .= '<li>
    <details>
      <summary>' . $r['start'] . '-' . $r['end'] . ': ' . $cnt . '</summary>
  ';
  $html .= '<ul>';
  for($pno = $r['start']; $pno <= $r['end']; $pno++) {
    $html .= showPostDetails($pno);
  }
  $html .= '</ul>';
  $html .= '<h3>Posts that also use this media</h3>';
  $hash = empty($r['sha']) ? '' : $r['sha'];
  if ($hash) {
    // this will include posts not in this range...
    //$html .= showHashDetails($hash);
  }
  $html .= '
    </details>

  ' . "\n";
}
$html .= '</ul>';
unset($floodData['ranges']);

// distributed attacks, likely the same message or media

// usedOnceIps

// these are interested but don't we have this list in ips?

unset($floodData['hash2posts']);
unset($floodData['postScores']);

//$html .= '<pre>' . htmlspecialchars(print_r($floodData, 1)) . "</pre>\n";

wrapContent($html);
?>
