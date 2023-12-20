<?php

function post_queue_display($qposts) {
  $str = '';
  global $pipelines;

  $io = array(
    'addFields' => array()
  );
  $pipelines[PIPELINE_FE_ADMIN_QUEUE_ROW]->execute($io);
  $addFields = $io['addFields'];

  $str .= '<table width=100%>';
  // could but a check all here...
  $fields = array(
    // 'id',
    '', 'uri', 'created', 'thread', 'type', 'ip', 'post', 'votes',
  );
  $str .= '<tr><th><nobr>' . join('</nobr><th><nobr>', $fields) . '</nobr>';
  foreach($addFields as $f) {
    $str .= '<th>' . $f['label'];
  }
  $str .= '<th>Actions';

  $userSettings = getUserSettings();

  foreach($qposts as $c3 => $s) {
    if ($c3 > 50) break;
    $uri = $s['board_uri'];

    $boardSettings = getter_getBoardSettings($uri);

    $d = json_decode($s['data'], true);
    $str .= '<tr>';
    $str .= '<td><input type=checkbox name="list[]" value="' . $s['queueid'] . '">';
    $str .= '<th><a href="/' . $uri . '/" target=_blank>' . $uri;
    //$str .= '<td>' . $s['queueid'];
    // age would be better...
    $str .= '<td>' . date('Y-m-d H:i:s', $s['created_at']);
    // there's no post number because they don't exist yet
    // <a href="' . $uri . '/thread/' . $s['post']['no'] . '.html" target=_blank>
    $str .= '<td>' . (!$s['thread_id'] ? 'new' : ('<a href="' . $uri . '/thread/' . $s['thread_id'] . '.html" target=_blank>' . $s['thread_id'] . '</a>'));
    $str .= '<td>' . $s['type'];
    $str .= '<td>' . $s['ip'] . '<td>' . renderPost($uri, $s['post'], array('userSettings' => $userSettings, 'boardSettings' => $boardSettings));
    $str .= '<td>' . $s['votes'];
    foreach($addFields as $c => $f) {
      $field = $f['field'];
      $val = $s[$field];
      if (!empty($f['type'])) {
        switch($f['type']) {
          case 'compact_informative':
            $str .= '<td>';
            foreach($val as $c2 => $l) {
              if ($c2 > 10) {
                $str .= '...';
                break;
              }
              $str .= '<a href="' . $l . '">X</a>' . "\n";
            }
          break;
          case 'compact_informative':
            $str .= '<td><span title="' . join(',', $val) . '">X</span>';
          break;
          default:
            echo "Unknown type[", $f['type'], "]<br>\n";
          break;
        }
      } else {
        // default
        if (is_array($val)) {
          $str .= '<td>' . join(',', $val);
        } else {
          $str .= '<td>' . $val;
        }
      }
    }
    // then actions?
    $dataActions = array(
      array('link' => 'admin/queue/' . $s['queueid'] . '/delete.html', 'label' => 'Delete'),
    );
    $htmlLinks = array();
    foreach($dataActions as $a) {
      $htmlLinks[] = action_getLinkHTML($a, array('where' => false));
    }
    $str .= '<td>' . join('<br>' > "\n", $htmlLinks);
    //$str .= '<td><pre>' . print_r($s, 1) . "</pre>\n";
  }
  $str .= '</table>';
  return $str;
}

return array();

?>