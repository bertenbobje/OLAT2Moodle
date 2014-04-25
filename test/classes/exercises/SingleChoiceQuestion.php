<?php

class SingleChoiceQuestion extends Item {

  public $answer;
  public $score;
  public $randomOrder;

  public function __construct($values = array()) {
    parent::__construct($values, 'qtici_SCQ');
  }

  function myFullConstruct($item) {
    $this->type = $item->type;
    $this->title = $item->title;
    $this->objective = NULL;
    $this->feedback = NULL;
    $this->hint = NULL;
    $this->solutionFeedback = NULL;
    $this->max_attempts = $item->max_attempts;
    $this->possibilities = NULL;
    $this->question = $item->question;
    $this->id = $item->id;
    $this->answer = NULL;
    $this->score = $item->score;
    $this->randomOrder = $item->ordering;
  }

  public function setAnswer($answer) {
    $this->answer = $answer;
  }

  public function getAnswer() {
    return $this->answer;
  }

  public function setScore($score) {
    $this->score = $score;
  }

  public function getScore() {
    return $this->score;
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

  /**
   * Parser function. $item is the loaded XML object
   */
  public function parseXML($item) {
    
    $this->setMax_attempts((string) getDataIfExists($item, 'attributes()', 'maxattempts'));
    $this->setRandomOrder((string) getDataIfExists($item, 'presentation', 'response_lid', 'render_choice', 'attributes()', 'shuffle'));
    // Set Type
    $this->setType('SCQ');
    // Get correct answers
    $correct = array();
    foreach ($item->resprocessing->respcondition as $resp) {
      if ($resp->attributes()->title == 'Mastery') {
        if (getDataIfExists($resp, 'conditionvar', 'and', 'varequal')) {
          $correct[] = (int) getDataIfExists($resp, 'conditionvar', 'and', 'varequal');
        }
        else {
          $correct[] = (int) getDataIfExists($resp, 'conditionvar', 'varequal');
        }
      }
    }
    // For SCQ we only need to handle answers, the rest of the data is generic to all items
    foreach ($item->presentation->response_lid->render_choice->children() as $flow_label) {
      $possibility = new Possibility();
      $content['value'] = (string) getDataIfExists($flow_label, 'response_label', 'material', 'mattext');
      $content['format'] = (string) getDataIfExists($flow_label, 'response_label', 'material', 'mattext', 'texttype');
      if (empty($content['format'])) {
        $content['format'] = 'full_html';
      }
      $ident = (int) getDataIfExists($flow_label, 'response_label', 'attributes()', 'ident');
      $is_correct = 0;
      if (in_array($ident, $correct)) {
        $is_correct = 1;
      }
      $possibility->myConstruct(NULL, $ident, ElementTypes::RADIOBUTTON, NULL, serialize($content), NULL, $is_correct, NULL);
      $this->setPossibility($possibility);
    }

    parent::parseXML($item);
  }

}

?>
