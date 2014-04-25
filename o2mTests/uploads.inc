<?php
/**
 * @file - All functions relating uploads
 */

/**
 * A form with a file upload field.
 *
 * This example allows the user to upload a file to Drupal which is stored
 * physically and with a reference in the database.
 *
 * @see qtici_zip_submit()
 * @ingroup qtici_zip
 */
 
module_load_include('php', 'qtici', 'objectParserv3');

function qtici_zip($form, &$form_state) {
  
  if (!empty($_GET['qtici']['i'])) {
    $i = $_GET['qtici']['i'];
  } else {
    $i = 0;
  }

  if (user_access('teacher')) {
    $form['#attributes'] = array('enctype' => 'multipart/form-data');

    $form['publish'] = array(
      '#type' => 'radios',
      '#title' => t('Wilt u de cursus en testen publiceren?'),
      '#default_value' => 1,
      '#options' => array('1' => t('Ja'), '0' => t('Nee')),
    );

    $form['files'] = array(
      '#type' => 'file',
      '#name' => 'files[]',
      '#attributes' => array('multiple' => 'multiple'),
    );
    
    $form['options'] = array(
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    
    global $_qtici_levels;
    $form['options']['level'] = array(
      '#type' => 'select',
      '#options' => array(0 => t('No Level')) + $_qtici_levels,
      '#required' => FALSE,
    );
    
    global $_qtici_topics;
    $form['options']['topic'] = array(
      '#type' => 'select',
      '#options' => array(0 => t('No topic')) + $_qtici_topics,
      '#required' => FALSE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Uploaden'),
    );
    
    $_GET['qtici']['i'] = $i;

    return $form;
  }
}

/**
 * Submit handler for qtici_zip().
 */
function qtici_zip_submit($form, &$form_state) {

  //get the $_FILES in a more comfortable array!
  $files = array();
  $fdata = $_FILES['files'];
  if (is_array($fdata['name'])) {
    for ($i = 0; $i < count($fdata['name']); ++$i) {
      $files[] = array(
        'name' => $fdata['name'][$i],
        'type' => $fdata['type'][$i],
        'tmp_name' => $fdata['tmp_name'][$i],
        'error' => $fdata['error'][$i],
        'size' => $fdata['size'][$i],
      );
    }
  }
  else {
    $files[] = $fdata;
  }

  $status = $form_state['values']['publish'];
  $topic = $form_state['values']['topic'];
  $level = $form_state['values']['level'];

  $index = 0;
  foreach ($files as $file) {
    $file = file_save_upload($index, array('file_validate_extensions' => array('zip')));
    $index++;
    $file = file_move($file, 'public://');
    file_save($file);

    $uniqid = uniqid(); //is used to place single QTI-test in a unique directory on the server (otherwise every directory is called QTI) --> this ID will be stored in the DB qtici_test!
    $zip = new ZipArchive();
    $path_file = substr($file->uri, 8);
    
    if ($zip->open('sites/default/files/' . $path_file) !== TRUE) {
      drupal_set_message($path_file . t(' kon dit zip-bestand niet openen!'), 'error');
    }
    
    //upload error when the file size exceeds the maximum allowed upload size
    if(file_validate_size($file, (int)(ini_get('upload_max_filesize')))){
      drupal_set_message(t('Kon dit zip-bestand uploaden wegens te groot bestand!'), 'error');
    }

    $files = array();
    $singleTest = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $files[] = $zip->getNameIndex($i);

      if ($zip->getNameIndex($i) == 'qti.xml') {
        $singleTest = true;
        unzipToDir('sites/default/files/' . $path_file, $uniqid . '/');
        insertZipInDatabase('sites/default/files/qtici/' . $uniqid . '/' . $zip->getNameIndex($i), $status, $topic, $level, '', $uniqid);
      }
    }

    if ($singleTest == false) {
      $files = array();

      $zipfiles = unzipToDir('sites/default/files/' . $path_file, str_replace('.zip', '', $file->filename) . '/');
      _qtici_save_course($zip, $file, $status, $zipfiles);
    }
  }

  // Set a response to the user.
  drupal_set_message(t('De bestanden werden geüpload'));
}

function qtici_zip_validate($form, &$form_state) {

  //get the $_FILES in a more comfortable array!
  $files = array();
  $fdata = $_FILES['files'];
  if (is_array($fdata['name'])) {
    for ($i = 0; $i < count($fdata['name']); ++$i) {
      $files[] = array(
        'name' => $fdata['name'][$i],
        'type' => $fdata['type'][$i],
        'tmp_name' => $fdata['tmp_name'][$i],
        'error' => $fdata['error'][$i],
        'size' => $fdata['size'][$i],
      );
    }
  }
  else {
    $files[] = $fdata;
  }

  foreach ($files as $new_file) {
    if ($new_file['error'] !== 0) {
      form_set_error('files', t('There was a problem uploading the zip, please try again later'));
    }
    else {
      if (!($new_file['type'] == "application/x-zip-compressed" || $new_file['type'] == "application/x-zip" || $new_file['type'] == "application/zip" || $new_file['type'] == "application/octet-stream")) { 
        // only zip-files
        form_set_error('files', t('You have to upload a .zip file'));
      }
    }
  }
}

