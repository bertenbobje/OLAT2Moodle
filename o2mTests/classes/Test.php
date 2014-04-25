<?php

class Test extends Entity {

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

  function __construct($values = array()) {
    parent::__construct($values, 'test');
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
 
  public function getAllSectionIDsByTest() {
    
    $entitys = $this->getAllSectionsByTest();
    $ids = array();

    foreach ($entitys as $entity) {
      $ids[] = $entity->id;
    }
    return $ids;
  }

  function allPossibilityIDSFromAllItemsFromAllSectionsInTest($testid) {

    $entitys = $this->getAllPossibilityIDsFromAllItemsFromAllSectionsInTest();
    $ids = array();

    foreach ($entitys as $entity) {
      $ids[] = $entity->id;
    }

    return $ids;
  }

  public function getAllItemIDsFromAllSectionsInTest() {

    $entities = $this->getAllItemsFromAllSectionsInTest();
    $ids = array();
    foreach ($entities as $entity) {
      $ids[] = $entity->id;
    }

    return $ids;
  }
}

?>
