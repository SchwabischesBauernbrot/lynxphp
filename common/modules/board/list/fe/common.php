<?php

/*
function secondsToTime($inputSeconds) {
  global $now;

  $obj = new DateTime();
  $obj->setTimeStamp($now - $inputSeconds);

  $diff = $then->diff(new DateTime(gmdate('Y-m-d H:i:s', $now)));
  return array('years' => $diff->y, 'months' => $diff->m, 'days' => $diff->d, 'hours' => $diff->h, 'minutes' => $diff->i, 'seconds' => $diff->s);
}
*/

function relativeColor($relativeTo) {
  global $now;
  $SECOND = 1;
  $MINUTE = 60; // 60s in 1min
  $HOUR   = 3600;
  $DAY    = 86400;
  $WEEK   = 604800;
  $MONTH  = 2629800;
  $YEAR   = 31536000;

  $diff = $now - $relativeTo;
  $minAgo = floor($diff / $MINUTE);

  $r = 0; $g = 0; $b = 0;
  if ($diff < $MINUTE) {
    $g = 0.7; $b = 1;
  } else
  if ($diff < $HOUR) {
    $r = ($minAgo / 60) * 0.5;
    $g = 1;
  } else
  if ($diff < $DAY) {
    $r = 0.5 + ($minAgo / 1440) * 0.5;
    $g = 1;
  } else
  if ($diff < $WEEK) {
    $g = 1 - ($minAgo /10080) * 0.5;
    $r = 1;
  } else
  if ($diff < $MONTH) {
    $g = 0.5 - ($minAgo / 43830) * 0.5;
    $r = 1;
  } else
  if ($diff < $YEAR) {
    $r = 1 - ($minAgo / 525960);
  }
  // else leave it black

  return sprintf('%02x%02x%02x', $r * 255, $g * 255, $b * 255);
}

function getBoardsParams() {
  $pageNum = 1;
  $params = array(
    'search' => '',
    'sort' => 'activity',
    'direction' => 'desc',
  );
  // popularity desc is the default
  // popularity desc should be highest post at the top
  // prettier if Latest activity is the default
  if (!empty($_REQUEST['search'])) {
    $params['search'] = $_REQUEST['search'];
  }
  if (!empty($_REQUEST['sort'])) {
    $params['sort'] = $_REQUEST['sort'];
  }
  $reverse_list = true;
  if (!empty($_REQUEST['direction'])) {
    //$params['direction'] = $_GET['direction'];
    $reverse_list = $_REQUEST['direction'] !== 'asc';
  }
  if (!empty($_GET['page'])) {
    $pageNum = (int)$_GET['page'];
  }
  $params['page'] = $pageNum;
  $params['direction'] = $reverse_list ? 'desc' : 'asc';

  return $params;
}

