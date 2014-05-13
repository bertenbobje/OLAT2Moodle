<?php

require_once("classes/generalclasses.php");
require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");

require_once("functions/general.php");

// Creates the backup file that Moodle can use to restore a course.
//
// Bert Truyens
//
/*************************************************************************************
 _________________________
|                         |
| MOODLE BACKUP STRUCTURE |
|_________________________|

[ ] = folder   |  (E)  = "empty" -- The same for every backup (empty XML tags).
||| = file     |  (EE) = completely empty (0B) 

[ ] ROOT FOLDER (.mbz)
 |_ [ ] activities ---------- Contains all activities (forums, pages, etc.)
 |   |_ [ ] forum_40 -------} <type of activity>_<moduleID of said activity>
 |   |_ [ ] page_41 --------} 
 |   |   |_ ||| filters.xml - (E)
 |   |   |_ ||| grades.xml -- (E)
 |   |   |_ ||| inforef.xml - Contains references to used media (fileIDs)
 |   |   |_ ||| module.xml -- Contains IDs and general module options
 |   |   |_ ||| page.xml ---- Contains IDs, the name and the content of the page
 |   |   |_ ||| roles.xml --- (E)
 |   |_ ...
 |_ [ ] course -------------- Contains general information about the entire course
 |   |_ ||| course.xml ------ Contains IDs, names and options
 | 	 |_ ||| enrolments.xml -- (E)
 |   |_ ||| filters.xml ----- Contains filter options (for turning off autohotlink)
 | 	 |_ ||| inforef.xml ----- Contains references (courseID)
 | 	 |_ ||| roles.xml ------- (E)
 |_ [ ] files --------------- Contains the external media files (SHA-1 hashed)
 |   |_ [ ] 6f -------------} First two characters of SHA-1 hash of underlying file
 |   |_ [ ] 9a -------------}
 |   |   |_ ||| 9ac490e3eed9b77a26a91e900af0647ab94554e7 - SHA-1 hashed file
 |   |_ ...
 |_ [ ] sections ------------ Contains all sections (topics)
 |   |_ [ ] section_24 -----} section_<sectionID of said section>
 |   |_ [ ] section_25 -----}
 |   |   |_ ||| inforef.xml - Contains references (E)
 |   |   |_ ||| section.xml - Contains IDs, names, and sequences of sections
 |   |_ ...                                                      --> moduleIDs
 |_ ||| completion.xml ------ (E)
 |_ ||| files.xml ----------- Contains information about the SHA-1 hashed files
 |_ ||| gradebook.xml ------- (E)
 |_ ||| groups.xml ---------- (E)
 |_ ||| moodle_backup.log --- (EE)
 |_ ||| moodle_backup.xml --- General .xml containing all references (biggest XML)
 |_ ||| outcomes.xml -------- (E)
 |_ ||| questions.xml ------- Contains the question bank
 |_ ||| roles.xml ----------- (E)
 |_ ||| scales.xml ---------- (E)
 
*************************************************************************************/

