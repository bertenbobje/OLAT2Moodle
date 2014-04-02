<?php

class Feedback {

  public $id;
  public $itemid;
  public $possibilityid;
  public $feedback_possibility;
  public $feedback_positive;
  public $feedback_negative;
  public $hint;
  public $solution_feedback;

  function __construct() {
    
  }

  function myConstruct($id, $itemid, $possibilityid, $feedback_possibility, $feedback_positive, $feedback_negative, $hint, $solution_feedback) {
    $this->id = $id;
    $this->itemid = $itemid;
    $this->possibilityid = $possibilityid;
    $this->feedback_possibility = $feedback_possibility;
    $this->feedback_positive = $feedback_positive;
    $this->feedback_negative = $feedback_negative;
    $this->hint = $hint;
    $this->solution_feedback = $solution_feedback;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getItemid() {
    return $this->itemid;
  }

  public function setItemid($itemid) {
    $this->itemid = $itemid;
  }

  public function getPossibilityid() {
    return $this->possibilityid;
  }

  public function setPossibilityid($possibilityid) {
    $this->possibilityid = $possibilityid;
  }

  public function getFeedback_possibility() {
    return $this->feedback_possibility;
  }

  public function setFeedback_possibility($feedback_possibility) {
    $this->feedback_possibility = $feedback_possibility;
  }

  public function getFeedback_positive() {
    return $this->feedback_positive;
  }

  public function setFeedback_positive($feedback_positive) {
    $this->feedback_positive = $feedback_positive;
  }

  public function getFeedback_negative() {
    return $this->feedback_negative;
  }

  public function setFeedback_negative($feedback_negative) {
    $this->feedback_negative = $feedback_negative;
  }

  public function getHint() {
    return $this->hint;
  }

  public function setHint($hint) {
    $this->hint = $hint;
  }

  public function getSolution_feedback() {
    return $this->solution_feedback;
  }

  public function setSolution_feedback($solution_feedback) {
    $this->solution_feedback = $solution_feedback;
  }
}

?>
