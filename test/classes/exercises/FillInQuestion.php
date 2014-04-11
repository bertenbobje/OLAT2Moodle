<?php

class FillInQuestion extends Item {

  public $quotation;
  public $answers = array();
  public $score;

  public function __construct($values = array()) {
    parent::__construct($values, 'qtici_FIB');
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
    $this->quotation = $item->quotation;
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

  public function parseXML($item) {
    // Set Type
    $this->setType('FIB');
    // Get Quotation
    $outputArray = getQuotationType($item);
    $quotation = $outputArray['quotation'];
    $results = $outputArray['results'];

    $this->setQuotation($quotation);

    // Get Answers
    $answers = array();
    if ($quotation == 'allCorrect') {
      foreach ($results as $result) {
        foreach ($result->conditionvar->and->or as $arrayAnswer) {
          $ident = (int) $arrayAnswer->varequal->attributes()->respident;
          $answers[$ident] = (string) $arrayAnswer->varequal;
        }
        $this->setAnswer($answers);
        $this->setScore((string) $result->setvar);
      }
    }
    elseif ($quotation == 'perAnswer') {
      foreach ($results as $result) {
        $answers[(string) $result->conditionvar->or->varequal['respident']] = array(
          'value' => (string) $result->conditionvar->or->varequal,
          'score' => (string) $result->setvar,
        );
        $this->setAnswer($answers);
      }
    }

    $content = '';
    foreach ($item->presentation->flow->children() as $child) {
      // MATERIAL can have the mattext or matimage elements (text/image)
      if ($child->getName() == 'material') {
        $materialArray = $child->xpath('*');
        foreach ($materialArray as $element) {
          if ($element->getName() == 'mattext') {
            $content .= (string) $element;
          }
          if ($element->getName() == 'matimage') {
            // Save image
            $newFile = file_save_upload((string) $element['uri'], array('file_validate_extensions' => array($allowed)));
            $newFile = file_move($newFile, 'public://');
            $newFile->status = 1; // Make permanent
            $newFile = file_save($newFile);
            $content .= ':img' . $newFile->fid . 'fid:';
          }
        }
      }
      elseif ($child->getName() == 'response_str') { // TEXTBOX
        $ident = (int) getDataIfExists($child, 'attributes()', 'ident');
        $content .= ':text' . $ident . 'box:';
        $possibility = new Possibility();
        $answer = NULL;
        if ($ident && !empty($answers[$ident])) {
          if (is_object($answers[$ident])) {
            $answer = $answers[$ident]->value;
          }
          else {
            $answer = $answers[$ident];
          }
        }
        $dumbAns['value'] = $answer;
        $dumbAns['format'] = 'full_html';
        $possibility->myConstruct(NULL, (string) getDataIfExists($child, 'attributes()', 'ident'), ElementTypes::TEXTBOX, NULL, serialize($dumbAns), NULL, NULL, NULL);
        $this->setPossibility($possibility);
      }
    }
    $this->setContent(html_entity_decode($content));

    parent::parseXML($item);
  }

}