//
// PARAMETERS
// -> $moodleObject = Moodle Object
//        $olatPath = OLAT Object (for the files)
//           $books = Reads out the checkbox in the beginning and turns pages in a row
//                    into a single book for a more clear overview
//   $chapterFormat = The chapter format (the choice box in the first page reflects this)
//           $error = The error handler
//
function moodleObjectToMoodleBackup($moodleObject, $olatObject, $books, $chapterFormat, &$error) {
	
	$questionID = 500000;
	$answerID = 5000000;
	
	// Creates a temporary storage name made of random numbers.
	$num = "moodle";
	for ($i = 0; $i < 9; $i++) {
		$num .= strval(mt_rand(0, 9));
	}
	
	$path = getcwd() . "/tmp/" . $num;
	
	// Checks if the folders exist and creates them if they do not.
	if (!file_exists(getcwd() . "/tmp") && !is_dir(getcwd() . "/tmp")) {
		mkdir(getcwd() . "/tmp", 0777, true);
	}
	if (!file_exists($path) && !is_dir($path)) {
		mkdir($path, 0777, true);
	}
	
	// This formats the xml files so it's not all on one line.
	$dom = new DOMDocument('1.0');
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;	
	
	// The header of every .xml file is always the same.
	$header = '<?xml version="1.0" encoding="UTF-8"?>';

	// moodle_backup.xml, the general backup .xml file containing everything
	// This will get added to a lot through the process.
	$moodleBackupXmlStart = new SimpleXMLElement($header . "<moodle_backup></moodle_backup>");
	$moodleBackupXml = $moodleBackupXmlStart->addChild('information');
	$moodleBackupXml->addChild('name', clean($moodleObject->getFullName()) . '.mbz');
	$moodleBackupXml->addChild('moodle_version', 2013062400);
	$moodleBackupXml->addChild('moodle_release', '2.5');
	$moodleBackupXml->addChild('backup_version', 2013062400);
	$moodleBackupXml->addChild('backup_release', '2.5');
	$moodleBackupXml->addChild('backup_date', time());
	$moodleBackupXml->addChild('mnet_remoteusers', 0);
	$moodleBackupXml->addChild('include_files', 1);
	$moodleBackupXml->addChild('include_file_references_to_external_content', 0);
	$moodleBackupXml->addChild('original_wwwroot', 'OLAT2Moodle');
	$moodleBackupXml->addChild('original_site_identifier_hash', "2221f5e20fe9c6708db19a7804aee7a34b077352");
	$moodleBackupXml->addChild('original_course_id', $moodleObject->getID());
	$moodleBackupXml->addChild('original_course_fullname', $moodleObject->getFullName());
	$moodleBackupXml->addChild('original_course_shortname', $moodleObject->getShortName());
	$moodleBackupXml->addChild('original_course_startdate', time());
	$moodleBackupXml->addChild('original_course_contextid', $moodleObject->getContextID());
	$moodleBackupXml->addChild('original_system_contextid', 1);
	$moodleBackupXmlDetails = $moodleBackupXml->addChild('details');
	$moodleBackupXmlDetailsDetail = $moodleBackupXmlDetails->addChild('detail');
	$moodleBackupXmlDetailsDetail->addAttribute('backup_id', sha1($moodleObject->getID()));
	$moodleBackupXmlDetailsDetail->addChild('type', 'course');
	$moodleBackupXmlDetailsDetail->addChild('format', 'moodle2');
	$moodleBackupXmlDetailsDetail->addChild('interactive', 1);
	$moodleBackupXmlDetailsDetail->addChild('mode', 10);
	$moodleBackupXmlDetailsDetail->addChild('execution', 1);
	$moodleBackupXmlDetailsDetail->addChild('executiontime', 1);
	$moodleBackupXmlContents = $moodleBackupXml->addChild('contents');
	$moodleBackupXmlContentsActivities = $moodleBackupXmlContents->addChild('activities');
	$moodleBackupXmlContentsSections = $moodleBackupXmlContents->addChild('sections');
	$moodleBackupXmlContentsCourse = $moodleBackupXmlContents->addChild('course');
	$moodleBackupXmlSettings = $moodleBackupXml->addChild('settings');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'filename', clean($moodleObject->getFullName()) . '.mbz');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'imscc11', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'users', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'anonymize', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'role_assignments', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'activities', '1');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'blocks', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'filters', '1');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'comments', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'badges', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'calendarevents', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'userscompletion', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'logs', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'grade_histories', '0');
	moodleBackupDefaultSettings($moodleBackupXmlSettings, 'root', 'questionbank', '1');
	
	// questions.xml, the question bank.
	// Whenever there is a quiz, this is where it will get the questions from.
	// Every quiz page will have its own question bank that is available over the entire course,
	// this means people can use all these question banks for other tests (like an exam).
	$questionsXml = new SimpleXMLElement($header . "<question_categories></question_categories>");
	$quizCounter = 1;
	$pageCounter = 1;
	$multiChoiceID = 1;
	$multiChoiceSetID = 1;
	$shortAnswerID = 1;
	$multiAnswerID = 1;
	$essayID = 1;
	$allQuestions = true;
	foreach ($moodleObject->getSection() as $section) {
		foreach ($section->getActivity() as $activity) {
			if ($activity->getModuleName() == "quiz") {
				if ($allQuestions) {
					$questionCategory = $questionsXml->addChild('question_category');
					$questionCategory->addAttribute('id', $moodleObject->getID() - 1);
					$questionCategory->addChild('name', "ALL QUESTIONS");
					$questionCategory->addChild('contextid', $moodleObject->getID());
					$questionCategory->addChild('contextlevel', 50);
					$questionCategory->addChild('contextinstanceid', $moodleObject->getID());
					$questionCategory->addChild('info', "All questions for the &lt;b&gt;" . $moodleObject->getFullName() . "&lt;/b&gt; course");
					$questionCategory->addChild('infoformat', 1);
					$questionCategory->addChild('stamp', 0);
					$questionCategory->addChild('parent', 0);
					$questionCategory->addChild('sortorder', 999);
					$questionCategory->addChild('questions');
					$allQuestions = false;
				}
				
				$questionCategory = $questionsXml->addChild('question_category');
				$questionCategory->addAttribute('id', $activity->getActivityID());
				$questionCategory->name = sprintf("%03s", $quizCounter) . " - " . $activity->getName();
				$quizCounter++;
				$questionCategory->addChild('contextid', $moodleObject->getID());
				$questionCategory->addChild('contextlevel', 50);
				$questionCategory->addChild('contextinstanceid', $moodleObject->getID());
				$questionCategory->addChild('info', "Category for the entire &lt;b&gt;" . $activity->getName() . "&lt;/b&gt; quiz.");
				$questionCategory->addChild('infoformat', 1);
				$questionCategory->addChild('stamp', 0);
				$questionCategory->addChild('parent', $moodleObject->getID() - 1);
				$questionCategory->addChild('sortorder', 999);
				$questionCategory->addChild('questions');
				
				foreach ($activity->getQuizPages() as $qp) {
					$questionCategory = $questionsXml->addChild('question_category');
					$questionCategory->addAttribute('id', $qp->getPageID());
					$name = sprintf("%03s", $pageCounter) . " - " . $qp->getPageTitle();
					$pageCounter++;
					$questionCategory->name = $name;
					$questionCategory->addChild('contextid', $moodleObject->getID());
					$questionCategory->addChild('contextlevel', 50);
					$questionCategory->addChild('contextinstanceid', $moodleObject->getID());
					$questionCategory->addChild('info', "Category for the &lt;b&gt;" . $qp->getPageTitle() . "&lt;/b&gt; page from the &lt;b&gt;" . $activity->getName() . "&lt;/b&gt; quiz.");
					$questionCategory->addChild('infoformat', 1);
					$questionCategory->addChild('stamp', 0);
					$questionCategory->addChild('parent', $activity->getActivityID());
					$questionCategory->addChild('sortorder', 999);
					$questionCategoryQuestions = $questionCategory->addChild('questions');
					foreach ($qp->getPageQuestions() as $qpq) {
						switch ($qpq->getQType()) {
							case "SCQ":
							case "MCQ":
								if ($qpq->getQType() == "MCQ" && $qpq->getQQuotation() == "allCorrect") {
									$type = "multichoiceset";	// MCQ (allCorrect) -------> Becomes a Multichoiceset object in Moodle	
								}
								else {
									$type = "multichoice";		// SCQ or MCQ (perAnswer) -> Becomes a Multichoice object in Moodle
								}
								break;
							case "FIB":
								if (count($qpq->getQPossibilities()) == 1) {
									$type = "shortanswer";		// FIB (1)  ---------------> Becomes a Short Answer object in Moodle
								}
								else {
									$type = "multianswer";		// FIB (2+) ---------------> Becomes a Cloze object in Moodle
								}
								break;
							case "ESSAY":
								$type = "essay";						// ESSAY ------------------> Becomes an Essay object in Moodle
								break;
						}
						if ($type == "multianswer") {
							questionBankMultiAnswer($questionCategoryQuestions, $qpq, $multiAnswerID, $shortAnswerID, $questionID, $answerID);
						}
						else {
							$questionCategoryQuestion = $questionCategoryQuestions->addChild('question');
							$questionCategoryQuestion->addAttribute('id', $qpq->getQID());
							$questionCategoryQuestion->addChild('parent', 0);
							$questionCategoryQuestion->name = $qpq->getQTitle();
							$questionCategoryQuestion->addChild('questiontext', preg_replace("/ {#.+?} /ism", "", $qpq->getQQuestion()));
							$questionCategoryQuestion->addChild('questiontextformat', 1);
							$questionCategoryQuestion->addChild('generalfeedback');
							$questionCategoryQuestion->addChild('generalfeedbackformat', 1);
							if (!is_null($qpq->getQScore()) && $qpq->getQScore() != "") {
								$questionCategoryQuestion->addChild('defaultmark', (string) $qpq->getQScore() . "000000");
							}
							else {
								$questionCategoryQuestion->addChild('defaultmark', "1.000000");
							}
							$questionCategoryQuestion->addChild('penalty', "0.3333333");
							$questionCategoryQuestion->addChild('qtype', $type);
							$questionCategoryQuestion->addChild('length', 1);
							$questionCategoryQuestion->addChild('stamp', 0);
							$questionCategoryQuestion->addChild('version', 0);
							$questionCategoryQuestion->addChild('hidden', 0);
							$questionCategoryQuestion->addChild('timecreated', time());
							$questionCategoryQuestion->addChild('timemodified', time());
							$questionCategoryQuestion->addChild('createdby', 2);
							$questionCategoryQuestion->addChild('modifiedby', 2);
							$questionCategoryQuestionPlugin = $questionCategoryQuestion->addChild('plugin_qtype_' . $type . '_question');
							if ($type != "essay") {
								$questionCategoryQuestionAnswers = $questionCategoryQuestionPlugin->addChild('answers');
								$amountCorrect = 0;
								$amountIncorrect = 0;
								foreach ($qpq->getQPossibilities() as $qpqp) {
									if ($qpqp->getQPIsCorrect()) {
										$amountCorrect++;
									}
									else {
										$amountIncorrect++;
									}
								}
								foreach ($qpq->getQPossibilities() as $qpqp) {
									$questionCategoryQuestionAnswer = $questionCategoryQuestionAnswers->addChild('answer');
									$questionCategoryQuestionAnswer->addAttribute('id', $qpqp->getQPID());
									$questionCategoryQuestionAnswer->addChild('answertext', $qpqp->getQPAnswer());
									$questionCategoryQuestionAnswer->addChild('answerformat', 1);
									if ($qpqp->getQPIsCorrect() || $type == "shortanswer") {
										$questionCategoryQuestionAnswer->addChild('fraction', "1.0000000");
									}
									else {
										$questionCategoryQuestionAnswer->addChild('fraction', "0.0000000");
									}
									if (!is_null($qpqp->getQPFeedback())) {
										$questionCategoryQuestionAnswer->addChild('feedback', $qpqp->getQPFeedback());
									}
									else {
										$questionCategoryQuestionAnswer->addChild('feedback');
									}
									$questionCategoryQuestionAnswer->addChild('feedbackformat', 1);
								}
							}
							$questionCategoryQuestionType = $questionCategoryQuestionPlugin->addChild($type);
							switch ($type) {
								case "multichoice":
									$questionCategoryQuestionType->addAttribute('id', $multiChoiceID);
									$questionCategoryQuestionType->addChild('layout', 0);
									$questionCategoryQuestionType->addChild('answers');
									if ($qpq->getQType() == "SCQ") {
										$questionCategoryQuestionType->addchild('single', 1);
									}
									else {
										$questionCategoryQuestionType->addchild('single', 0);
									}
									if ($qpq->getQShuffle()) {
										$questionCategoryQuestionType->addchild('shuffleanswers', 1);
									}
									else {
										$questionCategoryQuestionType->addchild('shuffleanswers', 0);
									}
									$questionCategoryQuestionType->addchild('correctfeedback', "");
									$questionCategoryQuestionType->addchild('correctfeedbackformat', 1);
									$questionCategoryQuestionType->addchild('partiallycorrectfeedback', "");
									$questionCategoryQuestionType->addchild('partiallycorrectfeedbackformat');
									$questionCategoryQuestionType->addchild('incorrectfeedback', "");
									$questionCategoryQuestionType->addchild('incorrectfeedbackformat', 1);
									$questionCategoryQuestionType->addchild('answernumbering', "none");
									$questionCategoryQuestionType->addchild('shownumcorrect', 1);
									$multiChoiceID++;
									break;
								case "multichoiceset":
									$questionCategoryQuestionType->addAttribute('id', $multiChoiceSetID);
									$questionCategoryQuestionType->addChild('layout', 0);
									$questionCategoryQuestionType->addChild('answers');
									if ($qpq->getQShuffle()) {
										$questionCategoryQuestionType->addchild('shuffleanswers', 1);
									}
									else {
										$questionCategoryQuestionType->addchild('shuffleanswers', 0);
									}
									$questionCategoryQuestionType->addchild('correctfeedback', "");
									$questionCategoryQuestionType->addchild('correctfeedbackformat', 1);
									$questionCategoryQuestionType->addchild('incorrectfeedback', "");
									$questionCategoryQuestionType->addchild('incorrectfeedbackformat', 1);
									$questionCategoryQuestionType->addchild('answernumbering', "none");
									$questionCategoryQuestionType->addchild('shownumcorrect', 1);
									$multiChoiceSetID++;
									break;
								case "shortanswer":
									$questionCategoryQuestionType->addAttribute('id', $shortAnswerID);
									$questionCategoryQuestionType->addChild('usecase', 0);
									$shortAnswerID++;
									break;
								case "essay":
									$questionCategoryQuestionType->addAttribute('id', $essayID);
									$questionCategoryQuestionType->addChild('responseformat', "editor");
									$questionCategoryQuestionType->addChild('responsefieldlines', (string) roundUpToAny($qpq->getQLines()));
									$questionCategoryQuestionType->addChild('attachments', 0);
									$questionCategoryQuestionType->addChild('graderinfo');
									$questionCategoryQuestionType->addChild('graderinfoformat', 1);
									$questionCategoryQuestionType->addChild('responsetemplate');
									$questionCategoryQuestionType->addChild('responsetemplateformat', 1);
									$essayID++;
									break;
							}
							$questionCategoryQuestion->question_hints = $qpq->getQHint();
							$questionCategoryQuestion->addChild('tags');
						}
					}
					questionBankRandom($questionCategoryQuestions, $qp, $name);
				}
				$pageCounter = 1;
			}
		}
	}
	$dom->loadXML($questionsXml->asXML());
	file_put_contents($path . "/questions.xml", $dom->saveXML());
	
	////////////////////////////////////////////////////////////////////
	// COURSE
	
	// Create the /course folder
	$coursePath = $path . "/course";
	
	if (!file_exists($coursePath) && !is_dir($coursePath)) {
		mkdir($coursePath, 0777, true);
	}
	
	// "EMPTY"
	// course/enrolments.xml
	$courseEnrolmentsXml = new SimpleXMLElement($header . "<enrolments></enrolments>");
	$courseEnrolmentsXml->addChild('enrols');
	
	$dom->loadXML($courseEnrolmentsXml->asXML());
	file_put_contents($coursePath . "/enrolments.xml", $dom->saveXML());
	// course/roles.xml
	$courseRolesXml = new SimpleXMLElement($header . "<roles></roles>");
	$courseRolesXml->addChild('role_overrides');
	$courseRolesXml->addChild('role_assignments');
	
	$dom->loadXML($courseRolesXml->asXML());
	file_put_contents($coursePath . "/roles.xml", $dom->saveXML());
	
	// NOT "EMPTY"
	// course/inforef.xml
	$courseInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
	$courseInforefXml->addChild('roleref')->addChild('role')->addChild('id', $moodleObject->getID());
	$courseInfoRefQuestions = $courseInforefXml->addChild('question_categoryref');
	
	$allQuestions = true;
	foreach ($moodleObject->getSection() as $section) {
		foreach ($section->getActivity() as $activity) {
			if ($activity->getModuleName() == "quiz") {
				if ($allQuestions) {
					$courseInfoRefQuestionCat = $courseInfoRefQuestions->addChild('question_category');
					$courseInfoRefQuestionCat->addChild('id', $moodleObject->getID() - 1);
					$allQuestions = false;
				}
				$courseInfoRefQuestionCat = $courseInfoRefQuestions->addChild('question_category');
				$courseInfoRefQuestionCat->addChild('id', $activity->getActivityID());
				foreach ($activity->getQuizPages() as $qp) {
					$courseInfoRefQuestionCat = $courseInfoRefQuestions->addChild('question_category');
					$courseInfoRefQuestionCat->addChild('id', $qp->getPageID());
				}
			}
		}
	}
	
	$dom->loadXML($courseInforefXml->asXML());
	file_put_contents($coursePath . "/inforef.xml", $dom->saveXML());
	// course/filters.xml
	$courseFiltersXml = new SimpleXMLElement($header . "<filters></filters>");
	$courseFiltersActives = $courseFiltersXml->addChild('filter_actives');
	$courseFiltersActive = $courseFiltersActives->addChild('filter_active');
	$courseFiltersActive->addChild('filter', "activitynames");
	$courseFiltersActive->addChild('active', "-1");
	$courseFiltersXml->addChild('filter_configs');
	
	$dom->loadXML($courseFiltersXml->asXML());
	file_put_contents($coursePath . "/filters.xml", $dom->saveXML());
	// course/course.xml
	$courseCourseXml = new SimpleXMLElement($header . "<course></course>");
	$courseCourseXml->addAttribute('id', $moodleObject->getID());
	$courseCourseXml->addAttribute('contextid', $moodleObject->getID());
	$courseCourseXml->addChild('shortname', $moodleObject->getShortName());
	$courseCourseXml->addChild('fullName', $moodleObject->getFullName());
	$courseCourseXml->addChild('summary', "&lt;p&gt;" . $moodleObject->getFullName() . "&lt;/p&gt;");
	$courseCourseXml->addChild('format', 'topics');
	$courseCourseXml->addChild('startdate', time());
	$courseCourseXml->addChild('visible', 1);
	$courseCourseXml->addChild('defaultgroupingid', 0);
	$courseCourseXml->addChild('lang');
	$courseCourseXml->addChild('theme');
	$courseCourseXml->addChild('timecreated', time());
	$courseCourseXml->addChild('timemodified', time());
	$courseCourseXml->addChild('numsections', count($moodleObject->getSection()));
	
	$dom->loadXML($courseCourseXml->asXML());
	file_put_contents($coursePath . "/course.xml", $dom->saveXML());
	
	// moodle_backup.xml
	$moodleBackupXmlContentsCourse->addChild('courseid', $moodleObject->getID());
	$moodleBackupXmlContentsCourse->title = $moodleObject->getShortName();
	$moodleBackupXmlContentsCourse->addchild('directory', "course");
	
	////////////////////////////////////////////////////////////////////
	// FILES + files.xml
	
	// files.xml
	$filesXml = new SimpleXMLElement($header . "<files></files>");
	$fileID = 10;
	
	// Create the /files folder
	$filesPath = $path . "/files";
	if (!file_exists($filesPath) && !is_dir($filesPath)) {
		mkdir($filesPath, 0777, true);
	}
	
	// All files that are present in a page, resource or page turned book will be
	// fetched from the OLAT backup and put in its respective folders and files.xml
	// for Moodle
	$olatFilesPath = $olatObject->getRootdir() . "/coursefolder";	
	$olatFiles = listFolderFiles($olatFilesPath);
	foreach ($olatFiles as $olatFile) {
		$olatFilePath = $olatFilesPath . "/" . $olatFile;
		$fileSHA1 = sha1($olatFile);
		$fileSHA1Dir = $filesPath . "/" . substr($fileSHA1, 0, 2);
		if (!file_exists($fileSHA1Dir) && !is_dir($fileSHA1Dir)) {
			mkdir($fileSHA1Dir, 0777, true);
		}
		if (!is_dir($olatFilePath)) {
			if (copy($olatFilePath, $fileSHA1Dir . "/" . $fileSHA1)) {
				foreach ($moodleObject->getSection() as $section) {
					foreach ($section->getActivity() as $activity) {
						$fileOK = 0;
						$activityModuleName = $activity->getModuleName();
						switch ($activityModuleName) {
							case "page":
								// There can be a lot of possibilities for matching filenames, because of strange characters (umlauts)
								if (strpos($activity->getContent(), $olatFile) !== false
										|| strpos(urldecode($activity->getContent()), $olatFile) !== false) {	
									if (substr($olatFile, -4) == "html" || substr($olatFile, -3) == "htm") {
										findEmbeddedFiles($filesPath, $olatFilePath, $olatFilesPath, $filesXml, $fileID, $activity, $books);
									}
									$fileOK = 1;
									$filesXmlChild = $filesXml->addChild('file');
									$filesXmlChild->addAttribute('id', $fileID);
									if ($books && $activity->getBook()) {
										$component = "mod_book";
									}
									else {
										$component = "mod_page";
									}
								}
								break;	
							case "resource":
								if ($activity->getResource() == $olatFile) {
									$fileOK = 1;
									$filesXmlChild = $filesXml->addChild('file');
									$filesXmlChild->addAttribute('id', $fileID);
									$component = "mod_resource";
								}
								break;
						}
						if ($fileOK != 0) {
							$filesXmlChild->addChild('contenthash', $fileSHA1);
							if ($books && $activity->getBook()) {
								$filesXmlChild->addChild('contextid', $activity->getBookContextID());
								$filesXmlChild->addChild('component', $component);
								$filesXmlChild->addChild('filearea', "chapter");
								$filesXmlChild->addChild('itemid', $activity->getChapterID());
							}
							else {
								$filesXmlChild->addChild('contextid', $activity->getContextID());
								$filesXmlChild->addChild('component', $component);
								$filesXmlChild->addChild('filearea', "content");
								$filesXmlChild->addChild('itemid', 0);
							}
							$filesXmlChild->addChild('filepath', "/");
							$filesXmlChild->filename = $olatFile;
							$filesXmlChild->addChild('userid', 2);
							$filesXmlChild->addChild('filesize', filesize($olatFilePath));
							$filesXmlChild->addChild('mimetype', finfo_file(finfo_open(FILEINFO_MIME_TYPE), $olatFilePath));
							$filesXmlChild->addChild('status', 0);
							$filesXmlChild->addChild('timecreated', filectime($olatFilePath));
							$filesXmlChild->addChild('timemodified', filemtime($olatFilePath));
							$filesXmlChild->source = $olatFile;
							$filesXmlChild->addChild('author', "OLAT2Moodle");
							$filesXmlChild->addChild('license', 'allrightsreserved');
							$filesXmlChild->addChild('sortorder', 0);
							$filesXmlChild->addChild('repositorytype', '$@NULL@$');
							$filesXmlChild->addChild('repositoryid', '$@NULL@$');
							$filesXmlChild->addChild('reference', '$@NULL@$');
							$activity->setFile($fileID);
							
							$fileID++;
						}
					}
				}
			}
			else {
				$error->setError(new Error("WARNING", 2, "Couldn't copy file: " . $olatFile, 0));
			}
		}
	}
	
	// The files for the folders and quizzes are located somewhere else,
	// so this is for fetching these files from OLAT.
	$olatExportPathRoot = $olatObject->getRootdir() . "/export";
	$olatExportRootFiles = listFolderFiles($olatExportPathRoot);
	$directoryArray = array();
	foreach ($olatExportRootFiles as $olatExportRootFile) {
		$dir = $olatExportPathRoot . "/" . substr($olatExportRootFile, 0, strpos($olatExportRootFile, DIRECTORY_SEPARATOR));
		$directoryArray[] = $dir;
	}
	
	// Removes all the duplicate files found, which speeds up the process
	$directoryArray = array_unique($directoryArray);
	
	foreach ($directoryArray as $directory) {
		if (is_dir($directory)) {
			$olatExportFiles = listFolderFiles($directory);
			foreach ($olatExportFiles as $olatExportFile) {
				// Ignore the .xml and .zip files
				if (substr($olatExportFile, -3) != "xml" && substr($olatExportFile, -3) != "zip") {
					$fileSHA1 = sha1($olatExportFile);
					$fileSHA1Dir = $filesPath . "/" . substr($fileSHA1, 0, 2);
					if (!file_exists($fileSHA1Dir) && !is_dir($fileSHA1Dir)) {
						mkdir($fileSHA1Dir, 0777, true);
					}		
					foreach ($moodleObject->getSection() as $section) {
						foreach ($section->getActivity() as $activity) {
							$activityModuleName = $activity->getModuleName();
							if ($activityModuleName == "folder" || $activityModuleName == "quiz") {
								if ($activity->getActivityID() == (string) ($activity->getSectionID() - 50000000000000)) {
									$activityID = (string) ($activity->getActivityID() + 50000000000000);
								}
								else {
									$activityID = $activity->getActivityID();
								}
								$olatExportFilePath = $olatExportPathRoot . "/" . $activityID . "/" . $olatExportFile;
								if (file_exists($olatExportFilePath)) {
									if (!is_dir($olatExportFilePath)) {
										if (copy($olatExportFilePath, $fileSHA1Dir . "/" . $fileSHA1)) {
											if ($activityModuleName == "folder") {
												foreach ($activity->getFolderFile() as $folderFile) {
													if ($folderFile->getFileName() == preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR)))) {
														$filesXmlChild = $filesXml->addChild('file');
														$filesXmlChild->addAttribute('id', $fileID);
														$filesXmlChild->addChild('contenthash', $fileSHA1);
														$filesXmlChild->addChild('contextid', $activity->getContextID());
														$activity->setFile($fileID);
														$filesXmlChild->addChild('component', "mod_folder");
														$filesXmlChild->addChild('filearea', "content");
														$filesXmlChild->addChild('itemid', 0);
														$preg = preg_quote('.*' . $activityID . '(.*)[\/]', '/');
														$fpath = str_replace("\\", "/", preg_replace("/$preg/", '$1', $olatExportFile));
														$filePath = substr($fpath, 0, strrpos($fpath, '/'));
														if (!empty($filePath)) {
															$filesXmlChild->filepath = "/" . $filePath . "/";
														}
														else {
															$filesXmlChild->addchild('filepath', "/");
														}
														$filesXmlChild->filename = preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR)));
														$filesXmlChild->addChild('userid', 2);
														$filesXmlChild->addChild('filesize', filesize($olatExportFilePath));
														$filesXmlChild->addChild('mimetype', finfo_file(finfo_open(FILEINFO_MIME_TYPE), $olatExportFilePath));
														$filesXmlChild->addChild('status', 0);
														$filesXmlChild->addChild('timecreated', filectime($olatExportFilePath));
														$filesXmlChild->addChild('timemodified', filemtime($olatExportFilePath));
														$filesXmlChild->source = preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR)));
														$filesXmlChild->addChild('author', "OLAT2Moodle");
														$filesXmlChild->addChild('license', 'allrightsreserved');
														$filesXmlChild->addChild('sortorder', 0);
														$filesXmlChild->addChild('repositorytype', '$@NULL@$');
														$filesXmlChild->addChild('repositoryid', '$@NULL@$');
														$filesXmlChild->addChild('reference', '$@NULL@$');
														
														$fileID++;
													}
												}
											}
											else if ($activityModuleName == "quiz") {
												foreach ($activity->getQuizPages() as $qp) {
													foreach ($qp->getPageQuestions() as $qpq) {
														if (strpos($qpq->getQQuestion(), preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR)))) !== false
																|| strpos(urldecode($qpq->getQQuestion()), preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR)))) !== false) {
															$filesXmlChild = $filesXml->addChild('file');
															$filesXmlChild->addAttribute('id', $fileID);
															$filesXmlChild->addChild('contenthash', $fileSHA1);
															$filesXmlChild->addChild('contextid', $moodleObject->getID());
															$filesXmlChild->addChild('component', "question");
															$filesXmlChild->addChild('filearea', "questiontext");
															$filesXmlChild->addChild('itemid', $qpq->getQID());
															$filesXmlChild->addchild('filepath', "/");
															$filesXmlChild->filename = preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR)));
															$filesXmlChild->addChild('userid', 2);
															$filesXmlChild->addChild('filesize', filesize($olatExportFilePath));
															$filesXmlChild->addChild('mimetype', finfo_file(finfo_open(FILEINFO_MIME_TYPE), $olatExportFilePath));
															$filesXmlChild->addChild('status', 0);
															$filesXmlChild->addChild('timecreated', filectime($olatFilePath));
															$filesXmlChild->addChild('timemodified', filemtime($olatFilePath));
															$filesXmlChild->source = preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR)));
															$filesXmlChild->addChild('author', "OLAT2Moodle");
															$filesXmlChild->addChild('license', 'allrightsreserved');
															$filesXmlChild->addChild('sortorder', 0);
															$filesXmlChild->addChild('repositorytype', '$@NULL@$');
															$filesXmlChild->addChild('repositoryid', '$@NULL@$');
															$filesXmlChild->addChild('reference', '$@NULL@$');
															
															$fileID++;
														}
													}
												}
											}
										}
										else {
											$error->setError(new Error("WARNING", 2, "Couldn't copy file: " . $olatExportFile, 0));
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	$dom->loadXml($filesXml->asXML());
	file_put_contents($path . "/files.xml", $dom->saveXML());
	echo "<p>OK - Files copied</p>";
	
	////////////////////////////////////////////////////////////////////
	// SECTIONS
	
	// Create the /sections folder
	if (!file_exists($path . "/sections") && !is_dir($path . "/sections")) {
		mkdir($path . "/sections", 0777, true);
	}
	
	// This number is for ordening the sections.
	$sectionNumber = 1;
	
	foreach ($moodleObject->getSection() as $section) {
		// Create the section folders in /section
		$sectionPath = $path . "/sections/section_" . $section->getSectionID();
		if (!file_exists($sectionPath) && !is_dir($sectionPath)) {
			mkdir($sectionPath, 0777, true);
		}
		
		// "EMPTY"
		// sections/section_[x]/inforef.xml
		$sectionInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
		
		$dom->loadXML($sectionInforefXml->asXML());
		file_put_contents($sectionPath . "/inforef.xml", $dom->saveXML());
		
		// NOT "EMPTY"
		// sections/section_[x]/section.xml
		$sectionSectionXml = new SimpleXMLElement($header . "<section></section>");
		$sectionSectionXml->addAttribute('id', $section->getSectionID());
		$sectionSectionXml->addChild('number', $section->getNumber());
		$sectionSectionXml->name = $section->getName();
		$sectionSectionXml->addChild('summary');
		$sectionSectionXml->addChild('summaryformat', 1);
		
		$sectionSequence = "";
		foreach ($section->getActivity() as $activity) {
			if ($activity->getSectionID() == $section->getSectionID()) {
				$sectionSequence .= $activity->getModuleID() . ",";
			}
		}
		
		$sectionSectionXml->addChild('sequence', substr($sectionSequence, 0, -1));
		$sectionSectionXml->addChild('visible', 1);
		$sectionSectionXml->addChild('availablefrom', 0);
		$sectionSectionXml->addChild('availableuntil', 0);
		$sectionSectionXml->addChild('showavailability', 0);
		$sectionSectionXml->addChild('groupingid', 0);
		
		$dom->loadXML($sectionSectionXml->asXML());
		file_put_contents($sectionPath . "/section.xml", $dom->saveXML());
		
		// moodle_backup.xml
		moodleBackupSection($moodleBackupXmlContentsSections, $section->getSectionID(), $section->getName());
		moodleBackupSetting($moodleBackupXmlSettings, 'section', $section->getSectionID(), 'section');
		
		$sectionNumber++;
	}
	
	////////////////////////////////////////////////////////////////////
	// ACTIVITIES
	
	// Create the /activities folder
	if (!file_exists($path . "/activities") && !is_dir($path . "/activities")) {
		mkdir($path . "/activities", 0777, true);
	}
	
	foreach ($moodleObject->getSection() as $section) {
		$previousActivity = null;
		// Page numbers (increases by one by every chapter of every single book)
		$pageNum = 1;
		
		// IDs for quiz activities
		$questionInstanceID = 1;
		$feedbackID = 1;
		
		foreach ($section->getActivity() as $activity) {
			// Books are collections of pages, so this is to make sure that all pages
			// that could be bundled in a book will become a book.
			if (!$books) {
				// Create the folder
				$activityPath = $path . "/activities/" . $activity->getModuleName() . "_" . $activity->getModuleID();
				if (!file_exists($activityPath) && !is_dir($activityPath)) {
					mkdir($activityPath, 0777, true);
				}
				
				// "EMPTY"
				// activities/[activity]_[x]/grades.xml
				$activityGradesXml = new SimpleXMLElement($header . "<activity_gradebook></activity_gradebook>");
				$activityGradesXml->addChild('grade_items');
				$activityGradesXml->addChild('grade_letters');
				
				$dom->loadXML($activityGradesXml->asXML());
				file_put_contents($activityPath . "/grades.xml", $dom->saveXML());
				// activities/[activity]_[x]/grading.xml (ONLY WITH ASSIGNMENTS)
				if ($activity->getModuleName() == "assign") {
					$activityGradingXml = new SimpleXMLElement($header . "<areas></areas>");
					$activityGradingArea = $activityGradingXml->addChild('area');
					$activityGradingArea->addAttribute('id', 1);
					$activityGradingArea->addChild('areaname', "submissions");
					$activityGradingArea->addChild('activemethod', "$@NULL@$");
					$activityGradingArea->addChild('definitions');
					
					$dom->loadXML($activityGradingXml->asXML());
					file_put_contents($activityPath . "/grading.xml", $dom->saveXML());		
				}
				
				// activities/[activity]_[x]/roles.xml
				$activityRolesXml = new SimpleXMLElement($header . "<roles></roles>");
				$activityRolesXml->addChild('role_overrides');
				$activityRolesXml->addChild('role_assignments');
				
				$dom->loadXML($activityRolesXml->asXML());
				file_put_contents($activityPath . "/roles.xml", $dom->saveXML());		
				// activities/[activity]_[x]/filters.xml
				$activityFiltersXml = new SimpleXMLElement($header . "<filters></filters>");
				$activityFiltersXml->addChild('filter_actives');
				$activityFiltersXml->addChild('filter_configs');
				
				$dom->loadXML($activityFiltersXml->asXML());
				file_put_contents($activityPath . "/filters.xml", $dom->saveXML());
			
				// NOT "EMPTY"
				// activities/[activity]_[x]/inforef.xml
				$activityInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
				if ($activity->getFile()) {
					$activityInforefXmlFileRef = $activityInforefXml->addChild('fileref');
					foreach ($activity->getFile() as $aFile) {
						$activityInforefXmlFileRefFile = $activityInforefXmlFileRef->addChild('file');
						$activityInforefXmlFileRefFile->addChild('id', $aFile);
					}
				}
				
				if ($activity->getModuleName() == "quiz") {
					$activityInforefXmlQuestionRef = $activityInforefXml->addChild('question_categoryref');
					foreach ($activity->getQuizPages() as $qp) {
						$activityInforefXmlQuestion = $activityInforefXmlQuestionRef->addChild('question_category');
						$activityInforefXmlQuestion->addChild('id', $qp->getPageID());
					}
				}
				
				$dom->loadXML($activityInforefXml->asXML());
				file_put_contents($activityPath . "/inforef.xml", $dom->saveXML());
				
				// activities/[activity]_[x]/module.xml
				$activityModuleXml = new SimpleXMLElement($header . "<module></module>");
				$activityModuleXml->addAttribute('id', $activity->getModuleID());
				$activityModuleXml->addAttribute('version', 2013110500);
				$activityModuleXml->addChild('modulename', $activity->getModuleName());
				$activityModuleXml->addChild('sectionid', $section->getSectionID());
				$activityModuleXml->addChild('idnumber');
				$activityModuleXml->addChild('added', time());
				$activityModuleXml->addChild('indent', $activity->getIndent());
				$activityModuleXml->addChild('visible', 1);
				$activityModuleXml->addChild('visibleold', 1);
				$activityModuleXml->addChild('groupingid', 0);
				$activityModuleXml->addChild('completionexpected', 0);
				
				$dom->loadXML($activityModuleXml->asXML());
				file_put_contents($activityPath . "/module.xml", $dom->saveXML());
				
				// activities/[activity]_[x]/[activity].xml
				$activityActivityXml = new SimpleXMLElement($header . "<activity></activity>");
				noBookAddActivity($activityActivityXml, $activity, $questionInstanceID, $feedbackID);
				
				$dom->loadXML($activityActivityXml->asXML());
				file_put_contents($activityPath . "/" . $activity->getModuleName() . ".xml", $dom->saveXML());
				
				// moodle_backup.xml
				moodleBackupActivity($moodleBackupXmlContentsActivities, $activity->getModuleID(), $activity->getSectionID(), $activity->getModuleName(), $activity->getName());
				moodleBackupSetting($moodleBackupXmlSettings, $activity->getModuleName(), $activity->getModuleID(), 'activity');
			}
			else {
				// Variable that says if we're currently making a book
				$currentlyBook = false;
				// Variable that says if we need to create the starting .xml tags (for books)
				$firstTags = false;
				
				// Create the folder
				// If it's a book, it has to check some things, namely:
				// - Is the page part of a book?
				// - Does the previous activity exist? If not, we can safely assume that this is the start of a book
				// - Is the previous activity already a book? Then we don't need to repeat this.
				if ($activity->getBook()) {
					if (!isset($previousActivity)) {
						$activityPath = $path . "/activities/book_" . $activity->getModuleID();
						$currentlyBook = true;
						$firstTags = true;
					}
					else if (!$previousActivity->getBook()) {
						$activityPath = $path . "/activities/book_" . $activity->getModuleID();
						$currentlyBook = true;
						$firstTags = true;
					}
					else {
						$currentlyBook = true;
						$firstTags = false;
					}
				}
				else {
					$activityPath = $path . "/activities/" . $activity->getModuleName() . "_" . $activity->getModuleID();
					$currentlyBook = false;
					$pageNum = 1;
				}
				if (!file_exists($activityPath) && !is_dir($activityPath)) {
					mkdir($activityPath, 0777, true);
				}
				
				// "EMPTY"
				// activities/[activity]_[x]/grades.xml
				$activityGradesXml = new SimpleXMLElement($header . "<activity_gradebook></activity_gradebook>");
				$activityGradesXml->addChild('grade_items');
				$activityGradesXml->addChild('grade_letters');
				
				$dom->loadXML($activityGradesXml->asXML());
				file_put_contents($activityPath . "/grades.xml", $dom->saveXML());
				// activities/[activity]_[x]/grading.xml (ONLY WITH ASSIGNMENTS)
				if ($activity->getModuleName() == "assign") {
					$activityGradingXml = new SimpleXMLElement($header . "<areas></areas>");
					$activityGradingArea = $activityGradingXml->addChild('area');
					$activityGradingArea->addAttribute('id', 1);
					$activityGradingArea->addChild('areaname', "submissions");
					$activityGradingArea->addChild('activemethod', "$@NULL@$");
					$activityGradingArea->addChild('definitions');
					
					$dom->loadXML($activityGradingXml->asXML());
					file_put_contents($activityPath . "/grading.xml", $dom->saveXML());		
				}
				// activities/[activity]_[x]/roles.xml
				$activityRolesXml = new SimpleXMLElement($header . "<roles></roles>");
				$activityRolesXml->addChild('role_overrides');
				$activityRolesXml->addChild('role_assignments');
				
				$dom->loadXML($activityRolesXml->asXML());
				file_put_contents($activityPath . "/roles.xml", $dom->saveXML());
				// activities/[activity]_[x]/filters.xml
				$activityFiltersXml = new SimpleXMLElement($header . "<filters></filters>");
				$activityFiltersXml->addChild('filter_actives');
				$activityFiltersXml->addChild('filter_configs');
				
				$dom->loadXML($activityFiltersXml->asXML());
				file_put_contents($activityPath . "/filters.xml", $dom->saveXML());
				
				// NOT "EMPTY"
				// activities/[activity]_[x]/inforef.xml
				if ($currentlyBook && $firstTags) {
					$activityInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
					$activityInforefXmlFileRef = $activityInforefXml->addChild('fileref');
					if ($activity->getFile()) {
						foreach ($activity->getFile() as $aFile) {
							$activityInforefXmlFileRefFile = $activityInforefXmlFileRef->addChild('file');
							$activityInforefXmlFileRefFile->addChild('id', $aFile);
						}
					}
				}
				else if ($currentlyBook && !$firstTags) {
					if ($activity->getFile()) {
						foreach ($activity->getFile() as $aFile) {
							$activityInforefXmlFileRefFile = $activityInforefXmlFileRef->addChild('file');
							$activityInforefXmlFileRefFile->addChild('id', $aFile);
						}
					}
				}
				else if (!$currentlyBook) {
					$activityInforefXml = new SimpleXMLElement($header . "<inforef></inforef>");
					if ($activity->getFile()) {
						$activityInforefXmlFileRef = $activityInforefXml->addChild('fileref');
						foreach ($activity->getFile() as $aFile) {
							$activityInforefXmlFileRefFile = $activityInforefXmlFileRef->addChild('file');
							$activityInforefXmlFileRefFile->addChild('id', $aFile);
						}
					}
				}
				
				if ($activity->getModuleName() == "quiz") {
					$activityInforefXmlQuestionRef = $activityInforefXml->addChild('question_categoryref');
					foreach ($activity->getQuizPages() as $qp) {
						$activityInforefXmlQuestion = $activityInforefXmlQuestionRef->addChild('question_category');
						$activityInforefXmlQuestion->addChild('id', $qp->getPageID());
					}
				}
				
				$dom->loadXML($activityInforefXml->asXML());
				file_put_contents($activityPath . "/inforef.xml", $dom->saveXML());
				
				// activities/[activity]_[x]/module.xml
				if ($currentlyBook && $firstTags) {
					$activityModuleXml = new SimpleXMLElement($header . "<module></module>");
					$activityModuleXml->addAttribute('id', $activity->getModuleID());
					$activityModuleXml->addAttribute('version', 2013110500);
					$activityModuleXml->addChild('modulename', "book");
					$activityModuleXml->addChild('sectionid', $section->getSectionID());
					$activityModuleXml->addChild('idnumber');
					$activityModuleXml->addChild('added', time());
					$activityModuleXml->addChild('indent', $activity->getIndent());
					$activityModuleXml->addChild('visible', 1);
					$activityModuleXml->addChild('visibleold', 1);
					$activityModuleXml->addChild('groupingid', 0);
					$activityModuleXml->addChild('completionexpected', 0);
				}
				else if (!$currentlyBook) {
					$activityModuleXml = new SimpleXMLElement($header . "<module></module>");
					$activityModuleXml->addAttribute('id', $activity->getModuleID());
					$activityModuleXml->addAttribute('version', 2013110500);
					$activityModuleXml->addChild('modulename', $activity->getModuleName());
					$activityModuleXml->addChild('sectionid', $section->getSectionID());
					$activityModuleXml->addChild('idnumber');
					$activityModuleXml->addChild('added', time());
					$activityModuleXml->addChild('indent', $activity->getIndent());
					$activityModuleXml->addChild('visible', 1);
					$activityModuleXml->addChild('visibleold', 1);
					$activityModuleXml->addChild('groupingid', 0);
					$activityModuleXml->addChild('completionexpected', 0);
				}
				
				$dom->loadXML($activityModuleXml->asXML());
				file_put_contents($activityPath . "/module.xml", $dom->saveXML());
				
				// activities/[activity]_[x]/[activity].xml		
				if ($currentlyBook && $firstTags) {
					$activityActivityXml = new SimpleXMLElement($header . "<activity></activity>");
					$activityActivityXml->addAttribute('id', $activity->getActivityID());
					$activityActivityXml->addAttribute('moduleid', $activity->getModuleID());
					$activityActivityXml->addAttribute('modulename', "book");
					$activityActivityXml->addAttribute('contextid', $activity->getContextID());
					$activityActivityChildXml = $activityActivityXml->addChild('book');
					$activityActivityChildXml->addAttribute('id', $activity->getActivityID());
					$name = createBookName($section, $activity);
					$activityActivityChildXml->name = $name;
					$activityActivityChildXml->intro = $name;
					$activityActivityChildXml->addChild('introformat', 1);
					$activityActivityChildXml->addChild('numbering', $chapterFormat);
					$activityActivityChildXml->addChild('customtitles', 1);
					$activityActivityChildXml->addChild('timecreated', time());
					$activityActivityChildXml->addChild('timemodified', time());
					$activityBookChapters = $activityActivityChildXml->addChild('chapters');
					$activityBookChapter = $activityBookChapters->addChild('chapter');
					$activityBookChapter->addAttribute('id', $activity->getChapterID());
					$activityBookChapter->addChild('pagenum', $pageNum);
					$pageNum++;
					if ($activity->getBookSubChapter()) {
						$activityBookChapter->addChild('subchapter', 1);
					}
					else {
						$activityBookChapter->addChild('subchapter', 0);
					}
					$activityBookChapter->title = $activity->getName();
					$activityBookChapter->addChild('content', $activity->getContent());
					$activityBookChapter->addChild('contentformat', 1);
					$activityBookChapter->addChild('hidden', 0);
					$activityBookChapter->addChild('timemodified', time());
					$activityBookChapter->addChild('importsrc');
				}
				else if ($currentlyBook && !$firstTags) {
					$activityBookChapter = $activityBookChapters->addChild('chapter');
					$activityBookChapter->addAttribute('id', $activity->getChapterID());
					$activityBookChapter->addChild('pagenum', $pageNum);
					$pageNum++;
					if ($activity->getBookSubChapter()) {
						$activityBookChapter->addChild('subchapter', 1);
					}
					else {
						$activityBookChapter->addChild('subchapter', 0);
					}
					$activityBookChapter->title = $activity->getName();
					$activityBookChapter->addChild('content', $activity->getContent());
					$activityBookChapter->addChild('contentformat', 1);
					$activityBookChapter->addChild('hidden', 0);
					$activityBookChapter->addChild('timemodified', time());
					$activityBookChapter->addChild('importsrc');
				}
				else if (!$currentlyBook) {
					$activityActivityXml = new SimpleXMLElement($header . "<activity></activity>");
					noBookAddActivity($activityActivityXml, $activity, $questionInstanceID, $feedbackID);
				}
				
				$dom->loadXML($activityActivityXml->asXML());
				
				if ($currentlyBook) {
					file_put_contents($activityPath . "/book.xml", $dom->saveXML());
				}
				else {
					file_put_contents($activityPath . "/" . $activity->getModuleName() . ".xml", $dom->saveXML());
				}

				// moodle_backup.xml
				if ($currentlyBook && $firstTags) {			
					moodleBackupActivity($moodleBackupXmlContentsActivities, $activity->getModuleID(), $activity->getSectionID(), 'book', $name, 'activities/book_' . $activity->getModuleID());		
					moodleBackupSetting($moodleBackupXmlSettings, 'book', $activity->getModuleID(), 'activity');
				}
				else if (!$currentlyBook) {
					moodleBackupActivity($moodleBackupXmlContentsActivities, $activity->getModuleID(), $activity->getSectionID(), $activity->getModuleName(), $activity->getName());
					moodleBackupSetting($moodleBackupXmlSettings, $activity->getModuleName(), $activity->getModuleID(), 'activity');
				}
				
				// Set the previous activity
				$previousActivity = $activity;
			}
		}
	}
	
	////////////////////////////////////////////////////////////////////
	// ROOT FILES
	
	// "EMPTY"
	// completion.xml
	$completionXml = new SimpleXMLElement($header . "<course_completion></course_completion>");
	
	$dom->loadXML($completionXml->asXML());
	file_put_contents($path . "/completion.xml", $dom->saveXML());
	// gradebook.xml
	$gradebookXml = new SimpleXMLElement($header . "<gradebook></gradebook>");
	$gradebookXml->addChild('grade_categories');
	$gradebookXml->addChild('grade_items');
	$gradebookXml->addChild('grade_letters');
	$gradebookXml->addChild('grade_settings');
	
	$dom->loadXML($gradebookXml->asXML());
	file_put_contents($path . "/gradebook.xml", $dom->saveXML());
	// groups.xml
	$groupsXml = new SimpleXMLElement($header . "<groups></groups>");
	
	$dom->loadXML($groupsXml->asXML());
	file_put_contents($path . "/groups.xml", $dom->saveXML());
	// moodle_backup.log
	file_put_contents($path . "/moodle_backup.log", "");
	// outcomes.xml
	$outcomesXml = new SimpleXMLElement($header . "<outcomes_definition></outcomes_definition>");
	
	$dom->loadXML($outcomesXml->asXML());
	file_put_contents($path . "/outcomes.xml", $dom->saveXML());

	// roles.xml
	$rolesXml = new SimpleXMLElement($header . "<roles_definition></roles_definition>");
	
	$dom->loadXML($rolesXml->asXML());
	file_put_contents($path . "/roles.xml", $dom->saveXML());
	// scales.xml
	$scalesXml = new SimpleXMLElement($header . "<scales_definition></scales_definition>");
	
	$dom->loadXML($scalesXml->asXML());
	file_put_contents($path . "/scales.xml", $dom->saveXML());
	
	// NOT "EMPTY"
	// moodle_backup.xml
	$dom->loadXML($moodleBackupXmlStart->asXML());
	file_put_contents($path . "/moodle_backup.xml", $dom->saveXML());
	
	// .MBZ
	// Creates the .zip file with all the Moodle backup contents
	
	try {
		$zipPath = $path . ".zip";
		App_File_Zip::CreateFromFilesystem($path, $zipPath);
		echo "<p>OK - .zip created</p>";
	}
	catch (App_File_Zip_Exception $e) {
		$error->setError(new Error("ERROR", 2, "ZIP failed to create: " . $e, 0));
		return null;
	}
	
	// Renames the .zip to .mbz (.mbz is just a renamed .zip anyway)
	if (rename($zipPath, $path . ".mbz")) {
		echo "<p>OK - .zip renamed to .mbz</p>";
	}
	else {
		$error->setError(new Error("ERROR", 2, "ZIP failed to rename." . $e, 0));
		return null;
	}
	
	$moodleDownload = "/tmp/" . clean($moodleObject->getFullName()) . ".mbz";
	
	if (rename(getcwd() . "/tmp/" . $num . ".mbz", getcwd() . $moodleDownload)) {
		echo "<p>OK - Course name given to .mbz file</p>";
	}
	else {
		$error->setError(new Error("ERROR", 2, "MBZ failed to rename." . $e, 0));
		return null;
	}

	// Remove both the OLAT and Moodle temporary directory
	rrmdir($path);
	echo "<p>OK - OLAT temp folder removed</p>";
	rrmdir($olatObject->getRootDir());
	echo "<p>OK - Moodle temp folder removed</p>";
	
	return $moodleDownload;
}

