<?php

function jsChanStyle() {
  return array(
    'useSections' => true,
    'wrapClass'   => 'row',
    'labelClass'  => 'label',
    'formClass'   => 'form-post',
    //'labelwrap' => 'div',
    //'labelwrapclass' => 'label',
  );
}

// FIXME: maybe a width constraint?
function simpleForm($action, $formFields, $button) {
  // FIXME: pipeline
  $formOptions = array_merge(jsChanStyle(), array(
    'buttonLabel' => $button,
  ));
  // FIXME: pipeline
  $values = array();
  foreach($formFields as $f => $row) {
    $values[$f] = getOptionalPostField($f);
  }
  return generateForm($action, $formFields, $values, $formOptions);
}

function generateForm($action, $fields, $values, $options = false) {
  //echo "[", print_r($options, 1), "]<br>\n";
  global $pipelines;
  $labelwrap1 = '';
  $labelwrap2 = '';
  $listTag  = 'dl';
  $wrapTag  = false;
  $labelTag = 'dt';
  $fieldTag = 'dd';
  extract(ensureOptions(array(
    'useSections' => false,
    'labelClass'  => '',
    'labelwrapclass' => false,
    'wrapClass'    => '',
    'labelwrap'    => false,
    'formClass'    => '',
    'formId'       => '',
    'postFormTag'  => '',
    'buttonLabel'  => 'Update',
    // do we need more than 2, should there be an array?
    // display options?
    'secondAction' => '',
    'button2Label' => '',
    'firstAction'  => false,
    'actionName'   => '',
  ), $options));

  if ($useSections) {
    $listTag = false;
    $wrapTag = 'section';
    $labelTag = 'div';
    $fieldTag = false;
  }
  if ($labelClass) {
    $labelClass = ' class="' . $labelClass . '"';
  }
  if ($wrapClass) {
    $wrapClass = ' class="' . $wrapClass . '"';
  }
  if ($labelwrap) {
    $labelwrap1 = '<' . $labelwrap;
    if ($labelwrapclass) {
      $labelwrap1 .= ' class="' . $labelwrapclass . '"';
    }
    $labelwrap1 .= '>';
    $labelwrap2 = '</' . $labelwrap . '>';
  }
  if ($formClass) {
    $formClass = ' class="' . $formClass . '"';
  }
  if ($formId) {
    $formId = ' id="' . $formId . '"';
  }
  if ($postFormTag) {
    $postFormTag = $postFormTag . "\n";
  }
  // FIXME: detect multidropfile/image
  $html = '<form' . $formClass . $formId . ' action="' . $action . '" method="post" enctype="multipart/form-data">' . "\n" . $postFormTag;


  // fields should always be an array
  if ($listTag) {
    $html.= '<' . $listTag . '>';
  }
  foreach($fields as $field => $details) {
    if (!isset($details['label']) && $details['type'] !== 'hidden') {
      echo "Skipping [$field], no label<br>\n";
      continue;
    }

    $sublabel = '';
    if (isset($details['sublabel'])) {
      $sublabel = '<small>' . $details['sublabel'] . '</small>';
    }
    $postlabel = '';
    if (isset($details['postlabel'])) {
      $postlabel = '<small>' . $details['postlabel'] . '</small>';
    }
    $wrapId = '';
    if (isset($details['wrapId'])) {
      $wrapId .= ' id="' . $details['wrapId'] . '"';
    }
    $twrapClass = $wrapClass;
    if (isset($details['wrapClass'])) {
      $twrapClass = ' class="' . $details['wrapClass'] . '"';
    }
    $tlabelClass = $labelClass;
    if (isset($details['labelClass'])) {
      $tlabelClass = ' class="' . $details['labelClass'] . '"';
    }
    if ($wrapTag) {
      $html .= '<' . $wrapTag . $twrapClass . $wrapId . '>';
    }
    $tlabelwrap1 = $labelwrap1;
    $tlabelwrap2 = $labelwrap2;
    /*
    if (isset($details['filesLabel'])) {
      $tlabelwrap1 = '<div>';
      $tlabelwrap2 = '</div>';
    }
    */
    if (isset($details['label'])) {
      $html .= '<' . $labelTag . $tlabelClass . '><label for="' . $field . '">' .
        $tlabelwrap1 . $details['label'] . ': &nbsp;' . $tlabelwrap2 .
        $sublabel . '</label>' . $postlabel . '</' . $labelTag . '>' . "\n";
    }
    // can be stomped if needed
    // do we need to escape?
    $value = empty($values[$field]) ? '' : $values[$field];
    if ($fieldTag) {
      $html .= '<' . $fieldTag . '>';
    }
    $ac = '';
    if (isset($details['autocomplete'])) {
      $ac = ' autocomplete="chrome-off"';
    }
    $ph = '';
    if (isset($details['placeholder'])) {
      $ph = ' placeholder="' . $details['placeholder'] . '"';
    }
    switch($details['type']) {
      case 'hidden':
        // using single quotes so you can pass JSON encoded data easily
        $html .= '<input type=hidden name="'.$field.'" value=\'' . $value . '\'>';
      break;
      /*
      case 'title':
        $html .= '<a class="close postform-style" href="' . $_SERVER['REQUEST_URI'] . '#!">X</a>';
      break;
      */
      case 'text':
        $html .= '<input type=text name="'.$field.'" value="' . $value . '"'.$ac.$ph.'>';
      break;
      case 'email':
        $html .= '<input type=email name="'.$field.'" value="' . $value . '"'.$ac.$ph.'>';
      break;
      case 'textpass':
        // always blank and can't be cleared
        $html .= '<input type=text name="'.$field.'">';
      break;
      case 'password':
        // always blank and can't be cleared
        $html .= '<input type=password name="'.$field.'">';
      break;
      case 'integer':
      case 'number':
        $html .= '<input type=number name="'.$field.'" value="' . $value . '">';
      break;
      case 'textarea':
        // minlength isn't official but jschan uses it in counter.js
        $mnl = isset($details['minLength']) ? ' minLength="' . $details['minLength'] . '"' : '';
        $mxl = isset($details['maxLength']) ? ' maxLength="' . $details['maxLength'] . '"' : '';
        // do we need to escape?
        $html .= '<textarea name="'.$field.'"'.$ac.$mnl.$mxl.'>'.$value.'</textarea>';
      break;
      case 'select':
        $html .= '<select name="' . $field . '">';
        foreach($details['options'] as $v => $l) {
          $sel  = $v === $value ? ' selected' : '';
          $html .= '<option value="' . $v . '"' . $sel . '>' . $l;
        }
        $html .= '</select>';
      break;
      case 'checkbox':
        $checked = $value ? ' CHECKED' : '';
        $html .= '<input id="'.$field.'" type=checkbox name="'.$field.'" value="1"'.$checked.'>';
      break;
      case 'captcha':
        // great for keeping the size of this file down
        $io = array(
          'field'   => $field,
          'details' => $details,
        );
        // generate/store/send captcha challange, image, and possibly an ID
        $pipelines[PIPELINE_FORM_CAPTCHA]->execute($io);
        if (isset($io['html'])) {
          $html .= $io['html'];
        }
      break;
      case 'themethumbnails':
        // great for keeping the size of this file down
        $io = array(
          'field'   => $field,
          'details' => $details,
          'value'   => $value,
        );
        // generate/store/send captcha challange, image, and possibly an ID
        $pipelines[PIPELINE_FORM_WIDGET_THEMETHUMBNAILS]->execute($io);
        if (isset($io['html'])) {
          $html .= $io['html'];
        }
      break;
      case 'image':
        if ($value) {
          // FIXME: BACKEND_BASE_URL
          // if not set it will clear it, so we need a clear checkbox...
          $html .= '<img height=100 src="backend/' . $value . '"><br>';
        }
        $html .= '<label><input type=checkbox name="'.$field.'_clear"> Reset back to default</label><br>';
        $html .= '<input type=file name="'.$field.'">';
      break;
      case 'multidropfile':
        if ($value && $value !== '[]') {
          //echo "<pre>", print_r($value, 1), "</pre>\n";
          $files = json_decode($value, true);
          $html .= '<ul>';
          foreach($files as $file) {
            $html .= '<li>' . $file['name'] . ' ' . formatBytes($file['size']) . "\n";
          }
          $html .= '</ul>';
          $html .= '<input type=hidden name="'. $field. '_already_uploaded" value=\'' . $value . '\'>';
        }
        $html .= '<span class="col">
                    <input id="file" type="file" name="' . $field . '[]" multiple>
                    <label class="jsonly postform-style filelabel" for="file">
                      Select/Drop/Paste files
                    </label>
                    <div class="upload-list" data-spoilers="true"></div>
                  </span>
                  <noscript>
                    <label class="postform-style ph-5 ml-1 fh">
                      <input type="checkbox" name="spoiler_all" value="true">
                      Spoiler
                    </label>
                  </noscript>';

      break;
      default:
        //echo "No such type [", $details['type'], "]<br>\n";
        // FIXME: registry
        $html .= 'Error unknown type '.$details['type'].', skipping ';
      break;
    }
    if ($fieldTag) {
      $html .= '</' . $fieldTag . '>' . "\n";
    }
    if ($wrapTag) {
      $html .= '</' . $wrapTag . '>' . "\n";
    }
  }
  if ($listTag) {
    $html.= '</' . $listTag . '>';
  }
  if ($actionName) {
    $actionName = ' name="' . $actionName . '"';
  }
  $secondButton = $secondAction ? '<button type=submit' . $actionName . ' value="' . $secondAction . '">' . $button2Label . '</button>' : '';
  $firstButton = $firstAction ? '<button type=submit' . $actionName . ' value="' . $firstAction . '">' . $buttonLabel . '</button>' : '<input type=submit value="' . $buttonLabel . '">';
  $html .= '
    ' . $firstButton . '
    ' . $secondButton . '
  </form>';
  return $html;
}

?>