function renderBoardsTemplate($res, $templates, $params) {
  global $now, $pipelines;

  $pageNum = $params['page'];
  $reverse_list = $params['direction'] === 'desc';

  $boards = $res['data']['boards'];
  // FIXME: not very cacheable like this...
  $settings = $res['data']['settings'];
  /*
  if (BACKEND_TYPE === 'default') {
    if ($reverse_list) {
    }
  }
  */
  $boards = array_reverse($boards);

  // notice common hack at the end
  // this function was not made for common location...
  $overboard_template = $templates['loop0'];
  $board_template     = $templates['loop1'];
  $page_tmpl     = $templates['loop2'];
  //echo "<pre>pages_template", htmlspecialchars(print_r($page_tmpl, 1)), "</pre>\n";

  $boards_html = '';
  foreach($boards as $c=>$b) {
    $last = '';
    $color = ''; // green
    if (!empty($b['last'])) {
      $time = $now - $b['last']['updated_at'];

      $months = floor($time / (60 * 60 * 24 * 30));
      $time -= $months * (60 * 60 * 24 * 30);

      $weeks = floor($time / (60 * 60 * 24 * 7));
      $time -= $weeks * (60 * 60 * 24 * 7);

      $days = floor($time / (60 * 60 * 24));
      $time -= $days * (60 * 60 * 24);

      $hours = floor($time / (60 * 60));
      $time -= $hours * (60 * 60);

      $minutes = floor($time / 60);
      $time -= $minutes * 60;

      $seconds = floor($time);
      $time -= $seconds;

      $last = '';
      if ($seconds) {
        $s = 's';
        if ($seconds === 1) $s = '';
        $last = $seconds . ' second' . $s . ' ago';
      }
      if ($minutes) {
        $s = 's';
        if ($minutes === 1) $s = '';
        $last = $minutes . ' minute' . $s . ' ago';
      }
      if ($hours) {
        $s = 's';
        if ($hours === 1) $s = '';
        $last = $hours   . ' hour' . $s . ' ago';
      }
      if ($days) {
        $s = 's';
        if ($days === 1) $s = '';
        $last = $days    . ' day' . $s . ' ago';
      }
      if ($weeks) {
        $s = 's';
        if ($weeks === 1) $s = '';
        $last = $weeks   . ' week' . $s . ' ago';
      }
      if ($months) {
        $s = 's';
        if ($months === 1) $s = '';
        $last = $months  . ' month' . $s . ' ago';
      }

      $color = relativeColor($b['last']['updated_at']);
    }
    $boardUri = $b['uri'];

    $board_actions = action_getLevels();
    $board_actions['all'][] = array('link' => '/' . $boardUri . '/', 'label' => 'View');

    $action_io = array(
      'boardUri' => $boardUri,
      'b' => $b,
      'actions'  => $board_actions,
    );
    $pipelines[PIPELINE_BOARD_ACTIONS]->execute($action_io);
    // remap output over the top of the input
    $board_actions = $action_io['actions'];
    $board_actions_html = action_getHtml($board_actions, array(
      'boardUri' => $boardUri, 'where' => 'boards'
    ));
    $tags = array(
      'uri' => $boardUri,
      'title' => htmlspecialchars($b['title']),
      'description' => htmlspecialchars($b['description']),
      'threads' => $b['threads'],
      'posts' => $b['posts'],
      'lastActivityColor' => $color,
      'last_post' => $last,
      'actions' => $board_actions_html,
    );
    $boards_html .= replace_tags($board_template, $tags) . "\n";
  }

  $page_html = '';
  // FIXME: we should tightly control the page links
  // so we can generate a static page for each page...
  // boards/1.html

  $qParams = array();
  if ($params['search']) $qParams['search'] = $params['search'];
  if ($params['sort'] !== 'activity') $qParams['sort'] = $params['sort'];
  if ($params['direction'] !== 'desc') $qParams['direction'] = 'asc';
  $qs = paramsToQuerystringGroups($qParams);

  if (isset($res['data']['pageCount'])) {
    //print_r($params);
    for($i = 0; $i < $res['data']['pageCount']; $i++) {
      $tags = array(
        'page' => $i + 1,
        'qs' => 'page=' . ($i + 1) . '&' . join('&', $qs),
        'bold' => $pageNum == $i + 1 ? 'bold' : '',
      );
      $page_html .= replace_tags($page_tmpl, $tags) . "\n";
    }
  } else {
    $tags = array(
      'page' => 1,
      'qs' => 'page=1&' . join('&', $qs),
      'bold' => 'bold',
    );
    $page_html .= replace_tags($page_tmpl, $tags) . "\n";
  }

  global $BASE_HREF;
  $tags = array(
    'overboard' => '',
    'fields' => '',
    'search' => $params['search'],
    'popularitySelected' => $params['sort'] === 'popularity' ? ' selected' : '',
    'latestSelected' => $params['sort'] === 'activity' ? ' selected' : '',
    'descSelected' => $reverse_list ? ' selected' : '',
    'ascSelected' => $reverse_list ? '' : ' selected',
    'pages' => $page_html,
    'boards' => $boards_html,
    // FIXME get named route
    'action' => $BASE_HREF . 'boards.php',
  );
  $content = replace_tags($templates['header'], $tags);

  return array(
    'content' => $content,
    'settings' => $settings,
  );
}

function getBoardsHandlerEngine($res) {
  $templates = moduleLoadTemplates('board_listing', __DIR__ . '/common');
  return renderBoardsTemplate($res, $templates);
}

// FIXME: solve custom sheetstyle issues in these...

return array();
?>