// When there is a multianswer question, every answer is
// made as a new question. This function adds these questions.
//
// PARAMETERS
// ->  $questions = The XML questions part of the question category
//           $qpq = The OLAT QuizQuestion object
// $multiAnswerID = The current multiAnswer ID
// $shortAnswerID = The current shortAnswer ID
function questionBankMultiAnswer(&$questions, $qpq, &$multiAnswerID, &$shortAnswerID, &$questionID, &$answerID) {
	$questionCategoryQuestion = $questions->addChild('question');
	$questionCategoryQuestion->addAttribute('id', $questionID);
	$qpq->setQID($questionID);
	$parentID = $questionID;
	$questionCategoryQuestion->addChild('parent', 0);
	$questionCategoryQuestion->name = $qpq->getQTitle();
	$questionCategoryQuestion->addChild('questiontext', $qpq->getQQuestion());
	$questionCategoryQuestion->addChild('questiontextformat', 1);
	$questionCategoryQuestion->addChild('generalfeedback');
	$questionCategoryQuestion->addChild('generalfeedbackformat', 1);
	$questionCategoryQuestion->addChild('defaultmark', (string) count($qpq->getQPossibilities()) . ".0000000");
	$questionCategoryQuestion->addChild('penalty', "0.3333333");
	$questionCategoryQuestion->addChild('qtype', "multianswer");
	$questionCategoryQuestion->addChild('length', 1);
	$questionCategoryQuestion->addChild('stamp', 0);
	$questionCategoryQuestion->addChild('version', 0);
	$questionCategoryQuestion->addChild('hidden', 0);
	$questionCategoryQuestion->addChild('timecreated', time());
	$questionCategoryQuestion->addChild('timemodified', time());
	$questionCategoryQuestion->addChild('createdby', 2);
	$questionCategoryQuestion->addChild('modifiedby', 2);
	$questionCategoryQuestionPlugin = $questionCategoryQuestion->addChild('plugin_qtype_multianswer_question');
	$questionCategoryQuestionPlugin->addChild('answers');
	$questionCategoryQuestionMultiAnswer = $questionCategoryQuestionPlugin->addChild("multianswer");
	$questionCategoryQuestionMultiAnswer->addAttribute('id', $multiAnswerID);
	$multiAnswerID++;
	$questionCategoryQuestionMultiAnswer->addChild('question', $questionID);
	$questionID++;
	$questionCategoryQuestion->addChild('question_hints', $qpq->getQHint());
	$questionCategoryQuestion->addChild('tags');

	$sequence = "";
	foreach ($qpq->getQPossibilities() as $qpqp) {
		$questionCategoryQuestion = $questions->addChild('question');
		$questionCategoryQuestion->addAttribute('id', $questionID);
		$sequence .= $questionID . ",";
		$questionID++;
		$questionCategoryQuestion->addChild('parent', $parentID);
		$questionCategoryQuestion->name = $qpq->getQTitle();
		$questionCategoryQuestion->addChild('questiontext', "{1:SHORTANSWER:=" . $qpqp->getQPAnswer() . "}");
		$questionCategoryQuestion->addChild('questiontextformat', 1);
		$questionCategoryQuestion->addChild('generalfeedback');
		$questionCategoryQuestion->addChild('generalfeedbackformat', 1);
		$questionCategoryQuestion->addChild('defaultmark', "1.0000000");
		$questionCategoryQuestion->addChild('penalty', "0.0000000");
		$questionCategoryQuestion->addChild('qtype', "shortanswer");
		$questionCategoryQuestion->addChild('length', 1);
		$questionCategoryQuestion->addChild('stamp', 0);
		$questionCategoryQuestion->addChild('version', 0);
		$questionCategoryQuestion->addChild('hidden', 0);
		$questionCategoryQuestion->addChild('timecreated', time());
		$questionCategoryQuestion->addChild('timemodified', time());
		$questionCategoryQuestion->addChild('createdby', 2);
		$questionCategoryQuestion->addChild('modifiedby', 2);
		$questionCategoryQuestionPlugin = $questionCategoryQuestion->addChild('plugin_qtype_shortanswer_question');
		$questionCategoryQuestionAnswers = $questionCategoryQuestionPlugin->addChild('answers');
		$questionCategoryQuestionAnswer = $questionCategoryQuestionAnswers->addChild('answer');
		$questionCategoryQuestionAnswer->addAttribute('id', $answerID);
		$answerID++;
		$questionCategoryQuestionAnswer->addChild('answertext', $qpqp->getQPAnswer());
		$questionCategoryQuestionAnswer->addChild('answerformat', 1);
		$questionCategoryQuestionAnswer->addChild('fraction', "1.0000000");
		if (!is_null($qpqp->getQPFeedback())) {
			$questionCategoryQuestionAnswer->addChild('feedback', $qpqp->getQPFeedback());
		}
		else {
			$questionCategoryQuestionAnswer->addChild('feedback');
		}
		$questionCategoryQuestionAnswer->addChild('feedbackformat', 1);
		$questionCategoryQuestionShortAnswer = $questionCategoryQuestionPlugin->addChild("shortanswer");
		$questionCategoryQuestionShortAnswer->addAttribute('id', $shortAnswerID);
		$shortAnswerID++;
		$questionCategoryQuestionShortAnswer->addChild('usecase', 0);	
		$questionCategoryQuestion->addChild('question_hints');
		$questionCategoryQuestion->addChild('tags');
	}
	$questionCategoryQuestionMultiAnswer->addChild('sequence', substr($sequence, 0, -1));
}

