<?php

class Section extends Entity {

  //public $ident;
  public $id;
  public $testid;
  public $title;
  public $objective;
  public $description;
  public $ordering;
  public $items = array();

  function __construct() {
    
  }

  function myConstruct($id) {
    $this->id = $id;
  }

  function myFullConstruct($section) {
    $this->id = $section->id;
    $this->testid = $section->testid;
    $this->title = $section->title;
    $this->description = $section->description;
    $this->ordering = $section->ordering;
    $this->items = NULL;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getTestid() {
    return $this->testid;
  }

  public function setTestid($testid) {
    $this->testid = $testid;
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

  public function getDescription() {
    return $this->description;
  }

  public function setDescription($description) {
    $this->description = $description;
  }

  public function setOrdering($ordering) {
    $this->ordering = $ordering;
  }

  public function getOrdering() {
    return $this->ordering;
  }

  public function setItem($item) {
    array_push($this->items, $item);
  }

  public function getItems() {
    return $this->items;
  }
}

?>
