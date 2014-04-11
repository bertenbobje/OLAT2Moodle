<?php

class Test {

  public $id;
  public $olat_testid;
  public $title;
  public $description;
  public $duration;
  public $passing_score;
  public $published;
  public $answers_in;
  public $answers_out;
  public $show_answer;
  public $check_answer;
  public $date;
  public $course;
  public $topic;
  public $level;
  public $objective;
  public $sections = array();

  function __construct() {

  }

  function myConstruct($id, $olat_testid, $title, $description, $duration, $passing_score, $published, $answers_in, $answers_out, $show_answer, $check_answer, $date, $course, $topic, $level, $objective, $sections) {
    $this->id = $id;
    //Only one bundle for the moment
    $this->bundle = 'simple_test';
    $this->olat_testid = $olat_testid;
    $this->title = $title;
    $this->description = $description;
    $this->duration = $duration;
    $this->passing_score = $passing_score;
    $this->published = $published;
    $this->answers_in = $answers_in;
    $this->answers_out = $answers_out;
    $this->show_answer = $show_answer;
    $this->check_answer = $check_answer;
    $this->date = $date;
    $this->course = $course;
    $this->topic = $topic;
    $this->level = $level;
    $this->objective = $objective;
    $this->sections = $sections;
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function getTitle() {
    return $this->title;
  }

  public function setDuration($duration) {
    $this->duration = $duration;
  }

  public function getDuration() {
    return $this->duration;
  }

  public function setObjective($objective) {
    $this->objective = $objective;
  }

  public function getObjective() {
    return $this->objective;
  }

  public function setSection($sections) {
    array_push($this->sections, $sections);
  }

  public function getSections() {
    return $this->sections;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getOlat_testid() {
    return $this->olat_testid;
  }

  public function setOlat_testid($olat_testid) {
    $this->olat_testid = $olat_testid;
  }

  public function getDescription() {
    return $this->description;
  }

  public function setDescription($string) {
    $description = array(
      'value' => $string,
      'format' => 'full_html',
    );

    $this->description = serialize($description);
  }

  public function getPassing_score() {
    return $this->passing_score;
  }

  public function setPassing_score($passing_score) {
    $this->passing_score = $passing_score;
  }

  public function getPublished() {
    return $this->published;
  }

  public function setPublished($published) {
    $this->published = $published;
  }

  public function getAnswers_in() {
    return $this->answers_in;
  }

  public function setAnswers_in($answers_in) {
    $this->answers_in = $answers_in;
  }

  public function getAnswers_out() {
    return $this->answers_out;
  }

  public function setAnswers_out($answers_out) {
    $this->answers_out = $answers_out;
  }

  public function getShow_answer() {
    return $this->show_answer;
  }

  public function setShow_answer($show_answer) {
    $this->show_answer = $show_answer;
  }

  public function getCheck_answer() {
    return $this->check_answer;
  }

  public function setCheck_answer($check_answer) {
    $this->check_answer = $check_answer;
  }

  public function getDate() {
    return $this->date;
  }

  public function setDate($date) {
    $this->date = $date;
  }

  public function getCourse() {
    return $this->course;
  }

  public function setCourse($course) {
    $this->course = $course;
  }

  public function getTopic() {
    return $this->topic;
  }

  public function setTopic($topic) {
    $this->topic = $topic;
  }

  public function getLevel() {
    return $this->level;
  }

  public function setLevel($level) {
    $this->level = $level;
  }

  public function setBundle($bundle) {
    $this->bundle = $bundle;
  }

  /**
   * Functions and queries of this class
   */
  function getMaximumScoreTest() {
    $query = db_select('qtici_possibility', 'p');
    $query->join('qtici_item', 'i', 'p.itemid = i.id');
    $query->join('qtici_section', 's', 'i.sectionid = s.id');
    $query->join('qtici_test', 't', 's.testid = t.id');
    $query->condition('t.id', $this->id, '=');
    $query->condition('p.score', 0, '>');
    $query->addExpression('SUM(p.score)');
    $posscore = $query->execute()->fetchAssoc();
    $posscore = (int) $posscore['expression'];

    $query = db_select('qtici_item', 'i');
    $query->join('qtici_section', 's', 'i.sectionid = s.id');
    $query->join('qtici_test', 't', 's.testid = t.id');
    $query->condition('t.id', $this->id, '=');
    $query->condition('i.score', 0, '>');
    $query->addExpression('SUM(i.score)');
    $itemscore = $query->execute()->fetchAssoc();
    $itemscore = (int) $itemscore['expression'];

    return $posscore + $itemscore;
  }

  function getTestIDByOLATTestID() {
    $query = db_select('qtici_test', 't');
    $query->fields('t', array('id'));
    $query->condition('t.olat_testid', $this->olat_testid, '=');
    $resultset = $query->execute()->fetchAssoc();

    return $resultset;
  }

  public function getAllSectionsByTest() {

    $query = db_select('qtici_section', 's');
    $query->fields('s');
    $query->condition('s.testid', $this->id, '=');
    $sections = $query->execute()->fetchAll(PDO::FETCH_CLASS, 'Section');

    return $sections;
  }
  
  public function getAllSectionIDsByTest() {
    
    $entitys = $this->getAllSectionsByTest();
    $ids = array();

    foreach ($entitys as $entity) {
      $ids[] = $entity->id;
    }
    return $ids;
  }

  function getPublishedStateOfTest() {

    $test = db_select('qtici_test', 't')
        ->fields('t', array('published'))
        ->condition('id', $this->id, '=')
        ->execute()
        ->fetchField();

    return $test;
  }
  
  function getAllPossibilityIDsFromAllItemsFromAllSectionsInTest() {

    $query = db_select('qtici_possibility', 'p');
    $query->fields('p', array('id', 'itemid'));
    $query->join('qtici_item', 'i', 'i.id = p.itemid');
    $query->join('qtici_section', 's', 's.id = i.sectionid');
    $query->condition('s.testid', $this->id, '=');
    $possibilities = $query->execute();

    return $possibilities;
  }

  function allPossibilityIDSFromAllItemsFromAllSectionsInTest($testid) {

    $entitys = $this->getAllPossibilityIDsFromAllItemsFromAllSectionsInTest();
    $ids = array();

    foreach ($entitys as $entity) {
      $ids[] = $entity->id;
    }

    return $ids;
  }
  
  function deleteStatistic() {
    db_delete('qtici_test_statistics')
      ->condition('testid', $this->id)
      ->execute();
  }

  public function getAllItemsFromAllSectionsInTest() {

    $query = db_select('qtici_item', 'i');
    $query->fields('i', array('id', 'type', 'quotation', 'question', 'score'));
    $query->join('qtici_section', 's', 's.id = i.sectionid');
    $query->join('qtici_test', 't', 't.id = s.testid');
    $query->condition('t.id', $this->id, '=');
    $entitys = $query->execute();

    return $entitys;
  }

  public function getAllItemIDsFromAllSectionsInTest() {

    $entities = $this->getAllItemsFromAllSectionsInTest();
    $ids = array();
    foreach ($entities as $entity) {
      $ids[] = $entity->id;
    }

    return $ids;
  }
  
  function deleteTestByTestIDOROlatID($testid = '', $olatTestid = '') {

    if (!empty($olatTestid)) {
      $test = $this->getTestIDByOLATTestID();
      $testid = $test['id'];
    }

    $possibilities = $this->getAllPossibilityIDsFromAllItemsFromAllSectionsInTest();

    foreach ($possibilities as $possibility) {
      db_delete('qtici_feedback')
          ->condition('possibilityid', $possibility->id)
          ->execute();

      db_delete('qtici_possibility')
          ->condition('id', $possibility->id)
          ->execute();
    }

    $items = $this->getAllItemsFromAllSectionsInTest();

    foreach ($items as $item) {
      db_delete('qtici_item')
          ->condition('id', $item->id)
          ->execute();

      db_delete('qtici_feedback')
          ->condition('itemid', $item->id)
          ->execute();
    }

    $sections = $this->getAllSectionsByTest();

    foreach ($sections as $section) {
      db_delete('qtici_section')
          ->condition('id', $section->id)
          ->execute();
    }


    //delete files in /upload/files
    $test = qtici_test_entity_load($testid);
    $olat_testid = $test['olat_testid'];
    $title = $test['title'];
    // /var/www/drupal14/index.php
    $files = glob(str_replace('index.php', '', $_SERVER['SCRIPT_FILENAME']) . 'sites/default/files/qtici/' . '*', GLOB_MARK);
    foreach ($files as $file) {
      $dirname = str_replace(str_replace('index.php', '', $_SERVER['SCRIPT_FILENAME']) . 'sites/default/files/qtici/', '', $file);
      $dirname = str_replace('/', '', $dirname);
      if ($dirname == $olat_testid || $dirname == $title) {
        rrmdir($file);
      }
    }

    db_delete('qtici_test')
        ->condition('id', $testid)
        ->execute();
  }

  /**
   * Implementation of test checking
   */
  public function checkTestAnswers($form, $form_state, $itemids) {
    $items = qtici_item_entity_load_multiple($itemids);

    $aantaljuist = 0;
    $maximum = 0;

    foreach ($items as $id) {
      $item = qtici_itemType_entity_load($id->id);
      //unset the attempts session
      if (isset($_SESSION['exercise']['attempts']['item_' . $item->id])) {
        unset($_SESSION['exercise']['attempts']['item_' . $item->id]);
      }

      $maximum += $item->score;
      $returnArray = $item->checkAnswerForTest($form, $form_state);

      $aantaljuist += $returnArray["score"];
    }

    $return["score"] = $aantaljuist;
    $return["max"] = $maximum;

    return $return;
  }
  
  /**
   * Saves the categories of a test
   */
  public function saveCategories($categories) {
    
    foreach ($categories as $cat) {
      // Check if tag already exists
      $terms = taxonomy_get_term_by_name($cat);
      if (!empty($terms)) {
        // Save already existing tag in taxonomy_entity_index
        $query = db_insert('taxonomy_entity_index');
        $query->fields(array('entity_type', 'bundle', 'entity_id', 'revision_id', 'field_name', 'delta', 'tid'));

        foreach ($terms as $term) {
          $query->values(array(
            'entity_type' => 'qitic_test',
            'bundle' => 'qtici_test',
            'entity_id' => $this->id,
            'revision_id' => $this->id,
            'field_name' => $term['field_name'],
            'delta' => $term['delta'],
            'tid' => $term['tid'],
          ));
        }

        $query->execute();
      }
      else {
        global $vid;
        global $field_name;
        // Save new tag
        $term = new stdClass();
        $term->name = $cat;
        $term->vid = $vid;
        $term->field_name = $field_name;
        taxonomy_term_save($term);
        // Save the term in taxonomy_entity_index
        $query = db_insert('taxonomy_entity_index');
        $query->fields(array('entity_type', 'bundle', 'entity_id', 'revision_id', 'field_name', 'delta', 'tid'));
        $query->values(array(
          'entity_type' => 'qitic_test',
          'bundle' => 'qtici_test',
          'entity_id' => $this->id,
          'revision_id' => $this->id,
          'field_name' => $term['field_name'],
          'delta' => $term['delta'],
          'tid' => $term['tid'],
        ));
        $query->execute();
      }
    }
  }
}

?>