// Default settings function for moodle_backup.xml
// (This shortens the code by quite a bit)
//
// PARAMETERS
// -> $settings = The <settings> part of the moodle_backup.xml SimpleXMLElement
//       $level = The <level> element
//        $name = The <name> element
//       $value = the <value> element
function moodleBackupDefaultSettings(&$settings, $level, $name, $value) {
	$moodleBackupXmlSettingsSetting = $settings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', $level);
	$moodleBackupXmlSettingsSetting->addChild('name', $name);
	$moodleBackupXmlSettingsSetting->addChild('value', $value);
}

// This function is for adding settings to moodle_backup.xml
//
// PARAMETERS
// -> $settings = The <settings> part of the moodle_backup.xml SimpleXMLElement
//        $name = Name of the module (section, page, quiz, ...)
//          $id = The ID of said module
//          $sa = 'section' or 'activity'
function moodleBackupSetting(&$settings, $name, $id, $sa) {
	$moodleBackupXmlSettingsSetting = $settings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', $sa);
	$moodleBackupXmlSettingsSetting->addChild($sa, $name . "_" . $id);
	$moodleBackupXmlSettingsSetting->addChild('name', $name . "_" . $id . "_included");
	$moodleBackupXmlSettingsSetting->addChild('value', 1);
	$moodleBackupXmlSettingsSetting = $settings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', $sa);
	$moodleBackupXmlSettingsSetting->addChild($sa, $name . "_" . $id);
	$moodleBackupXmlSettingsSetting->addChild('name', $name . "_" . $id . "_userinfo");
	$moodleBackupXmlSettingsSetting->addChild('value', 0);
}