/**
 * Unzip the source_file in the destination dir
 *
 * @param   string      The path to the ZIP-file.
 * @param   string      The path where the zipfile should be unpacked, if false the directory of the zip-file is used
 * @param   boolean     Indicates if the files will be unpacked in a directory with the name of the zip-file (true) or not (false) (only if the destination directory is set to false!)
 * @param   boolean     Overwrite existing files (true) or not (false)
 *
 * @return  boolean     Succesful or not
 */
function unzipToDir($src_file, $dest_dir = FALSE) {
  $zip = new ZipArchive;
  $res = $zip->open($src_file);
  $zipfiles = array();

  if ($res === TRUE) {
    if ($dest_dir === false) {
      $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter)) . "/";
    }
    // Create the directories to the destination dir if they don't already exist
    create_dirs($dest_dir);
    $final_dir = 'sites/default/files/qtici/' . $dest_dir;
    $files = array();
    $media = array();
    // Extract everything but .zip within the .zip
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $filename = $zip->getNameIndex($i);
      $extension = substr($filename, -4);
      $repo = substr($filename, -8);
      $ext = substr($extension, 1);

      if ($extension !== '.zip') {
        $files[] = $filename;
      }
      elseif ($repo === 'repo.zip') {
        $zipfiles[] = $filename;
      }
    }

    $zip->extractTo($final_dir, $files);

    // Extract .zips within .zip
    if (!empty($zipfiles)) {
      foreach ($zipfiles as $zips) {
        $path_to_zip = $final_dir;
        $zip->extractTo($path_to_zip, $zips);
      }
    }
    $zip->close();
  }
  else {
    return false;
  }

  return $zipfiles;
}

/**
 * This function creates recursive directories if it doesn't already exist
 *
 * @param String  The path that should be created
 *
 * @return  void
 */
function create_dirs($dir) {
  $path = 'public://qtici/';
  file_prepare_directory($path, FILE_CREATE_DIRECTORY);

  if (!is_dir('sites/default/files/qtici/' . $dir)) {
    $path .= $dir;
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
  }
  else {
    // Delete and create it empty again
    rrmdir('sites/default/files/qtici/' . $dir);
    create_dirs($dir);
  }
}


function insertZipInDatabase($path, $published, $topic, $level, $filename = '', $folderID = '', $courseName = '') {
  
  $object = parseQTIToObject($path . $filename, $folderID);
  $object->setOlat_testid($folderID);
  $object->setPublished($published);
  $object->setCourse($courseName);
  $object->setDate(REQUEST_TIME);
  if ($topic != 0) {
    $object->setTopic($topic);
  }
  if ($level != 0) {
    $object->setLevel($level);
  }
  // So far only one bundle
  $object->setBundle('qtici_test');
  // Test
  drupal_write_record('qtici_test', $object);
  
  $possibilityOption = null;
  // Section
  $sections = $object->getSections();
  foreach ($sections as $section) {
    $section->setTestid($object->id);
    drupal_write_record('qtici_section', $section);

    //Item
    $items = $section->getItems();
    foreach ($items as $item) {
      $item->sectionid = $section->id;
      drupal_write_record('qtici_item', $item);
      $possibilities = $item->getPossibilities();
      foreach ($possibilities as $possibility) {
        $possibility->itemid = $item->id;
        drupal_write_record('qtici_possibility', $possibility);
        // Replace ident with new id's for FiB
        if ($item->type == 'FIB') {
          $content = unserialize($item->content);
          $item->content = serialize(_qtici_setTextbox($content, $possibility->ident, $possibility->id));
        }
      }
      drupal_write_record('qtici_item', $item, array('id'));
      $feedbacks = $item->getFeedback();
      foreach ($feedbacks as $feedback) {
        $feedback->itemid = $item->id;
        drupal_write_record('qtici_feedback', $feedback);
      }
    } // END ITEM
  } // END SECTION
}

function _qtici_course_already_exists($filename) {
  $filename = str_replace('.zip', '', $filename);
  $result = db_query('SELECT c.id FROM qtici_course AS c WHERE c.filepath = :filename', array(':filename' => $filename));
  $num = $result->rowCount();

  return $num;
}

