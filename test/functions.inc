<?php

/**
 * @file - Diverse functions
 */

function strpos_r($haystack, $needle) {
  if (strlen($needle) > strlen($haystack))
    trigger_error(sprintf("%s: length of argument 2 must be <= argument 1", __FUNCTION__), E_USER_WARNING);

  $seeks = array();
  while ($seek = strrpos($haystack, $needle)) {
    array_push($seeks, $seek);
    $haystack = substr($haystack, 0, $seek);
  }
  return $seeks;
}

function checkIfExistAndCast($input, $path = '', $folderID = '') {

  if (is_bool($input)) {
    if ($input == FALSE) {
      $input = 0;
    }
  }

  if ($input == '' || $input == NULL) {
    $input = NULL;
  }
  else {
    if (is_string($input)) {
      $input = _formatHTML($input);
      $path = str_replace('qti.xml', '', $path);
      _qtici_replaceVideo($input, $path);
    }
  }

  return $input;
}

function getSecondsFromDuration($input) {
  $hours = substr($input, strpos($input, 'T') + 1, ((strpos($input, 'H') - 1 ) - strpos($input, 'T')));
  $minutes = substr($input, strpos($input, 'H') + 1, ((strrpos($input, 'M') - 1 ) - strpos($input, 'H')));
  $seconds = substr($input, strrpos($input, 'M') + 1, ((strpos($input, 'S') - 1 ) - strrpos($input, 'M')));

  $duration = 0;
  $duration += $seconds;
  $duration += ($minutes * 60);
  $duration += ($hours * 3600);

  return $duration;
}

function rrmdir($dir) {
  foreach (glob($dir . '/{,.}*', GLOB_BRACE) as $file) {
    if (substr($file, -2) === '/.' || substr($file, -2) === '..') {

    }
    else {
      if (is_dir($file)) {
        rrmdir($file);
      }
      else {
        unlink($file);
      }
    }
  }
  drupal_rmdir($dir);
}

/**
 * Replace old html tags for xhtml new ones
 */
function _formatHTML($string) {

  $replace = array(
    '<br>',
    '&',
    '<p></p>',
    '&amp;nbsp;',
  );

  $with = array(
    '<br />',
    '&amp;',
    '',
    '',
  );

  $newString = str_replace($replace, $with, $string);

  return $newString;
}

/**
 * Returns an array with tests and their ids
 * Args:
 *  - Level
 *  - Topic
 */
function _qtici_get_exercisesLT($level = '', $topic = '', $tag = 0) {

  $exercises = array();

  $query = db_select('qtici_test', 't')
      ->fields('t', array('id', 'level', 'topic', 'title'));

  if ($level !== '') {
    $query->condition('level', $level, '=');
  }

  if ($topic !== '') {
    $query->condition('topic', $topic, '=');
  }

  if ($tag != 0) {
    $query->join('taxonomy_entity_index', 'i', 'i.entity_id = t.id');
    $query->condition('i.tid', $tag, '=');
  }

  $result = $query->execute();

  foreach ($result as $test) {
    $exercises[] = array(
      'id' => $test->id,
      'title' => $test->title,
      'level' => $test->level
    );
  }

  return $exercises;
}

/**
 * Adds a flowplayer element in the $selector (class or id) element
 */
function _qtici_addMediaElement($selector, $url, $audio = FALSE) {

  static $flowplayer_selectors = array();
  
  static $mediaelement_added = FALSE;
  $config = array();
  if ($mediaelement_added === FALSE) {
    drupal_add_library('qtici', 'mediaelement');
    drupal_add_library('qtici', 'mediaelement-css');

    $mediaelement_added = TRUE;
  }

  if (isset($selector) && !isset($flowplayer_selectors[$selector])) {

    $config['playlist'] = array(
      $url,
    );
    global $base_url;
    $config['swf'] = $base_url . '/' . drupal_get_path('module', 'qtici') . '/js/mediaelement/build/flashmediaelement.swf';

    drupal_add_js(drupal_get_path('module', 'qtici') . '/js/mediaelement.js', array('type' => 'file', 'scope' => 'footer', 'group' => JS_THEME, 'weight' => 100, 'defer' => TRUE));
    drupal_add_js(array('mediaelement' => array($selector => $config)), 'setting');

    $flowplayer_selectors[$selector] = TRUE;
  }
}