// This function is for adding sections to moodle_backup.xml
//
// PARAMETERS
// -> $xmlSections = The <sections> part of the moodle_backup.xml SimpleXMLElement
//      $sectionID = The section's ID
//          $title = The section's title
function moodleBackupSection(&$xmlSections, $sectionID, $title) {
	$moodleBackupXmlContentsSectionsSection = $xmlSections->addChild('section');
	$moodleBackupXmlContentsSectionsSection->addChild('sectionid', $sectionID);
	$moodleBackupXmlContentsSectionsSection->title = $title;
	$moodleBackupXmlContentsSectionsSection->addChild('directory', "sections/section_" . $sectionID);
}

// This function is for adding activities to moodle_backup.xml
//
// PARAMETERS
// -> $xmlActivities = The <activities> part of the moodle_backup.xml SimpleXMLElement
//         $moduleID = The module's ID
//        $sectionID = The section's ID
//       $moduleName = The module's name (page, quiz, ...)
//            $title = The module's title
function moodleBackupActivity(&$xmlActivities, $moduleID, $sectionID, $moduleName, $title) {
	$moodleBackupXmlContentsActivitiesActivity = $xmlActivities->addChild('activity');
	$moodleBackupXmlContentsActivitiesActivity->addChild('moduleid', $moduleID);
	$moodleBackupXmlContentsActivitiesActivity->addChild('sectionid', $sectionID);
	$moodleBackupXmlContentsActivitiesActivity->addChild('modulename', $moduleName);
	$moodleBackupXmlContentsActivitiesActivity->title = $title;
	$moodleBackupXmlContentsActivitiesActivity->addChild('directory', "activities/" . $moduleName . "_" . $moduleID);
}

