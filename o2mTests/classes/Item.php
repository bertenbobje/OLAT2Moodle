<?php

class Item extends Entity {

  //name variables same as database columns
  //  protected $ident;
  public $type;
  public $title;
  public $objective;
  public $feedback = array();
  public $hint;
  public $solutionFeedback;
  public $max_attempts;
  public $possibilities = array();
  public $question;
  public $id;
  public $content;
  public $sectionid;
  public $description;

  public function __construct($values = array()) {
    parent::__construct($values, 'item');
  }

  public function myConstruct($ident, $type, $title, $objective = null, $max_attempts = '') {
    $this->id = $ident;
    $this->type = $type;
    $this->title = $title;
    //$this->objective = $objective;
    $this->max_attempts = $max_attempts;
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

  public function getSectionid() {
    return $this->sectionid;
  }

  public function setSectionid($sectionid) {
    $this->sectionid = $sectionid;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getId() {
    return $this->id;
  }

  public function getType() {
    return $this->type;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function getQuestion() {
    return $this->question;
  }

  public function setQuestion($question) {
    $this->question = $question;
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function getTitle() {
    return $this->title;
  }

  public function setObjective($objective) {
    $this->objective = $objective;
  }

  public function getObjective() {
    return $this->objective;
  }

  public function setFeedback($feedback) {
    array_push($this->feedback, $feedback);
  }

  public function getFeedback() {
    return $this->feedback;
  }

  public function setHint($hint) {
    $this->hint = $hint;
  }

  public function getHint() {
    return $this->hint;
  }

  public function setSolutionFeedback($solutionFeedback) {
    $this->solutionFeedback = $solutionFeedback;
  }

  public function getSolutionFeedback() {
    return $this->solutionFeedback;
  }

  public function getMax_attempts() {
    return $this->max_attempts;
  }

  public function setMax_attempts($max_attempts) {
    $this->max_attempts = $max_attempts;
  }

  public function setPossibility($possibilities) {
    array_push($this->possibilities, $possibilities);
  }

  public function getPossibilities() {
    return $this->possibilities;
  }

  public function setContent($content) {
    $this->content = serialize($content);
  }

  public function getContent() {
    return unserialize($this->content);
  }
}