/**
 * Checks if a extension belongs to the defined extensions in global
 */
function _is_in_extensions($ext) {
  global $_qtici_extensions;

  return in_array($ext, $_qtici_extensions);
}

/**
 * Returns the fid of the audio, video or image file
 */
function _qtici_getFID($text, $position, $type) {
  $length = strlen($type) + 1;
  $end = strpos($text, 'fid:', $position);

  $fid = (int) substr($text, $position + $length, $end - $position - $length);

  return $fid;
}

/**
 * Fetch all posibilities for an item
 */
function _qtici_loadPossibilitiesByItemID($id) {

  $query = db_select('qtici_possibility', 'p');
  $query->fields('p', array('id'));
  $query->condition('p.itemid', $id);

  $res = $query->execute();

  $ids = array();

  foreach ($res as $id) {
    $ids[] = $id->id;
  }

  return entity_load('qtici_possibility', $ids, array(), FALSE);
}

/**
 * Fetch all item ids of a test
 */
function _qtici_loadItemIDsByTestID($id) {
  $query = db_select('qtici_item', 'i');
  $query->fields('i', array('id'));
  $query->join('qtici_section', 's', 's.id = i.sectionid');
  $query->join('qtici_test', 't', 't.id = s.testid');
  $query->condition('t.id', $id, '=');
  $items = $query->execute()->fetchAssoc();

  return $items;
}

/**
 * Checks for olat videos in an HTML string, saves them to DB and replaces them with tokens
 */
function _qtici_replaceVideo(&$input, $path) {
  // Find videos
  $tag = strpos($input, 'class="olatFlashMovieViewer"');
  // Replace them
  while ($tag !== FALSE) {
    $lenInput = strlen($input);
    // Find starting of the closest span (backwards)
    $fstSpan = strrpos($input, 'span', $tag - $lenInput) - 1;
    // Find closest </span>
    $posLastSpan = strpos($input, '</span>', $tag) + 7;
    // Get video URL
    $dumbVar = substr($input, $fstSpan, $posLastSpan);
    $media = null;
    if (strpos($dumbVar, 'media/')) {
      $dumbVar = substr($dumbVar, strpos($dumbVar, 'media/'));
      $dumbVar = substr($dumbVar, 0, strpos($dumbVar, '"'));
      $audio = strpos(strtolower($dumbVar), '.mp3');
      global $base_url, $_qtici_extensions, $user;
      $uri = $path . $dumbVar;

      // Create file object
      $newFile = new stdClass();
      $newFile->uid = $user->uid;
      $newFile->status = 1;
      $newFile->filename = drupal_basename($uri);
      $newFile->uri = $uri;
      $newFile->filemime = file_get_mimetype($uri);
      $newFile->size = filesize($uri);
      $newFile = file_copy($newFile, 'public://', FILE_EXISTS_REPLACE);
      $newFile = file_save($newFile);

      if ($audio == TRUE) {
        $media = ':audio' . $newFile->fid . 'fid:';
      }
      else {
        $media = ':video' . $newFile->fid . 'fid:';
      }
    }
    $input = substr_replace($input, $media, $fstSpan, $posLastSpan - $fstSpan);
    $tag = strpos($input, 'class="olatFlashMovieViewer"');
  }
  // If there are more "media/" after replacement we asume they are images
  if (strpos($input, '"media/')) {
    $input = str_replace('"media/', '"' . $path . 'media/', $input);
  }
}

/**
 * Returns a date of the DB in a nice format
 * Args:
 * $date (string)
 */
function _qtici_getNiceDate($timestamp) {
  $niceDate = date('j M Y H:i', $timestamp);

  return $niceDate;
}

/**
 * Checks if there si video or audio on a string
 */