// If questions are randomized, they are added to the question bank as
// questions of their own. This function adds these questions.
//
// PARAMETERS
// ->  $questions = The XML questions part of the question category
//           $qpq = The OLAT QuizQuestion object
//          $name = Name of the current question bank
function questionBankRandom(&$questions, $qp, $name) {
	if ($qp->getPageOrdering() == "Random") {
		foreach ($qp->getRandomQuestionIDs() as $qpr) {
			$questionCategoryQuestion = $questions->addChild('question');
			$questionCategoryQuestion->addAttribute('id', $qpr);
			$questionCategoryQuestion->addChild('parent', $qpr);
			$questionCategoryQuestion->name = "Random (" . $name . ")";
			$questionCategoryQuestion->addChild('questiontext');
			$questionCategoryQuestion->addChild('questiontextformat', 0);
			$questionCategoryQuestion->addChild('generalfeedback');
			$questionCategoryQuestion->addChild('generalfeedbackformat', 0);
			$questionCategoryQuestion->addChild('defaultmark', 1);
			$questionCategoryQuestion->addChild('penalty', "0.0000000");
			$questionCategoryQuestion->addChild('qtype', "random");
			$questionCategoryQuestion->addChild('length', 1);
			$questionCategoryQuestion->addChild('stamp', 0);
			$questionCategoryQuestion->addChild('version', 0);
			$questionCategoryQuestion->addChild('hidden', 0);
			$questionCategoryQuestion->addChild('timecreated', time());
			$questionCategoryQuestion->addChild('timemodified', time());
			$questionCategoryQuestion->addChild('createdby', 2);
			$questionCategoryQuestion->addChild('modifiedby', 2);
			$questionCategoryQuestion->addChild('question_hints');
			$questionCategoryQuestion->addChild('tags');
		}
	}
}

