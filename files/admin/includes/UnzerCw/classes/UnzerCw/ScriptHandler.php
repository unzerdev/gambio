<?php 
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

require_once 'Customweb/Core/Assert.php';



class  UnzerCw_ScriptHandler {
	
	private $sourceFilePath;
	private $replacements = array();
	private $sessionFile = null;
	
	public function __construct($sourceFile) {
		Customweb_Core_Assert::hasLength($sourceFile);
		if (!file_exists($sourceFile)) {
			throw new Exception("The file '" . strip_tags($sourceFile) . "' does not exists.");
		}
		$this->sourceFilePath = $sourceFile;
	}
	
	public function replace($search, $replace) {
		$this->replacements[] = array(
			'search' => $search,
			'replace' => $replace,
		);
		return $this;
	}
	
	public function write() {
		$tmpFilePath = $this->getTmpFilePath();
		
		if (!file_exists($tmpFilePath)) {
			$content = file_get_contents($this->getSourceFilePath());
			foreach ($this->replacements as $replace) {
				$content = str_replace($replace['search'], $replace['replace'], $content);
			}
			file_put_contents($tmpFilePath, $content);
		}
		
		return $tmpFilePath;
	}
	
	protected function getTmpFilePath(){
		$time = filemtime($this->getSourceFilePath());
		
		$stringToHash = '';
		foreach ($this->replacements as $replace) {
			$stringToHash .= $replace['search'] . $replace['replace'];
		}
		
		return UNZERCW_CATALOG_PATH . '/templates_c/unzercw_' .  basename($this->getSourceFilePath(), '.php') . '_' . $time . '_' . md5($stringToHash) . '.php';
	}
	
	public function getContent() {
		return $this->content;
	}
	
	public function getSourceFilePath() {
		return $this->sourceFilePath;
	}
	
	/**
	 * Loads the session from the given file.
	 * 
	 * @throws Exception
	 * @return UnzerCw_ScriptHandler
	 */
	public function loadSession($file){
		$path = UNZERCW_TMP_SESSION_DIRECTORY_PATH . basename($file);
		if (!file_exists($path)) {
			throw new Exception("Invalid session file.");
		}
		$this->sessionFile = $path;
		$_SESSION = unserialize(file_get_contents($path));
		register_shutdown_function(array($this, 'writeSessionFile'));
		
		return $this;
	}
	
	public function writeSessionFile() {
		file_put_contents($this->sessionFile, serialize($_SESSION));
	}
	
}