function _qtici_checkMedia(&$string, $itemid) {
  $check = array(
    'video',
    'audio',
  );
  $info = array();
  foreach ($check as $value) {
    $media = strpos($string, ':' . $value);
    while ($media !== FALSE) {
      $fid = _qtici_getFID($string, $media, $value);
      $file = file_load($fid);
      $url = file_create_url($file->uri);
      $info[$fid] = array(
        'type' => $value,
        'class' => $value,
      );
      // Video tag is not fetched because of filter_xss_admin() if it goes on #title
      $replacement = '<div class="video' . $fid . '"></div>';
      if ($value == 'audio') {
        $replacement = '<div class="audio' . $fid . '"></div>';
      }
      $string = str_replace(':' . $value . $fid . 'fid:', $replacement, $string);
      $media = strpos($string, ':' . $value, $media + 1);
    }
  }

  return $info;
}

/**
 * Checks if there is a textbox and replaces it with a form
 */
function _qtici_checkTextbox(&$string, $DAD = FALSE) {

  $text = strpos($string, ':text');
  while ($text !== FALSE) {
    $id = _qtici_getPossID($string, $text, 'text');
    $replacement = '<div class="container-inline"><div class="form-item form-type-textfield form-item-textbox-' . $id . '"><input id="edit-textbox-' . $id . '" class="form-text';
    if ($DAD) {
      $replacement .= ' droppable';
    }
    $replacement .= '" type="text" style="maxlength: 128px; size: 12px; margin: 3px" value="" name="textbox_' . $id . '" /></div></div>';
    $string = str_replace(':text' . $id . 'box:', $replacement, $string);
    $text = strpos($string, ':text', $text + 1);
  }
}

/**
 * Returns the fid of the audio, video or image file
 */
function _qtici_getPossID($text, $position, $type) {
  $length = strlen($type) + 1;
  $end = strpos($text, 'box:', $position);

  $fid = (int) substr($text, $position + $length, $end - $position - $length);

  return $fid;
}

/**
 * Replace ident with id in the content of FiB
 */
function _qtici_setTextbox($string, $ident, $new_id) {

  $replacement = ':text' . $new_id . 'box:';
  $string = str_replace(':text' . $ident . 'box:', $replacement, $string);

  return $string;
}

/**
 * Publishes or unpublishes tests
 */
function _qtici_publishTest($ids) {

  $entities = qtici_test_entity_load_multiple($ids);

  foreach ($entities as $test) {
    $status = 0; // Not published
    if ($test->published == 0) {
      $status = 1;
    }
    $test->published = $status;
    qtici_test_entity_save($test);
  }

  drupal_set_message(t('Tests have been (un)published'));
}

/**
 * Makes a label for the check answer callback. This method calculates the score of the student for eacht type of exercise and returns it in a label with text
 */
function qtici_makeLabelFeedback($form_state, $itemid, $returnArray) {

  //get the variables out of the array
  $juistFout = $returnArray["trueFalse"];
  // Check quotation
  if ($returnArray['quotation'] == 'allCorrect') {
    $score = ($juistFout) ? 1 : 0;
  }
  else {
    $score = $returnArray["score"];
  }
  $label = '';  
  
  if ($juistFout) {
    $label = t('Correct, u heeft alles juist');
  }
  else {
    $label = t('Fout, uw score is :score!', array(':score' => number_format($score, 2, ',', ' ')));
  }

  return $label;
}


// Theme for single radio.
function theme_qtici_item_form_radio($variables) {
  $element = $variables['elements'];
  $element['#attributes']['type'] = 'radio';
  element_set_attributes($element, array('id', 'name', '#return_value' => 'value'));

  if (isset($element['#return_value']) && $element['#value'] !== FALSE && $element['#value'] == $element['#return_value']) {
    $element['#attributes']['checked'] = 'checked';
  }
  _form_set_class($element, array('form-radio'));

  return '<input' . drupal_attributes($element['#attributes']) . ' /> ' . $element["#title"] . '<br />';
}

// Theme for radio group.
function theme_qtici_item_form_radios($variables) {

  $element = $variables['elements'];
  $attributes = array();
  if (isset($element['#id'])) {
    $attributes['id'] = $element['#id'];
  }
  $attributes['class'] = 'form-radios';
  if (!empty($element['#attributes']['class'])) {
    $attributes['class'] .= ' ' . implode(' ', $element['#attributes']['class']);
  }
  if (isset($element['#attributes']['title'])) {
    $attributes['title'] = $element['#attributes']['title'];
  }

  $output = ''; 
  //This duplicates the div: '<div' . drupal_attributes($attributes) . '><br />' . (!empty($element['#children']) ? $element['#children'] : '');

  $keys = array_keys($element['#options']);
  foreach ($keys as $key) {
    // Each radios is theme by calling our custom 'my_radio' theme function.
    $output .= theme('qtici_item_form_radio', $element[$key]);
  }

  //$output .= '</div>';

  return $output;
}

