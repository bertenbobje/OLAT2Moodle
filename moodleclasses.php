<?php

/**********************************************************
/* Collection of objects for storing Moodle data.
/**********************************************************
/* Bert Truyens
/*********************************************************/

//                                             ________________
// MoodleCourse (1)                           |                |
//    |___> Section (1+)                      v                | recursion
//               |_______________________> Activity (1+) ______|
//                      moduleID's            |
// Folder                                     |
//                                            - ActivityPage
//                                            - ActivityQuiz
//                                            - ActivityURL
//                                            - ActivityBook
//                                            - ActivityWiki
//                                            - ActivityFolder
//                                            - ActivityResource
//

///////////////////////////////////////////////////////////
// COURSE /////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class MoodleCourse {
	
  protected $id;
  protected $contextID;

  public $shortName;
  public $fullName;
	public $categoryID;
	public $sections = array();
	
	public function __construct($id, $shortName, $fullName, $categoryID) {
		$this->id = $id;
		$this->contextID = (string) ($id + 2);
		$this->shortName = $shortName;
		$this->fullName = $fullName;
		$this->categoryID = $categoryID;
	}
	
	public function setID($id) {
		$this->id = $id;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function setContextID($type) {
		$this->contextID = $contextID;
	}
	
	public function getContextID() {
		return $this->contextID;
	}
	
	public function setShortName($shortName) {
		$this->shortName = $shortName;
	}
	
	public function getShortName() {
		return $this->shortName;
	}
	
	public function setFullName($longName) {
		$this->longName = $longName;
	}
	
	public function getFullName() {
		return $this->fullName;
	}
	
	public function setCategoryID($categoryID) {
		array_push($this->categoryID, $categoryID);
	}
	
	public function getCategoryID() {
		return $this->categoryID;
	}
	
	public function setSection($sections) {
		array_push($this->sections, $sections);
	}
	
	public function getSection() {
		return $this->sections;
	}
	
}

///////////////////////////////////////////////////////////
// SECTION ////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Section {

	protected $sectionID;
	protected $name;
	protected $number;
	protected $activities = array();
	
	public function __construct($sectionID, $name, $number) {
		$this->sectionID = $sectionID;
		$this->name = $name;
		$this->number = $number;
	}
	
	public function setSectionID($sectionID) {
		$this->sectionID = $sectionID;
	}
	
	public function getSectionID() {
		return $this->sectionID;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setNumber($number) {
		$this->number = $number;
	}
	
	public function getNumber() {
		return $this->number;
	}
	
	public function setActivity($activities) {
		array_push($this->activities, $activities);
	}
	
	public function getActivity() {
		return $this->activities;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY ///////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Activity {

	protected $activityID;
	protected $moduleID;
	protected $moduleName;
	protected $contextID;
	protected $name;
	
	public function __construct($activityID, $moduleName, $name) {
		$this->activityID = $activityID;
		$this->moduleID = (string) ($activityID + 1);
		$this->moduleName = $moduleName;
		$this->contextID = (string) ($activityID + 2);
		$this->name = $name;
	}
	
	public function setActivityID($activityID) {
		$this->activityID = $activityID;
		$this->moduleID = (string) ($activityID + 1);
		$this->contextID = (string) ($activityID + 2);
	}
	
	public function getActivityID() {
		return $this->activityID;
	}
	
	public function setModuleID($moduleID) {
		$this->moduleID = $moduleID;
	}
	
	public function getModuleID() {
		return $this->moduleID;
	}

	public function setModuleName($moduleName) {
		$this->moduleName = $moduleName;
	}
	
	public function getModuleName() {
		return $this->moduleName;
	}
	
	public function setContextID($contextID) {
		$this->contextID = $contextID;
	}
	
	public function getContextID() {
		return $this->contextID;
	}
	
	public function setName($name) {
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
}

///////////////////////////////////////////////////////////
// ACTIVITY PAGE //////////////////////////////////////////
///////////////////////////////////////////////////////////
class ActivityPage extends Activity {

	protected $content;
	
	public function __construct($content) {
		$this->content = $content;
	}
	
	public function setContent($content) {
		$this->content = $content;
	}
	
	public function getContent() {
		return $this->content;
	}
	
}

?>