// This function creates names for the books, basically it will be something like
// [first chapter in book] - [last chapter in book].
//
// PARAMETERS
// ->  $section = The current section
//    $activity = The current activity (the first chapter of the book)
function createBookName($section, $activity) {
	$bookName = "";
	$bookName1 = $activity->getName() . " - ";
	$sectionActivities = $section->getActivity();
	$currentActivity = array_search($activity, $sectionActivities);
	for ($i = $currentActivity + 1; $i < count($sectionActivities); $i++) {
		$a = $sectionActivities[$i];
		if ($a->getBook()) {
			$bookName2 = $a->getName();
			if ($i == count($sectionActivities) - 1) {
				break;
			}
		}
		else {
			break;
		}
	}
	$bookName = $bookName1 . $bookName2;
	return $bookName;
}

// This function is for creating the activity.xml file when the 'books'
// option isn't checked, since it gets used twice in the code and it's kind of
// extensive, it will make the code cleaner to separate it in a function
//
// PARAMETERS
// -> $activityActivityXml = The SimpleXMLFile for activity.xml
//               $activity = The current activity
//     $questionInstanceID = The current questionInstance ID
//             $feedbackID = The current feedback ID

function noBookAddActivity(&$activityActivityXml, $activity, &$questionInstanceID, &$feedbackID) {
	$activityActivityXml->addAttribute('id', $activity->getActivityID());
	$activityActivityXml->addAttribute('moduleid', $activity->getModuleID());
	$activityActivityXml->addAttribute('modulename', $activity->getModuleName());
	$activityActivityXml->addAttribute('contextid', $activity->getContextID());
	$activityActivityChildXml = $activityActivityXml->addChild($activity->getModuleName());
	$activityActivityChildXml->addAttribute('id', $activity->getActivityID());
	$activityActivityChildXml->name = $activity->getName();
	if ($activity->getModuleName() == "quiz") {
		$activityActivityChildXml->intro = $activity->getDescription();
	}
	else if ($activity->getModuleName() == "assign" && $activity->getAssignmentText() != "") {
		$activityActivityChildXml->intro = $activity->getAssignmentText();
	}
	else {
		$activityActivityChildXml->intro = $activity->getName();
	}
	$activityActivityChildXml->addChild('introformat', 1);
	
	switch ($activity->getModuleName()) {
		case "page":
			$activityActivityChildXml->addChild('display', 5);
			$activityActivityChildXml->addChild('content', $activity->getContent());
			$activityActivityChildXml->addChild('contentformat', 1);
			$activityActivityChildXml->addChild('legacyfiles', 0);
			$activityActivityChildXml->addChild('legacyfileslast', "$@NULL@$");
			$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";s:1:"0";}');
			$activityActivityChildXml->addChild('revision', 1);
			$activityActivityChildXml->addChild('timemodified', time());
			break;
		
		case "folder":
			$activityActivityChildXml->addChild('display', 0);
			$activityActivityChildXml->addChild('showexpanded', 1);
			$activityActivityChildXml->addChild('revision', 1);
			$activityActivityChildXml->addChild('timemodified', time());
			break;
			
		case "url":
			$activityActivityChildXml->addChild('display', 0);
			$activityActivityChildXml->externalurl = $activity->getURL();
			$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";s:1:"0";}');
			$activityActivityChildXml->addChild('parameters', 'a:0:{}');
			$activityActivityChildXml->addChild('timemodified', time());
			break;
			
		case "resource":
			$activityActivityChildXml->addChild('display', 0);
			$activityActivityChildXml->addChild('tobemigrated', 0);
			$activityActivityChildXml->addChild('legacyfiles', 0);
			$activityActivityChildXml->addChild('legacyfileslast', "$@NULL@$");
			$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";i:1;}');
			$activityActivityChildXml->addChild('revision', 1);
			$activityActivityChildXml->addChild('timemodified', time());
			break;
		
		case "wiki":
			$activityActivityChildXml->firstpagetitle = $activity->getName();
			$activityActivityChildXml->addChild('wikimode', 'collaborative');
			$activityActivityChildXml->addChild('defaultformat', 'html');
			$activityActivityChildXml->addChild('forceformat', 0);
			$activityActivityChildXml->addChild('editbegin', 0);
			$activityActivityChildXml->addChild('editend', 0);
			$activityActivityChildXml->addChild('subwikis');
			$activityActivityChildXml->addChild('timemodified', time());
			break;
		
		case "assign":
			$activityActivityChildXml->addChild('alwaysshowdescription', 0);
			$activityActivityChildXml->addChild('submissiondrafts', 0);
			$activityActivityChildXml->addChild('sendnotifications', 0);
			$activityActivityChildXml->addChild('sendlatenotifications', 0);
			$activityActivityChildXml->addChild('duedate', 0);
			$activityActivityChildXml->addChild('cutoffdate', 0);
			$activityActivityChildXml->addChild('allowsubmissionsfromdate', 0);
			$activityActivityChildXml->addChild('grade', 100);
			$activityActivityChildXml->addChild('timemodified', time());
			$activityActivityChildXml->addChild('completionsubmit', 0);
			$activityActivityChildXml->addChild('requiresubmissionstatement', 0);
			$activityActivityChildXml->addChild('teamsubmission', 0);
			$activityActivityChildXml->addChild('requireallteammemberssubmit', 0);
			$activityActivityChildXml->addChild('teamsubmissiongroupingid', 0);
			$activityActivityChildXml->addChild('blindmarking', 0);
			$activityActivityChildXml->addChild('revealidentities', 0);
			$activityActivityChildXml->addChild('attemptreopenmethod', "none");
			$activityActivityChildXml->addChild('maxattempts', "-1");
			$activityActivityChildXml->addChild('markingworkflow', 0);
			$activityActivityChildXml->addChild('markingallocation', 0);
			$activityActivityChildXml->addChild('userflags');
			$activityActivityChildXml->addChild('submissions');
			$activityActivityChildXml->addChild('grades');
			$pluginID = 1;
			$activityActivityChildPluginConfigs = $activityActivityChildXml->addChild('plugin_configs');
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "onlinetext");
			$activityActivityChildPlugin->addChild('subtype', "assignsubmission");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 1);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "file");
			$activityActivityChildPlugin->addChild('subtype', "assignsubmission");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 1);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "file");
			$activityActivityChildPlugin->addChild('subtype', "maxfilesubmissions");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 1);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "file");
			$activityActivityChildPlugin->addChild('subtype', "assignsubmission");
			$activityActivityChildPlugin->addChild('name', "maxxubmissionsizebytes");
			$activityActivityChildPlugin->addChild('value', 0);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "comments");
			$activityActivityChildPlugin->addChild('subtype', "assignsubmission");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 1);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "comments");
			$activityActivityChildPlugin->addChild('subtype', "assignfeedback");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 1);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "editpdf");
			$activityActivityChildPlugin->addChild('subtype', "assignfeedback");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 0);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "offline");
			$activityActivityChildPlugin->addChild('subtype', "assignfeedback");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 0);
			$activityActivityChildPlugin = $activityActivityChildPluginConfigs->addChild('plugin_config');
			$activityActivityChildPlugin->addAttribute('id', $pluginID);
			$pluginID++;
			$activityActivityChildPlugin->addChild('plugin', "file");
			$activityActivityChildPlugin->addChild('subtype', "assignsfeedback");
			$activityActivityChildPlugin->addChild('name', "enabled");
			$activityActivityChildPlugin->addChild('value', 0);
			break;
		
		case "quiz":
			$activityActivityChildXml->addChild('timeopen', 0);
			$activityActivityChildXml->addChild('timeclose', 0);
			$timelimit = $activity->getDuration();
			if (!is_null($timelimit) && !empty($timelimit)) {
				$activityActivityChildXml->addChild('timelimit', $timelimit);
			}
			else {
				$activityActivityChildXml->addChild('timelimit', 0);
			}
			$activityActivityChildXml->addChild('overduehandling', 'autoabandon');
			$activityActivityChildXml->addChild('graceperiod', 0);
			$activityActivityChildXml->addChild('preferredbehaviour', 'deferredfeedback');
			$activityActivityChildXml->addChild('attempts_number', 0);
			$activityActivityChildXml->addChild('attemptonlast', 0);
			$activityActivityChildXml->addChild('grademethod', 1);
			$activityActivityChildXml->addChild('decimalpoints', 2);
			$activityActivityChildXml->addChild('questiondecimalpoints', -1);
			$activityActivityChildXml->addChild('reviewattempt', 69904);
			$activityActivityChildXml->addChild('reviewcorrectness', 4368);
			$activityActivityChildXml->addChild('reviewmarks', 4368);
			$activityActivityChildXml->addChild('reviewspecificfeedback', 4368);
			$activityActivityChildXml->addChild('reviewgeneralfeedback', 4368);
			$activityActivityChildXml->addChild('reviewrightanswer', 4368);
			$activityActivityChildXml->addChild('reviewoverallfeedback', 4368);
			$activityActivityChildXml->addChild('questionsperpage', 1);
			$activityActivityChildXml->addChild('navmethod', 'free');
			$activityActivityChildXml->addChild('shufflequestions', 0);
			$activityActivityChildXml->addChild('shuffleanswers', 1);
			$questions = "";
			$sumgrades = 0;
			foreach ($activity->getQuizPages() as $qp) {
				if ($qp->getPageOrdering() == "Random") {
					foreach ($qp->getRandomQuestionIDs() as $qpr) {
						$questions .= $qpr . ",";
						if ($activity->getClustering() == "itemPage") {
							$questions .= "0,";
						}
						$sumgrades++;
					}
				}
				else {
					foreach ($qp->getPageQuestions() as $qpq) {
						$questions .= $qpq->getQID() . ",";
						if ($activity->getClustering() == "itemPage") {
							$questions .= "0,";
						}
						$sumgrades++;
					}
				}
				if ($activity->getClustering() != "itemPage") {
					$questions .= "0,";
				}
			}
			$activityActivityChildXml->addChild('questions', substr($questions, 0, -1));
			$activityActivityChildXml->addChild('sumgrades', $sumgrades . ".00000");
			if (!is_null($activity->getPassingScore()) && $activity->getPassingScore() != "0.0") {
				$activityActivityChildXml->addChild('grade', $activity->getPassingScore());
			}
			else {
				$activityActivityChildXml->addChild('grade', $sumgrades . ".00000");
			}
			$activityActivityChildXml->addChild('timecreated', time());
			$activityActivityChildXml->addChild('timemodified', time());
			$activityActivityChildXml->addChild('password');
			$activityActivityChildXml->addChild('subnet');
			$activityActivityChildXml->addChild('browsersecurity', "-");
			$activityActivityChildXml->addChild('delay1', 0);
			$activityActivityChildXml->addChild('delay2', 0);
			$activityActivityChildXml->addChild('showuserpicture', 0);
			$activityActivityChildXml->addChild('showblocks', 0);
			$activityActivityChildXmlQI = $activityActivityChildXml->addChild('question_instances');
			foreach ($activity->getQuizPages() as $qp) {
				if ($qp->getPageOrdering() == "Random") {
					foreach ($qp->getRandomQuestionIDs() as $qpr) {
						$activityActivityChildXmlQII = $activityActivityChildXmlQI->addChild('question_instance');
						$activityActivityChildXmlQII->addAttribute('id', $questionInstanceID);
						$questionInstanceID++;
						$activityActivityChildXmlQII->addChild('question', $qpr);
						$activityActivityChildXmlQII->addChild('grade', "1.0000000");
					}
				}
				else {
					foreach ($qp->getPageQuestions() as $qpq) {
						$activityActivityChildXmlQII = $activityActivityChildXmlQI->addChild('question_instance');
						$activityActivityChildXmlQII->addAttribute('id', $questionInstanceID);
						$questionInstanceID++;
						$activityActivityChildXmlQII->addChild('question', $qpq->getQID());
						$activityActivityChildXmlQII->addChild('grade', "1.0000000");
					}
				}
			}
			$activityActivityChildFeedbacksXml = $activityActivityChildXml->addChild('feedbacks');
			$activityActivityChildFeedbacksFeedbackXml = $activityActivityChildXml->addChild('feedback');
			$activityActivityChildFeedbacksFeedbackXml->addAttribute('id', $feedbackID);
			$feedbackID++;
			$activityActivityChildFeedbacksFeedbackXml->addChild('feedbacktext');
			$activityActivityChildFeedbacksFeedbackXml->addChild('feedbacktextformat', 1);
			$activityActivityChildFeedbacksFeedbackXml->addChild('mingrade', '0.00000');
			$activityActivityChildFeedbacksFeedbackXml->addChild('maxgrade', '11.00000');
			$activityActivityChildXml->addChild('overrides');
			$activityActivityChildXml->addChild('grades');
			$activityActivityChildXml->addChild('attempts');
			break;
	}					
}