// Theme for single checkbox.
function theme_qtici_item_form_checkbox($variables) {
  $element = $variables['elements'];
  $t = get_t();
  $element['#attributes']['type'] = 'checkbox';
  element_set_attributes($element, array('id', 'name', '#return_value' => 'value'));

  // Unchecked checkbox has #value of integer 0.
  if (!empty($element['#checked'])) {
    $element['#attributes']['checked'] = 'checked';
  }
  _form_set_class($element, array('form-checkbox'));

  return '<input' . drupal_attributes($element['#attributes']) . ' /> ' . $element["#title"] . '<br />';
}

// Theme for checkbox group.
function theme_qtici_item_form_checkboxes($variables) {
  $element = $variables['elements'];
  $attributes = array();
  if (isset($element['#id'])) {
    $attributes['id'] = $element['#id'];
  }
  $attributes['class'][] = 'form-checkboxes';
  if (!empty($element['#attributes']['class'])) {
    $attributes['class'] = array_merge($attributes['class'], $element['#attributes']['class']);
  }
  if (isset($element['#attributes']['title'])) {
    $attributes['title'] = $element['#attributes']['title'];
  }

  $output = '';
  //This duplicates the div: $output = '<div' . drupal_attributes($attributes) . '><br />' . (!empty($element['#children']) ? $element['#children'] : '');

  $keys = array_keys($element['#options']);
  foreach ($keys as $key) {
    // Each radios is theme by calling our custom 'my_radio' theme function.
    $output .= theme('qtici_item_form_checkbox', $element[$key]);
  }

  //$output .= '</div>';

  return $output;
}

/**
 * Find answers for marker exercises from the solution string
 */
function _qtici_findAnswers($string) {

  $answers = array();

  $find = explode('<span style="text-decoration: underline;">', $string);
  foreach ($find as $value) {
    $length = strpos($value, '</span>');
    $answers[] = substr($value, 0, $length);
  }

  return $answers;
}

/**
 * Title callback for statistics form
 */
function qtici_statistics_title($test) {
  return t('Statistics for @title', array('@title' => $test->title));
}

/**
 * Get all tests
 */
function _qtici_getAllTests() {
  $query = db_select('qtici_test', 't');
  $query->fields('t');
  $query->orderBy('published', 'DESC');
  $query->orderBy('title');
  $results = $query->execute();
  
  return $results;
}

/**
 * Delete section by IDS
 */
function _qtici_deleteSections($ids = array()) {
  foreach ($ids as $id) {
    $sectionObj = new Section();
    $sectionObj->myConstruct($id);
    $sectionObj->deleteSection();
  }
}

/**
 * Gets the correct possibility for an item
 */
function _qtici_getCorrectPossibilityForItem($itemid) {
  $query = db_select('qtici_possibility', 'p');
  $query->fields('p', array('answer'));
  $query->condition('p.itemid', $itemid, '=');
  $query->condition('p.is_correct', 1, '=');
  $result = $query->execute();

  $return = array();
  foreach ($result as $answer) {
    $answer_u = unserialize($answer->answer);
    $return[] = $answer_u['value'];
  }

  return $return;
}

/**
 * Returns the text description without the categories and an array by ref. with them
 */
function _qtici_get_testDescription($string, &$categories) {
  $pos = strpos('<categories>', $string);
  $description = $string;
  // Check if categories are defined inside description
  if ($pos) {
    $end = strpos('</categories>', $string);
    $tag_list = substr($string, $pos + strlen('<categories>'), $end);
    $tag_array = explode(',', $tag_list);
    
    $description = substr($string, 0, $pos);
    $categories += $tag_array;
  }
  
  return $description;
}

/**
 * converts string to lower case and replaces punctuation with underscores for comparission
 */
function _qtici_strip_punctuation(&$string) {
  $string = strtolower($string);
  $string = preg_replace('/[\W]+/', ' ', $string);
  $string = trim($string);
  return $string;
}