function _qtici_save_course($zip, $file, $status, $zipfiles) {
  for ($i = 0; $i < $zip->numFiles; $i++) {
    $files[] = $zip->getNameIndex($i);

    if (strstr($zip->getNameIndex($i), 'runstructure.xml') == "runstructure.xml") {

      $courseObject = parseCourseObject('sites/default/files/qtici/' . str_replace('.zip', '', $file->filename) . '/' . $zip->getNameIndex($i));
      $qtici_course_general_info_id = db_insert('qtici_course_general_info')
          ->fields(array(
            'general_id' => $courseObject->getId(),
            'short_title' => $courseObject->getShortTitle(),
            'type' => $courseObject->getType(),
            'publish_status' => $status,
          ))
          ->execute();

      $courseName = $courseObject->getShortTitle();

      //course
      $course_id = db_insert('qtici_course')
          ->fields(array(
            'long_title' => $courseObject->getLongTitle(),
            'version' => $courseObject->getVersion(),
            'general_info_id' => $qtici_course_general_info_id,
            'status' => 0,
            'filepath' => str_replace('.zip', '', $file->filename),
            'date' => date("d-m-Y H:i:s", time()),
          ))
          ->execute();

      foreach ($courseObject->getChapters() as $chapterObject) {
        $chapter_general_info_id = db_insert('qtici_course_general_info')
            ->fields(array(
              'general_id' => $chapterObject->getId(),
              'short_title' => $chapterObject->getShortTitle(),
              'type' => $chapterObject->getType(),
              'publish_status' => 1,
            ))
            ->execute();

        $chapter_id = db_insert('qtici_course_chapter')
            ->fields(array(
              'visibility_begin_date' => $chapterObject->getVisibilityBeginDate(),
              'visibility_end_date' => $chapterObject->getVisibilityEndDate(),
              'access_begin_date' => $chapterObject->getAccessBeginDate(),
              'access_end_date' => $chapterObject->getAccessEndDate(),
              'general_info_id' => $chapter_general_info_id,
              'course_id' => $course_id,
              'status' => 0,
            ))
            ->execute();

        $chapter_function_id = db_insert('qtici_course_function')
            ->fields(array(
              'chapter_id' => $chapter_id,
            ))
            ->execute();

        switch ($chapterObject->getType()) {
          case "iqtest":
          case "iqself":
          case "iqsurv":
            break;

          case "en":
            $chapter_learning_object_id = db_insert('qtici_course_learning_object')
                ->fields(array(
                  'learning_object' => $chapterObject->getChapterLearningObjectives(),
                  'function_id' => $chapter_function_id,
                ))
                ->execute();
            break;

          case "bc":
            foreach ($chapterObject->getChapterFolders() as $FolderObject) {
              $chapter_folder_id = db_insert('qtici_course_folder')
                  ->fields(array(
                    'name' => $FolderObject->getFileName(),
                    'location' => $FolderObject->getFileLocation(),
                    'type' => $FolderObject->getFileType(),
                    'size' => $FolderObject->getFileSize(),
                    'modified' => $FolderObject->getFileModified(),
                  ))
                  ->execute();

              $chapter_drop_folder_id = db_insert('qtici_course_drop_folder')
                  ->fields(array(
                    'function_id' => $chapter_function_id,
                    'folder_id' => $chapter_folder_id,
                  ))
                  ->execute();
            }
            break;

          case "sp":
          case "st":
            $chapter_page_id = db_insert('qtici_course_page')
                ->fields(array(
                  'location' => $chapterObject->getChapterPage(),
                  'function_id' => $chapter_function_id,
                ))
                ->execute();
            break;
        }

        getSubjectsDb($chapterObject->getSubjects(), $chapter_id);
      }
    }
  }

  if (!empty($zipfiles)) {
    foreach ($zipfiles as $zip) {
      $path_to_zip = 'sites/default/files/qtici/' . str_replace('.zip', '', $file->filename) . '/' . $zip;
      $path = str_replace('repo.zip', '', $path_to_zip);
      $folderID = str_replace('/repo.zip', '', $path_to_zip);
      $folderID = substr($folderID, strrpos($folderID, '/') + 1);
      $qtiZip = new ZipArchive();
      $qtiZip->open($path_to_zip);
      file_prepare_directory($path);
      $qtiZip->extractTo($path);
      for ($j = 0; $j < $qtiZip->numFiles; $j++) {
        // QTI Test
        if ($qtiZip->getNameIndex($j) == 'qti.xml') {
          insertZipInDatabase($path . '/', $status, $qtiZip->getNameIndex($j), $folderID, $courseName);
        }
      }
      $qtiZip->close();
    }
  }
}

function _qtici_overwrite_course($path_file, $zip, $file, $status) {

  // Delete course using the callback
  $coursename = str_replace('.zip', '', $file->filename);
  $result = db_query('SELECT c.id FROM qtici_course AS c WHERE c.filepath = :filename', array(':filename' => $coursename));
  $course_id = $result->fetchField();
  $courses = array(
    0 => $course_id . '-0',
  );

  $_POST["var"] = array(
    0 => $courses,
    1 => 'delete_course',
    3 => 'true',
  );

  course_conf_page_callback();

  // Create again the course
  $zipfiles = unzipToDir('sites/default/files/' . $path_file, str_replace('.zip', '', $file->filename) . '/');
  _qtici_save_course($zip, $file, $status, $zipfiles);
}