// When a HTML page gets referenced to in a course, this HTML page could
// contain media files of its own, this function makes sure that these
// files also get migrated
//
// PARAMETERS
// ->         $filesPath = The path to /files (Moodle)
//                 $path = The path to the referenced HTML file
//     $coursefolderPath = The path to the current coursefolder
//             $filesXml = The current files.xml SimpleXMLElement
//               $fileID = The current fileID
//             $activity = The activity where the HTML file gets referenced to
//                $books = The books checkbox
function findEmbeddedFiles($filesPath, $path, $coursefolderPath, &$filesXml, &$fileID, &$activity, $books) {
	$embeddedFiles = array();
	
	$dom = new DOMDocument;
	$errorState = libxml_use_internal_errors(TRUE);
	$dom->loadHTMLFile($path);
	$errors = libxml_get_errors();

	// Find all image references in embedded HTML files
	foreach ($dom->getElementsByTagName('img') as $inode) {
		$srcValue = $inode->getAttribute('src');
		if (substr($srcValue, 0, 7)  !== "http://" 
		 && substr($srcValue, 0, 8)  !== "https://") {
			array_push($embeddedFiles, $srcValue);
		}
	}
	
	foreach ($embeddedFiles as $embeddedFile) {
		$embeddedFilePath = $coursefolderPath . "/" . $embeddedFile;
		$fileSHA1 = sha1($embeddedFile);
		$fileSHA1Dir = $filesPath . "/" . substr($fileSHA1, 0, 2);
		if (!file_exists($fileSHA1Dir) && !is_dir($fileSHA1Dir)) {
			mkdir($fileSHA1Dir, 0777, true);
		}
		if (!is_dir($embeddedFilePath)) {
			if (copy($embeddedFilePath, $fileSHA1Dir . "/" . $fileSHA1)) {
				$filesXmlChild = $filesXml->addChild('file');
				$filesXmlChild->addAttribute('id', $fileID);
				if ($books && $activity->getBook()) {
					$component = "mod_book";
				}
				else {
					$component = "mod_page";
				}
				$filesXmlChild->addChild('contenthash', $fileSHA1);
				if ($books && $activity->getBook()) {
					$filesXmlChild->addChild('contextid', $activity->getBookContextID());
					$filesXmlChild->addChild('component', $component);
					$filesXmlChild->addChild('filearea', "chapter");
					$filesXmlChild->addChild('itemid', $activity->getChapterID());
				}
				else {
					$filesXmlChild->addChild('contextid', $activity->getContextID());
					$filesXmlChild->addChild('component', $component);
					$filesXmlChild->addChild('filearea', "content");
					$filesXmlChild->addChild('itemid', 0);
				}
				$filesXmlChild->addChild('filepath', "/");
				$filesXmlChild->filename = $embeddedFile;
				$filesXmlChild->addChild('userid', 2);
				$filesXmlChild->addChild('filesize', filesize($embeddedFilePath));
				$filesXmlChild->addChild('mimetype', finfo_file(finfo_open(FILEINFO_MIME_TYPE), $embeddedFilePath));
				$filesXmlChild->addChild('status', 0);
				$filesXmlChild->addChild('timecreated', filectime($embeddedFilePath));
				$filesXmlChild->addChild('timemodified', filemtime($embeddedFilePath));
				$filesXmlChild->source = $embeddedFile;
				$filesXmlChild->addChild('author', "OLAT2Moodle");
				$filesXmlChild->addChild('license', 'allrightsreserved');
				$filesXmlChild->addChild('sortorder', 0);
				$filesXmlChild->addChild('repositorytype', '$@NULL@$');
				$filesXmlChild->addChild('repositoryid', '$@NULL@$');
				$filesXmlChild->addChild('reference', '$@NULL@$');
				$activity->setFile($fileID);
				
				$fileID++;
			}
		}
	}
}

?>
