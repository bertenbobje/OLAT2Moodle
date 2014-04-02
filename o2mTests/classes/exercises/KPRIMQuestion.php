<?php

class KPRIMQuestion extends Item {

  public $answers = array();
  public $randomOrder;
  public $score;

  public function __construct($values = array()) {
    parent::__construct($values, 'qtici_KPRIM');
  }

  public function setAnswer($answers) {
    array_push($this->answers, $answers);
  }

  public function getAnswer() {
    return $this->answers;
  }

  public function setRandomOrder($randomOrder) {
    if ($randomOrder == 'Yes') {
      $this->randomOrder = TRUE;
    }
    else {
      $this->randomOrder = FALSE;
    }
  }

  public function getRandomOrder() {
    return $this->randomOrder;
  }

  public function setScore($score) {
    $this->score = $score;
  }

  public function getScore() {
    return $this->score;
  }
  
  public function parseXML($item) {
    $this->setRandomOrder((string) getDataIfExists($item, 'presentation', 'response_lid', 'render_choice', 'attributes()', 'shuffle'));
    $this->setScore((string) getDataIfExists($item, 'resprocessing', 'outcomes', 'decvar', 'attributes()', 'maxvalue'));
    // Set Type
    $this->setType('KPRIM');
    // Get answers
    foreach ($item->presentation->response_lid->render_choice->children() as $child) {
      $possibility = new Possibility();
      $answer['value'] = (string) getDataIfExists($child, 'response_label', 'material', 'mattext');
      $answer['format'] = (string) getDataIfExists($flow_label, 'response_label', 'material', 'mattext', 'texttype');
      $possibility->myConstruct(NULL, (string) getDataIfExists($child, 'response_label', 'attributes()', 'ident'), ElementTypes::RADIOBUTTON, NULL, serialize($answer), NULL, NULL, NULL);
      $this->setPossibility($possibility);
    }

    $results = $item->xpath('resprocessing/respcondition[conditionvar/and]');
    foreach ($results as $result) {
      foreach ($result->conditionvar->and->varequal as $arrayAnswer) {
        $id = substr($arrayAnswer, 0, strpos($arrayAnswer, ':'));
        $status = substr($arrayAnswer, strpos($arrayAnswer, ':') + 1);
        $answers[$id] = $status;
      }
    }
    $this->setAnswer($answers);

    parent::parseXML($item);
  }

}
