<?php

class MultipleChoiceQuestion extends Item {

  public $quotation;
  public $answers = array();
  public $score;
  public $randomOrder;

  public function __construct($values = array()) {
    parent::__construct($values, 'qtici_MCQ');
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
    $this->quotation = $item->quotation;
    $this->answers = NULL;
    $this->score = $item->score;
    $this->randomOrder = $item->ordering;
  }

  public function setQuotation($quotation) {
    $this->quotation = $quotation;
  }

  public function getQuotation() {
    return $this->quotation;
  }

  public function setAnswer($answers) {
    array_push($this->answers, $answers);
  }

  public function getAnswer() {
    return $this->answers;
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
    $this->setRandomOrder((string) getDataIfExists($item, 'presentation', 'response_lid', 'render_choice', 'attributes()', 'shuffle'));
    // Set Type
    $this->setType('MCQ');
    // Get Quotation
    $outputArray = getQuotationType($item);
    $quotation = $outputArray['quotation'];
    $results = $outputArray['results'];

    $this->setQuotation($quotation);

    // Get correct answers
    $correct = array();
    foreach ($item->resprocessing->respcondition as $resp) {
      if ($resp->attributes()->title == 'Mastery') {
        if (getDataIfExists($resp, 'conditionvar', 'and', 'varequal')) {
          foreach ($resp->conditionvar->and->varequal as $varequal) {
            $correct[] = (int) getDataIfExists($varequal);
          }
        }
        else {
          foreach ($resp->conditionvar->varequal as $varequal) {
            $correct[] = (int) getDataIfExists($varequal);
          }
        }
      }
    }
 
    // Get answers
    foreach ($item->presentation->response_lid->render_choice->children() as $child) {
      $possibility = new Possibility();
      $content['value'] = (string) getDataIfExists($child, 'response_label', 'material', 'mattext');
      $content['format'] = (string) getDataIfExists($child, 'response_label', 'material', 'mattext', 'texttype');
      if (empty($content['format'])) {
        $content['format'] = 'full_html';
      }
      $ident = (int) getDataIfExists($child, 'response_label', 'attributes()', 'ident');
      $is_correct = 0;
      if (in_array($ident, $correct)) {
        $is_correct = 1;
      }
      $possibility->myConstruct(NULL, $ident, ElementTypes::CHECKBOX, NULL, serialize($content), NULL, $is_correct, NULL);
      $this->setPossibility($possibility);
    }

    // Set Score
    $answers = array();
    if ($quotation == 'allCorrect') {
      foreach ($results as $result) {
        $arrayAnswer = $result->conditionvar->and;
        if (count($arrayAnswer->varequal) != 0) {
          for ($i = 0; $i < count($arrayAnswer->varequal); $i++) {
            $title = (string) $result->attributes()->title;
            $answers[$title] = (string) $arrayAnswer->varequal[$i];
          }
        }
        $this->setScore((string) $results[0]->setvar);
      }
    }
    elseif ($quotation == 'perAnswer') {
      foreach ($results as $result) {
        $answers[$result->attributes()->title] = array(
          'value' => (string) $result->conditionvar->varequal,
          'score' => (string) $result->setvar,
        );
      }
    }
    $this->setAnswer($answers);

    parent::parseXML($item);
  }

}

?>
