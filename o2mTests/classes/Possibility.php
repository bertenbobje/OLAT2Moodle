<?php

class Possibility extends Entity {

  public $id;
  public $ident;
  public $type;
  public $possibility; // Type of possibility: radio, checkbox, textbox
  public $itemid;
  public $answer; // Content of the possibility
  public $ordering;
  public $is_correct;
  public $score;

  public function __construct($values = array()) {
    parent::__construct($values, 'possibility');
  }

  function myConstruct($id, $ident, $possibility, $itemid, $answer, $ordering, $is_correct, $score) {
    $this->id = $id;
    $this->ident = $ident;
    $this->possibility = $possibility;
    $this->itemid = $itemid;
    $this->answer = $answer;
    $this->ordering = $ordering;
    $this->is_correct = $is_correct;
    $this->score = $score;
  }

  public function getIdent() {
    return $this->ident;
  }

  public function setIdent($ident) {
    $this->ident = $ident;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function setPossibility($possibility) {
    $this->possibility = $possibility;
  }

  public function getPossibility() {
    return $this->possibility;
  }

  public function getItemid() {
    return $this->itemid;
  }

  public function setItemid($itemid) {
    $this->itemid = $itemid;
  }

  public function getAnswer() {
    return $this->answer;
  }

  public function setAnswer($answer) {
    $this->answer = $answer;
  }

  public function getOrdering() {
    return $this->ordering;
  }

  public function setOrdering($ordering) {
    $this->ordering = $ordering;
  }

  public function getIs_correct() {
    return $this->is_correct;
  }

  public function setIs_correct($is_correct) {
    $this->is_correct = $is_correct;
  }

  public function getScore() {
    return $this->score;
  }

  public function setScore($score) {
    $this->score = $score;
  }
}

?>
