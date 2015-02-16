<?php

// Puts an entire directory with its structure into a .zip
// With thanks to Roger Thomas, the original creator
// http://www.rogerethomas.com/blog/recursively-zip-entire-directory-using-php
//
// Bert Truyens
//
class App_File_Zip_Exception extends Exception {}
	
class App_File_Zip {

	/**
	 * Zip a file, or entire directory recursively.
	 *
	 * @param string $source directory or file name
	 * @param string $destinationPathAndFilename full path to output
	 * @throws App_File_Zip_Exception
	 * @return boolean whether zip was a success
	*/
	public static function CreateFromFilesystem($source, $destinationPathAndFilename) {
		$base = realpath(dirname($destinationPathAndFilename));
		if (!is_writable($base)) {
			throw new App_File_Zip_Exception('Destination must be writable directory.');
		}
		if (!is_dir($base)) {
			throw new App_File_Zip_Exception('Destination must be a writable directory.');
		}
		if (!file_exists($source)) {
			throw new App_File_Zip_Exception('Source doesnt exist in location: ' . $source);
		}
		
		$source = realpath($source);

		if (!extension_loaded('zip') || !file_exists($source)) {
			return false;
		}

		$zip = new ZipArchive();
		$zip->open($destinationPathAndFilename, ZipArchive::CREATE);
		if (is_dir($source) === true) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
			$baseName = realpath($source);
			foreach ($files as $file) {
				if (in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1), array('.', '..')) ){
					continue;
				}
				$relative = substr($file, strlen($baseName));
				if (is_dir($file) === true) {
					// Add directory
					$added = $zip->addEmptyDir(trim($relative, "\\/"));
					if (!$added) {
						throw new App_File_Zip_Exception('Unable to add directory named: ' . trim($relative, "\\/"));
					}
				}
				else if (is_file($file) === true) {
					// Add file
					$added = $zip->addFromString(trim($relative, "\\/"), file_get_contents($file));
					if (!$added) {
						throw new App_File_Zip_Exception('Unable to add file named: ' . trim($relative, "\\/"));
					}
				}
			}
		}
		else if (is_file($source) === true) {
			// Add file
			$added = $zip->addFromString(trim(basename($source), "\\/"), file_get_contents($source));
			if (!$added) {
				throw new App_File_Zip_Exception('Unable to add file named: ' . trim($relative, "\\/"));
			}
		}
		else {
			throw new App_File_Zip_Exception('Source must be a directory or a file.');
		}

		return $zip->close();
	}

}

// This is the error handler of the project, every warning or error will be written
// to this class so it can be shown to the end user at the end of the process.
class o2mErrorHandler {

	public $error = array();
	
	public function __construct() {}
	
	public function setError($error) {
		array_push($this->error, $error);
	}
	
	public function getErrors() {
		return $this->error;
	}

}

class Error {

	public $type;		// Type of error (ERROR or WARNING)
	public $level;		// Severity of error (1 = Low priority, 2 = High priority)
	public $errorText;	// The error itself
	public $partOf;		// If the error is part of a bigger error

	public function __construct($type, $level, $errorText, $partOf) {
		$this->type = $type;
		$this->level = $level;
		$this->errorText = $errorText;
		$this->partOf = $partOf;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setLevel($level) {
		$this->level = $level;
	}
	
	public function getLevel() {
		return $this->level;
	}
	
	public function setErrorText($errorText) {
		$this->errorText = $errorText;
	}
	
	public function getErrorText() {
		return $this->errorText;
	}
	
	public function setPartOf($partOf) {
		$this->partOf = $partOf;
	}
	
	public function getPartOf() {
		return $this->partOf;
	}
	
}
	
?>
