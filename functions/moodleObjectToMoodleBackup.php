<?php

require_once("classes/olatclasses.php");
require_once("classes/moodleclasses.php");
require_once("classes/generalclasses.php");

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
 |   |   |_ ||| grades.xml -- (E)
 |   |   |_ ||| inforef.xml - Contains references to used media (fileIDs)
 |   |   |_ ||| module.xml -- Contains IDs and general module options
 |   |   |_ ||| page.xml ---- Contains IDs, the name and the content of the page
 |   |   |_ ||| roles.xml --- (E)
 |   |_ ...
 |_ [ ] course -------------- Contains general information about the entire course
 |   |_ ||| course.xml ------ Contains IDs, names and options
 | 	 |_ ||| enrolments.xml -- (E)
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
 |_ ||| questions.xml ------- (E)
 |_ ||| roles.xml ----------- (E)
 |_ ||| scales.xml ---------- (E)
*************************************************************************************/
//
// PARAMETERS
// -> $moodleObject = Moodle Object
//        $olatPath = OLAT Object (for the files)
//           $books = Reads out the checkbox in the beginning and turns pages in a row
//                    into a single book for a more clear overview
//
function moodleObjectToMoodleBackup($moodleObject, $olatObject, $books, $chapterFormat) {
	// Creates a temporary storage name made of random numbers.
	$num = "moodle";
	for ($i = 0; $i < 9; $i++) {
		$num .= strval(mt_rand(0, 9));
	}
	
	$path = getcwd() . "/tmp/" . $num;
	
	// Checks if the folders exist and creates them if they do not.
	if (!file_exists(getcwd() . "/tmp") and !is_dir(getcwd() . "/tmp")) {
		mkdir(getcwd() . "/tmp", 0777, true);
	}
	if (!file_exists($path) and !is_dir($path)) {
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
	$moodleBackupXml->addChild('name', 'OLAT2Moodle.mbz');
	$moodleBackupXml->addChild('moodle_version', 2013111800);
	$moodleBackupXml->addChild('moodle_release', '2.6');
	$moodleBackupXml->addChild('backup_version', 2013111800);
	$moodleBackupXml->addChild('backup_release', '2.6');
	$moodleBackupXml->addChild('backup_date', time());
	$moodleBackupXml->addChild('mnet_remoteusers', 0);
	$moodleBackupXml->addChild('include_files', 1);
	$moodleBackupXml->addChild('include_file_references_to_external_content', 0);
	$moodleBackupXml->addChild('original_wwwroot', 'OLAT2Moodle');
	$moodleBackupXml->addChild('original_site_identifier_hash', "36492b9f86ba50b90b65082da25006e96b348e1d");
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
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'filename');
	$moodleBackupXmlSettingsSetting->addChild('value', 'OLAT2Moodle.mbz');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'imscc11');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'users');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'anonymize');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'role_assignments');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'activities');
	$moodleBackupXmlSettingsSetting->addChild('value', '1');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'blocks');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'filters');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'comments');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'badges');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'calendarevents');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'userscompletion');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'logs');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'grade_histories');
	$moodleBackupXmlSettingsSetting->addChild('value', '0');
	$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
	$moodleBackupXmlSettingsSetting->addChild('level', 'root');
	$moodleBackupXmlSettingsSetting->addChild('name', 'questionbank');
	$moodleBackupXmlSettingsSetting->addChild('value', '1');
	
	
	////////////////////////////////////////////////////////////////////
	// COURSE
	
	// course folder
	
	$coursePath = $path . "/course";
	
	if (!file_exists($coursePath) and !is_dir($coursePath)) {
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
	
	$dom->loadXML($courseInforefXml->asXML());
	file_put_contents($coursePath . "/inforef.xml", $dom->saveXML());
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
	
	// files path
	$filesPath = $path . "/files";
	
	// files.xml
	$filesXml = new SimpleXMLElement($header . "<files></files>");
	$fileID = 10;
	
	if (!file_exists($filesPath) and !is_dir($filesPath)) {
		mkdir($filesPath, 0777, true);
	}
	
	// OLAT files
	$olatFilesPath = $olatObject->getRootdir() . "/coursefolder";	
	$olatFiles = listFolderFiles($olatFilesPath);
	$fileError = 0;
	foreach ($olatFiles as $olatFile) {
		$olatFilePath = $olatFilesPath . "/" . $olatFile;
		$fileSHA1 = sha1($olatFile);
		$fileSHA1Dir = $filesPath . "/" . substr($fileSHA1, 0, 2);
		if (!file_exists($fileSHA1Dir) and !is_dir($fileSHA1Dir)) {
			mkdir($fileSHA1Dir, 0777, true);
		}
		if (!is_dir($olatFilesPath . "/" . $olatFile)) {
			if (copy($olatFilesPath . "/" . $olatFile, $fileSHA1Dir . "/" . $fileSHA1)) {
				foreach ($moodleObject->getSection() as $section) {
					foreach ($section->getActivity() as $activity) {
						$fileOK = 0;
						$activityModuleName = $activity->getModuleName();
						switch ($activityModuleName) {
							case "page":
								if (strpos($activity->getContent(), $olatFile) !== false
													|| strpos($activity->getContent(), str_replace(' ', '%20', $olatFile)) !== false) {
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
							
							case "folder":
								foreach ($activity->getFolderFile() as $folderFile) {
									if ($folderFile->getFileName() == $olatFile) {
										$fileOK = 1;
										$filesXmlChild = $filesXml->addChild('file');
										$filesXmlChild->addAttribute('id', $fileID);
										$component = "mod_folder";
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
		}
		else {
			echo "<p style='color:red;'>ERROR COPYING FILE: " . $olatFile . "</p><br>";
			$fileError++;
		}
	}
	
	// Migrates the files to put in the folders.
	$olatExportPathRoot = $olatObject->getRootdir() . "/export";
	$olatExportRootFiles = listFolderFiles($olatExportPathRoot);
	$directoryArray = array();
	foreach ($olatExportRootFiles as $olatExportRootFile) {
		$dir = $olatExportPathRoot . "/" . substr($olatExportRootFile, 0, strpos($olatExportRootFile, DIRECTORY_SEPARATOR));
		$directoryArray[] = $dir;
	}
	
	$directoryArray = array_unique($directoryArray);
	
	foreach($directoryArray as $directory) {
		if (is_dir($directory)) {
			$olatExportFiles = listFolderFiles($directory);
			foreach ($olatExportFiles as $olatExportFile) {
				// Ignore the .xml and .zip files
				if (substr($olatExportFile, -3) != "xml" || substr($olatExportFile, -3) != "zip") {
					$fileSHA1 = sha1($olatExportFile);
					$fileSHA1Dir = $filesPath . "/" . substr($fileSHA1, 0, 2);
					if (!file_exists($fileSHA1Dir) and !is_dir($fileSHA1Dir)) {
						mkdir($fileSHA1Dir, 0777, true);
					}		
					foreach ($moodleObject->getSection() as $section) {
						foreach ($section->getActivity() as $activity) {
							$activityModuleName = $activity->getModuleName();
							if ($activityModuleName == "folder") {
								if ($activity->getActivityID() == (string) ($activity->getSectionID() - 50000000000000)) {
									$activityID = (string) ($activity->getActivityID() + 50000000000000);
								}
								else {
									$activityID = $activity->getActivityID();
								}
								$olatExportFilePath = $olatExportPathRoot . "/" . $activityID . "/" . $olatExportFile;
								if (file_exists($olatExportFilePath)) {
									//echo substr($olatExportFile, 0, strpos($olatExportFile, DIRECTORY_SEPARATOR)) . "<br>";
									if (!is_dir($olatExportFilePath)) {
										if (copy($olatExportFilePath, $fileSHA1Dir . "/" . $fileSHA1)) {
											foreach ($activity->getFolderFile() as $folderFile) {
												//echo $folderFile->getFileName() . "<br>";
												//echo preg_replace("/[\/\\\]/", "", substr($olatExportFile, strrpos($olatExportFile, DIRECTORY_SEPARATOR))) . "<br><br>";
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
							}
						}
					}
				}
			}
		}
	}
	
	$dom->loadXml($filesXml->asXML());
	file_put_contents($path . "/files.xml", $dom->saveXML());
	if ($fileError == 0) {
		echo "<p>Files copied</p>";
	}
	else {
		echo "<p style='color:red;'>WARNING - " . $fileError . " file(s) failed to copy</p>";
	}
	
	////////////////////////////////////////////////////////////////////
	// SECTIONS
	
	// sections folder
	if (!file_exists($path . "/sections") and !is_dir($path . "/sections")) {
		mkdir($path . "/sections", 0777, true);
	}
	
	// This number is for ordening the sections.
	$sectionNumber = 1;
	
	foreach ($moodleObject->getSection() as $section) {
		// Create the folder
		$sectionPath = $path . "/sections/section_" . $section->getSectionID();
		if (!file_exists($sectionPath) and !is_dir($sectionPath)) {
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
		foreach($section->getActivity() as $activity) {
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
		$moodleBackupXmlContentsSectionsSection = $moodleBackupXmlContentsSections->addChild('section');
		$moodleBackupXmlContentsSectionsSection->addChild('sectionid', $section->getSectionID());
		$moodleBackupXmlContentsSectionsSection->title = $section->getName();
		$moodleBackupXmlContentsSectionsSection->addChild('directory', "sections/section_" . $section->getSectionID());
	
		$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
		$moodleBackupXmlSettingsSetting->addChild('level', 'section');
		$moodleBackupXmlSettingsSetting->addChild('section', "section_" . $section->getSectionID());
		$moodleBackupXmlSettingsSetting->addChild('name', "section_" . $section->getSectionID() . "_included");
		$moodleBackupXmlSettingsSetting->addChild('value', 1);
		$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
		$moodleBackupXmlSettingsSetting->addChild('level', 'section');
		$moodleBackupXmlSettingsSetting->addChild('section', "section_" . $section->getSectionID());
		$moodleBackupXmlSettingsSetting->addChild('name', "section_" . $section->getSectionID() . "_userinfo");
		$moodleBackupXmlSettingsSetting->addChild('value', 0);
		
		$sectionNumber++;
	}
	
	////////////////////////////////////////////////////////////////////
	// ACTIVITIES
	
	// activities folder
	if (!file_exists($path . "/activities") and !is_dir($path . "/activities")) {
		mkdir($path . "/activities", 0777, true);
	}
	
	// Chapter IDs (increases by one by every chapter over all the books, so it's just an increment)
	// for every chapter page in the entire course
	foreach ($moodleObject->getSection() as $section) {
		$previousActivity = null;
		// Page numbers (increases by one by every chapter of every single book)
		$pageNum = 1;
		foreach ($section->getActivity() as $activity) {
			// Books are collections of pages, so this is to make sure that all pages
			// that could be bundled in a book will become a book.
			if (!$books) {
				// Create the folder
				$activityPath = $path . "/activities/" . $activity->getModuleName() . "_" . $activity->getModuleID();
				if (!file_exists($activityPath) and !is_dir($activityPath)) {
					mkdir($activityPath, 0777, true);
				}
				
				// "EMPTY"
				// activities/[activity]_[x]/grades.xml
				$activityGradesXml = new SimpleXMLElement($header . "<activity_gradebook></activity_gradebook>");
				$activityGradesXml->addChild('grade_items');
				$activityGradesXml->addChild('grade_letters');
				
				$dom->loadXML($activityGradesXml->asXML());
				file_put_contents($activityPath . "/grades.xml", $dom->saveXML());
				// activities/[activity]_[x]/roles.xml
				$activityRolesXml = new SimpleXMLElement($header . "<roles></roles>");
				$activityRolesXml->addChild('role_overrides');
				$activityRolesXml->addChild('role_assignments');
				
				$dom->loadXML($activityRolesXml->asXML());
				file_put_contents($activityPath . "/roles.xml", $dom->saveXML());
			
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
				$activityActivityXml->addAttribute('id', $activity->getActivityID());
				$activityActivityXml->addAttribute('moduleid', $activity->getModuleID());
				$activityActivityXml->addAttribute('modulename', $activity->getModuleName());
				$activityActivityXml->addAttribute('contextid', $activity->getContextID());
				$activityActivityChildXml = $activityActivityXml->addChild($activity->getModuleName());
				$activityActivityChildXml->addAttribute('id', $activity->getActivityID());
				$activityActivityChildXml->name = $activity->getName();
				$activityActivityChildXml->intro = $activity->getName();
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
						break;
					
					case "folder":
						$activityActivityChildXml->addChild('display', 1);
						$activityActivityChildXml->addChild('showexpanded', 1);
						$activityActivityChildXml->addChild('revision', 1);
						break;
						
					case "url":
						$activityActivityChildXml->addChild('display', 0);
						$activityActivityChildXml->externalurl = $activity->getURL();
						$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";s:1:"0";}');
						$activityActivityChildXml->addChild('parameters', 'a:0:{}');
						break;
						
					case "resource":
						$activityActivityChildXml->addChild('display', 0);
						$activityActivityChildXml->addChild('tobemigrated', 0);
						$activityActivityChildXml->addChild('legacyfiles', 0);
						$activityActivityChildXml->addChild('legacyfileslast', "$@NULL@$");
						$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";i:1;}');
						$activityActivityChildXml->addChild('revision', 1);
					
					case "wiki":
						$activityActivityChildXml->firstpagetitle = $activity->getName();
						$activityActivityChildXml->addChild('wikimode', 'collaborative');
						$activityActivityChildXml->addChild('defaultformat', 'html');
						$activityActivityChildXml->addChild('forceformat', 0);
						$activityActivityChildXml->addChild('editbegin', 0);
						$activityActivityChildXml->addChild('editend', 0);
						$activityActivityChildXml->addChild('subwikis');
				}
				
				$activityActivityChildXml->addChild('timemodified', time());
					
				$dom->loadXML($activityActivityXml->asXML());
				file_put_contents($activityPath . "/" . $activity->getModuleName() . ".xml", $dom->saveXML());
				
				// moodle_backup.xml
				$moodleBackupXmlContentsActivitiesActivity = $moodleBackupXmlContentsActivities->addChild('activity');
				$moodleBackupXmlContentsActivitiesActivity->addChild('moduleid', $activity->getModuleID());
				$moodleBackupXmlContentsActivitiesActivity->addChild('sectionid', $activity->getSectionID());
				$moodleBackupXmlContentsActivitiesActivity->addChild('modulename', $activity->getModuleName());
				$moodleBackupXmlContentsActivitiesActivity->title = $activity->getName();
				$moodleBackupXmlContentsActivitiesActivity->addChild('directory', "activities/" . $activity->getModuleName() . "_" . $activity->getModuleID());
			
				$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
				$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
				$moodleBackupXmlSettingsSetting->addChild('activity', $activity->getModuleName() . "_" . $activity->getModuleID());
				$moodleBackupXmlSettingsSetting->addChild('name', $activity->getModuleName() . "_" . $activity->getModuleID() . "_included");
				$moodleBackupXmlSettingsSetting->addChild('value', 1);
				$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
				$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
				$moodleBackupXmlSettingsSetting->addChild('activity', $activity->getModuleName() . "_" . $activity->getModuleID());
				$moodleBackupXmlSettingsSetting->addChild('name', $activity->getModuleName() . "_" . $activity->getModuleID() . "_userinfo");
				$moodleBackupXmlSettingsSetting->addChild('value', 0);
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
				if (!file_exists($activityPath) and !is_dir($activityPath)) {
					mkdir($activityPath, 0777, true);
				}
				
				// "EMPTY"
				// activities/[activity]_[x]/grades.xml
				$activityGradesXml = new SimpleXMLElement($header . "<activity_gradebook></activity_gradebook>");
				$activityGradesXml->addChild('grade_items');
				$activityGradesXml->addChild('grade_letters');
				
				$dom->loadXML($activityGradesXml->asXML());
				file_put_contents($activityPath . "/grades.xml", $dom->saveXML());
				// activities/[activity]_[x]/roles.xml
				$activityRolesXml = new SimpleXMLElement($header . "<roles></roles>");
				$activityRolesXml->addChild('role_overrides');
				$activityRolesXml->addChild('role_assignments');
				
				$dom->loadXML($activityRolesXml->asXML());
				file_put_contents($activityPath . "/roles.xml", $dom->saveXML());
				
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
					$activityActivityChildXml->name = $activity->getName();
					$activityActivityChildXml->intro = $activity->getName();
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
					$activityActivityXml->addAttribute('id', $activity->getActivityID());
					$activityActivityXml->addAttribute('moduleid', $activity->getModuleID());
					$activityActivityXml->addAttribute('modulename', $activity->getModuleName());
					$activityActivityXml->addAttribute('contextid', $activity->getContextID());
					$activityActivityChildXml = $activityActivityXml->addChild($activity->getModuleName());
					$activityActivityChildXml->addAttribute('id', $activity->getActivityID());
					$activityActivityChildXml->name = $activity->getName();
					$activityActivityChildXml->intro = $activity->getName();
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
							break;
						
						case "folder":
							$activityActivityChildXml->addChild('display', 1);
							$activityActivityChildXml->addChild('showexpanded', 1);
							$activityActivityChildXml->addChild('revision', 1);
							break;
							
						case "url":
							$activityActivityChildXml->addChild('display', 0);
							$activityActivityChildXml->externalurl = $activity->getURL();
							$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";s:1:"0";}');
							$activityActivityChildXml->addChild('parameters', 'a:0:{}');
							break;
							
						case "resource":
							$activityActivityChildXml->addChild('display', 0);
							$activityActivityChildXml->addChild('tobemigrated', 0);
							$activityActivityChildXml->addChild('legacyfiles', 0);
							$activityActivityChildXml->addChild('legacyfileslast', "$@NULL@$");
							$activityActivityChildXml->addChild('displayoptions', 'a:1:{s:10:"printintro";i:1;}');
							$activityActivityChildXml->addChild('revision', 1);
							break;
						
						case "wiki":
							$activityActivityChildXml->firstpagetitle = $activity->getName();
							$activityActivityChildXml->addChild('wikimode', 'collaborative');
							$activityActivityChildXml->addChild('defaultformat', 'html');
							$activityActivityChildXml->addChild('forceformat', 0);
							$activityActivityChildXml->addChild('editbegin', 0);
							$activityActivityChildXml->addChild('editend', 0);
							$activityActivityChildXml->addChild('subwikis');
							break;
					}
					
					$activityActivityChildXml->addChild('timemodified', time());
				}
				
				$dom->loadXML($activityActivityXml->asXML());
				
				if ($currentlyBook) {
					file_put_contents($activityPath . "/book.xml", $dom->saveXML());
				}
				else {
					file_put_contents($activityPath . "/" . $activity->getModuleName() . ".xml", $dom->saveXML());
				}
				
				if ($currentlyBook && $firstTags) {
				// moodle_backup.xml
					$moodleBackupXmlContentsActivitiesActivity = $moodleBackupXmlContentsActivities->addChild('activity');
					$moodleBackupXmlContentsActivitiesActivity->addChild('moduleid', $activity->getModuleID());
					$moodleBackupXmlContentsActivitiesActivity->addChild('sectionid', $activity->getSectionID());
					$moodleBackupXmlContentsActivitiesActivity->addChild('modulename', "book");
					$moodleBackupXmlContentsActivitiesActivity->title = $activity->getName();
					$moodleBackupXmlContentsActivitiesActivity->addChild('directory', "activities/book_" . $activity->getModuleID());
				
					$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
					$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
					$moodleBackupXmlSettingsSetting->addChild('activity', "book_" . $activity->getModuleID());
					$moodleBackupXmlSettingsSetting->addChild('name', "book_" . $activity->getModuleID() . "_included");
					$moodleBackupXmlSettingsSetting->addChild('value', 1);
					$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
					$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
					$moodleBackupXmlSettingsSetting->addChild('activity', "book_" . $activity->getModuleID());
					$moodleBackupXmlSettingsSetting->addChild('name', "book_" . $activity->getModuleID() . "_userinfo");
					$moodleBackupXmlSettingsSetting->addChild('value', 0);
				}
				else if (!$currentlyBook) {
					$moodleBackupXmlContentsActivitiesActivity = $moodleBackupXmlContentsActivities->addChild('activity');
					$moodleBackupXmlContentsActivitiesActivity->addChild('moduleid', $activity->getModuleID());
					$moodleBackupXmlContentsActivitiesActivity->addChild('sectionid', $activity->getSectionID());
					$moodleBackupXmlContentsActivitiesActivity->addChild('modulename', $activity->getModuleName());
					$moodleBackupXmlContentsActivitiesActivity->title = $activity->getName();
					$moodleBackupXmlContentsActivitiesActivity->addChild('directory', "activities/" . $activity->getModuleName() . "_" . $activity->getModuleID());
				
					$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
					$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
					$moodleBackupXmlSettingsSetting->addChild('activity', $activity->getModuleName() . "_" . $activity->getModuleID());
					$moodleBackupXmlSettingsSetting->addChild('name', $activity->getModuleName() . "_" . $activity->getModuleID() . "_included");
					$moodleBackupXmlSettingsSetting->addChild('value', 1);
					$moodleBackupXmlSettingsSetting = $moodleBackupXmlSettings->addChild('setting');
					$moodleBackupXmlSettingsSetting->addChild('level', 'activity');
					$moodleBackupXmlSettingsSetting->addChild('activity', $activity->getModuleName() . "_" . $activity->getModuleID());
					$moodleBackupXmlSettingsSetting->addChild('name', $activity->getModuleName() . "_" . $activity->getModuleID() . "_userinfo");
					$moodleBackupXmlSettingsSetting->addChild('value', 0);
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
	// questions.xml
	$questionsXml = new SimpleXMLElement($header . "<question_categories></question_categories>");
	
	$dom->loadXML($questionsXml->asXML());
	file_put_contents($path . "/questions.xml", $dom->saveXML());
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
		echo "<p>.zip created</p>";
	}
	catch (App_File_Zip_Exception $e) {
		echo "<p>ERROR - .zip failed to create: " . $e . "</p>";
	}
	
	// Renames the .zip to .mbz (.mbz is just a renamed .zip anyway)
	if (rename($zipPath, $path . ".mbz")) {
		echo "<p>.zip renamed to .mbz</p>";
	}
	else {
		echo "<p>ERROR - .zip failed to rename</p>";
	}
	
	$moodleDownload = "/tmp/" . clean($moodleObject->getFullName()) . ".mbz";
	
	if (rename(getcwd() . "/tmp/" . $num . ".mbz", getcwd() . $moodleDownload)) {
		echo "<p>Course name given to .mbz file</p>";
	}
	else {
		echo "<p>ERROR - .mbz failed to rename</p>";
	}

	// Remove both the OLAT and Moodle temporary directory
	rrmdir($path);
	echo "<p>OLAT temp folder removed</p>";
	rrmdir($olatObject->getRootDir());
	echo "<p>Moodle temp folder removed</p>";
	
	return $moodleDownload;
}

?>
