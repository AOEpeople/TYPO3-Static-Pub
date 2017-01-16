<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Static publishing extension, base class
 *
 * $Id $
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Static publishing extension, base class
 *
 * @package TYPO3
 * @subpackage tx_staticpub
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
class tx_staticpub {

	var $errorMsg = '';		// Error message.


	const FILE_CREATED	= 111;
	const FILE_CHANGED	= 222;
	const FILE_NOCHANGE = 333;

	protected static $stateMessages = array(
		111 => 'New file created',
		222 => 'Existing file updated.',
		333 => 'Existing file has not changed.',
	);

	/**
	 * Return directory for static publishing
	 *
	 * @return	string|null		Returns publishing directory relative to the path site (if any)
	 */
	function getPublishDir()	{

		$currentPageId = $GLOBALS['TSFE']->id;

		// hook
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['staticpub/class.tx_staticpub.php']['getPublishDir'])) {
			$params = array(
				'currentPageId' => $currentPageId,
			);
			$pubDir = GeneralUtility::callUserFunction(
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['staticpub/class.tx_staticpub.php']['getPublishDir'],
				$params,
				$this
			);
		}

		// retrieve from page tsconfig
		if (empty($pubDir)) {
			$pageTSconfig = BackendUtility::getPagesTSconfig($currentPageId);
			if (isset($pageTSconfig['tx_staticpub.']['publishDir'])) {
				$pubDir = $pageTSconfig['tx_staticpub.']['publishDir'];
			}
		}

		// retrieve from TYPO3_CONF_VARS
		if (empty($pubDir)) {
			$pubDir = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['staticpub']['publishDir'];
		}

		if (!empty($pubDir)) {
			$pubDirAbs = GeneralUtility::getFileAbsFileName($pubDir);
			if (substr($pubDirAbs,-1)!='/')	{
				$pubDirAbs.='/';
			}
			if (TRUE ===$this->autoCreatePublishDir($pubDirAbs)){
				return $pubDirAbs;
			}else{
				$GLOBALS['TSFE']->applicationData['tx_crawler']['log'][] = 'EXT:staticpub getPublishdir - target directory is not existing '.$pubDirAbs;
			}
		}
	}

	/**
	 * Gets list of configured resource types (file extensions) allowed for static publishing.
	 *
	 * @param  array $conf  Staticpub crawler configuration array.
	 * @return string		Comma separated list of resource file extensions.
	 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
	 */
	protected function getResourceWhitelist( array $conf=null ) {
		# Default resource types allowed for publishing
		$whitelist = 'gif,jpeg,jpg,png,ico,css,js,swf';

		# Check for domain level whitelist configuration
		if ( $conf && isset($conf['resources.']['whitelist']) ) {
			$resourceWhitelist = explode( ',', $conf['resources.']['whitelist'] );
		} else {
			# Check for globally configured whitelist configuration
			$extConf = $GLOBALS['TYPO3_CONF_VARS']["EXT"]["extConf"]['staticpub'];
			$extConf = unserialize( $extConf );
			if(is_array($extConf)){
				if ( array_key_exists('resourceWhitelist', $extConf) ) {
					$resourceWhitelist = explode( ',', $extConf['resourceWhitelist'] );
				}
			}
		}
		if ( isset($resourceWhitelist) ) {
			$resourceWhitelist = implode( ',', array_map('trim', $resourceWhitelist) );

			if ( 0 < strlen($resourceWhitelist) ) {
				$whitelist = strtolower( $resourceWhitelist );
			}
		}

		return $whitelist;
	}












	/**********************************
	 *
	 * Create static files
	 *
	 **********************************/

	/**
	 * Writes content string to static filename in publishing directory
	 *
	 * @param	string	$path		Path in which to publish file (relative to $pubDir)
	 * @param	string	$file		Filename (if blank, writes to "index.html")
	 * @param	string	$content	Content to write
	 * @param	string	$pubDir		Absolute dir in which to publish the content
	 * @param	integer	$page_id	Page id
	 * @param	array	$options	Options. Key: includeResources=boolean; decides if resources are rendered or not.
	 * @return	string				Success-message
	 */
	public function createStaticFile($path,$file,$content,$pubDir,$page_id,$options=array()) {

		# Fix base URL:
		if ( isset($options['overruleBaseUrl']))	{
			$htmlparser = GeneralUtility::makeInstance( 'TYPO3\\CMS\\Core\\Html\\HtmlParser' );
			$parts = $htmlparser->splitTags( 'base', $content, 1 );

			if ( isset($parts[1]) ) {
				$parts[1] = !$options['overruleBaseUrl'] ? '' : '<base href="'.htmlspecialchars($options['overruleBaseUrl']).'" />';
				$content = implode( '', $parts );
			}
		}
		# Fix base URL with uid:
		if (isset($options['sys_domain_base_url']))	{
			$htmlparser = GeneralUtility::makeInstance( 'TYPO3\\CMS\\Core\\Html\\HtmlParser' );
			$parts = $htmlparser->splitTags( 'base', $content, 1 );
			$url = $this->getUrlFromSysDomainUid($options['sys_domain_base_url']);
			if ( isset($parts[1]) ) {
				$parts[1] = !$url ? '' : '<base href="'.htmlspecialchars($url).'" />';
				$content = implode( '', $parts );
			}
		}
		# Find relative path prefix for file:
		if ( $options['includeResources'] === 'relPath' ) {
			$prefixN = count( explode('/', $path) ) - 1;
			if( '/' == substr($path, -1) ) $prefixN--;

			$prefixStr = '';

			for( $i = 0; $i < $prefixN; $i++ ) {
				$prefixStr .= '../';
			}
		}

		if ( $options['includeResources'] || $options['checkLinks'] ) {
			$token = md5( microtime() );
			$htmlparser = GeneralUtility::makeInstance( 'TYPO3\\CMS\\Core\\Html\\HtmlParser' );
			$parts = explode( $token, $htmlparser->prefixResourcePath($token, $content, array(), $token) );

			if ( array_key_exists('includeResources', $options) ) {
				# Extract resources from content
				$resources = array();
				$partsCount = count( $parts );

				for ( $i = 1; $i <= $partsCount; $i += 2 ) {
					$resources[] = $parts[$i];
				}

				$miscellaneousResources = $this->extractResources( $content, $path );
				$resources = array_merge( $resources, $miscellaneousResources );

				# Parse resources for further resource references
				$additionalResources = array();

				foreach ( $resources as $index => $resource ) {
					$resourceType = strtoupper( substr($resource, -3) );

					if ( !strcmp('CSS', $resourceType) ) {
						$fileInfo = pathinfo( $resource );
						$resourceFile = PATH_site . $fileInfo['dirname'] . '/' . $fileInfo['basename'];
                        if(FALSE == file_exists($resourceFile)) {
                            return;
                        }
                        $fileContent = file_get_contents( $resourceFile );
						$moreResources = $this->extractResources( $fileContent, $fileInfo['dirname'] . '/', true );
						$additionalResources = array_merge( $additionalResources, $moreResources );
					}
				}

				$resources = array_merge( $resources, $additionalResources );

				# Get resources from static includes
				$resources = array_merge( $resources, $this->getResourcesFromStaticIncludes($options) );

				# Get resources from reference index if enabled
				if ( isset($options['resources.']['refIndexLookUp']) && ('1' == $options['resources.']['refIndexLookUp']) ) {
					$resources = array_merge( $resources, $this->getResourcesFromRefIndex($page_id) );
				}

				# Get rid of duplicate resources
				$resource = array_unique( $resources );

				# Copy resources to configured static-pub folder
				$this->includeResources( $resources, $this->getPublishDirForResources($pubDir,$options), $page_id, $options );
			}

			foreach ( $parts as $k => $v ) {
				if ( $k % 2 ) {
					$noPrefix = false;

					# Check links
					if ( array_key_exists('checkLinks', $options) ) {
						if ( 0 && preg_match('/<a[^<>]+$/', $parts[$k-1]) ) {

							# Resource is a directory, add default script name "index.html"
							if ( '/' == substr($parts[$k], -1) ) $parts[$k] .= 'index.html';

							# Set JavaScript notice when no file found:
							if ( !@is_file($pubDir.$parts[$k]) ) {
								$parts[$k] = 'javascript:alert("Sorry, this link is disabled in the offline version");return false;';
								$noPrefix = true;
							}
						}
					}

					# Fix relative path of resources (including links):
					if ( $options['includeResources'] === 'relPath' && !$noPrefix ) {
						$parts[$k] = $prefixStr . $parts[$k];
					}
				}
			}

			# Update if source has changed:
			if ( ($options['includeResources'] === 'relPath') || $options['checkLinks'] )	{
				$content = implode( '', $parts );
			}
		}

	    if ( $options['addComment'] ) $content.='<!-- sp - '.date(r).'--->';

		# Check for file prefix
	    $path = $this->getResourcePrefix($file, $options) . ltrim($path, '/');

		# Write file:
		$result = $this->createFile( $path, $file, $content, $pubDir, $page_id );
		return $result;
	}
	/**
	 * @param integer $uid
	 * @return string url
	 * @throws Exception
	 */
	protected function getUrlFromSysDomainUid($uid){
		$uid = intval($uid);
		if ($uid > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'sys_domain',
				'uid = '.$uid .
				BackendUtility::BEenableFields('sys_domain') .
				BackendUtility::deleteClause('sys_domain')
			);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ($row['domainName'] != '') {
				return 'http://'.$row['domainName'];
			}
		}
		throw new Exception('could not find url from uid: '.$uid);
	}
	/**
	 * Copies resource files to configured publishing folder.
	 *
	 * @param  array 	$resources	An array of relative (w.r.t TYPO3 root) resource links.
	 * @param  string 	$pubDir		Absolute path to publish directory.
	 * @param  integer 	$pid		Page ID.
	 * @param  array	$options	Crawler configuration.
	 * @return void
	 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
	 */
	protected function includeResources( array $resources, $pubDir, $pid, $options ) {

		$log = array();
		$resourceWhitelist = $this->getResourceWhitelist( $options );

		foreach ( $resources as $resource ) {
			$fileInfo = GeneralUtility::split_fileref( $resource );

			if ( GeneralUtility::inList($resourceWhitelist, $fileInfo['fileext']) )	{
				$fileName = GeneralUtility::getFileAbsFileName( $resource );

				if ( @is_file($fileName) ) {
					if ( !isset($log[$resource]) ) {
						$fileInfo 	= GeneralUtility::split_fileref( $resource );
						$path 		= $this->getResourcePrefix($fileInfo['file'], $options) . $fileInfo['path'];
						$state 		= $this->createFile( $path, $fileInfo['file'], GeneralUtility::getUrl($fileName), $pubDir, $pid, true );

						$logEntry = array();
						$logEntry['fileInfo'] = $fileInfo;
						$logEntry['path'] = $path;
						$logEntry['filename'] = $fileInfo['file'];
						$logEntry['state']	= $state;
						$logEntry['message'] = $this->getMessageForState($state);


						$GLOBALS['TSFE']->applicationData['tx_crawler']['log']['resources'][$fileInfo['fileext']][] = $logEntry;
						$log[$resource] = $resource;
					}
				}
			}
		}
	}

	/**
	 * Creates and returns a message for a file creation state.
	 *
	 * @param string $state
	 * @return string
	 */
	protected function getMessageForState($state){
		return self::$stateMessages[$state];
	}

	/**
	 * Extracts resource links from given content. It includes resources defined both inline as well as in external CSS files.
	 * If a CSS file includes other CSS files then they are also parsed.
	 *
	 * @param  string  $content		Content to scan for resource links.
	 * @param  string  $path		Path to the content file. Require to resolve relative path.
	 * @param  boolean $recursive	Flag to specify whether or not to parse fetched resources recursively for containing resources.
	 * @return array				Array of fetched resource links.
	 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
	 */
	protected function extractResources( $content, $path, $recursive=false ) {

		$resources = array();
		$resourceLocations = array( 'fileadmin', 'uploads', 'typo3temp', 'typo3conf' );

		# Define patterns here to extract the desired resources
		$patterns = array(
						# Pattern to match resources defined in style properties
						'url\(\s*[\'"]?([^\(\)\'"\s]+)[\'"]?\s*\)',

						# Pattern for matching CSS resources defined via @import "stylesheet.css"
						'@import\s+[\'"]([^\(\)\'"]+)[\'|"]'
					);
		$pattern = '/(?:' . implode( ')|(?:', $patterns ) . ')/i';
		$matchCount = preg_match_all( $pattern, $content, $matches );

		if ( $matchCount && (0 < $matchCount) ) {

			$matchedResources = array();
			$patternCount = count( $patterns );

			for ( $i = 1; $i <= $patternCount; ++$i ) {
				$resources = array_merge( $resources, array_filter($matches[$i]) );
			}

			# Resolve path for resources
			foreach ( $resources as $index => $resource ) {
				if(substr($resource,0,1)==='/'){
					// fix absolutes paths
					$resource = substr( $resource, 1 );
				}
				$resourceRoot = substr( $resource, 0, strpos($resource, '/') );
				if ( !in_array($resourceRoot, $resourceLocations) && strcmp('/', $resource{0}) ) {
					$resources[$index] = GeneralUtility::resolveBackPath( $path . $resource );
				} else {
					$resources[$index] = GeneralUtility::resolveBackPath( $resource );
				}
			}

			# Check if extracted resources need to be parsed for containing resources
			if ( $recursive ) {
			  $additionalResources = array();

				foreach ( $resources as $index => $resource ) {
					$resourceType = strtoupper( substr($resource, -3) );

					if ( !strcmp('CSS', $resourceType) ) {
						$fileInfo = pathinfo( $resource );
						$fileContent = file_get_contents( PATH_site . $fileInfo['dirname'] . '/' . $fileInfo['basename'] );
						$moreResources = $this->extractResources( $fileContent, $fileInfo['dirname'] . '/', true );
						$additionalResources = array_merge( $additionalResources, $moreResources );
					}
				}

				$resources = array_merge( $resources, $additionalResources );
			}
		}

		return $resources;
	}

	/**
	 * Returns resources for the page by looking up in the reference index table.
	 *
	 * @param  integer $pid		Page-ID to extract the resources for.
	 * @return array 			An array of TYPO3 relative resource file paths.
	 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
	 */
	protected function getResourcesFromRefIndex( $pid ) {
		$resources = array();

		$resSelectQuery = '
			SELECT `sys_refindex`.ref_string
			FROM `sys_refindex`
			LEFT JOIN `tt_content` ON `sys_refindex`.recuid = `tt_content`.uid
			WHERE `tt_content`.pid = ' . $pid . '
					AND `sys_refindex`.ref_table = \'_FILE\'
					AND `sys_refindex`.deleted = 0';
		$res = $GLOBALS['TYPO3_DB']->sql_query( $resSelectQuery );

		if ( $res && (0 < $GLOBALS['TYPO3_DB']->sql_num_rows($res)) ) {
			while ( $record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) ) {
				$resources[] = $record['ref_string'];
			}
		}

		return $resources;
	}

	/**
	 * Gets resources from locations specified in staticpub crawler configuration.
	 *
	 * @param  array $conf	Staticpub crawler configuration.
	 * @return array		Array of TYPO3 relative resource file paths.
	 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
	 */
	protected function getResourcesFromStaticIncludes( array $conf ) {
		$resources = array();

		if ( isset($conf['resources.']['staticIncludes.']) && is_array($conf['resources.']['staticIncludes.']) ) {
			$staticIncludes = $conf['resources.']['staticIncludes.'];

			foreach ( $staticIncludes as $resource ) {
				$resource = GeneralUtility::getFileAbsFileName( $resource );

				if ( @is_file($resource) ) {
					$resources[] = substr( $resource, strlen(PATH_site) );
				} else {
					if ( @is_dir($resource) ) {
						$resources = array_merge( $resources, $this->getResourcesFromDirectory($resource) );
					}
				}
			}
		}

		return $resources;
	}

	/**
	 * Gets resources from locations specified in staticpub crawler configuration.
	 *
	 * @param  array 	$absDirPath	Absoute directory path to parse for resource files.
	 * @param  integer 	$depth		A dummy parameter to control recusion level for sub-directories parsing.
	 * @return array				Array of TYPO3 relative resource file paths.
	 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
	 */
	protected function getResourcesFromDirectory( $absDirPath, $depth=0 ) {
		$resources = array();

		if ( 25 > $depth ) {
			if ( $dir = @opendir($absDirPath) ) {
				while ( $element = readdir($dir) ) {
					if ( '.' != substr($element, 0, 1) ) {
						$element = $absDirPath . '/' . $element;

						if ( @is_file($element) ) {
							$resources[] = substr( $element, strlen(PATH_site) );
						} else {
							if ( @is_dir($element) ) {
								$resources = array_merge( $resources, $this->getResourcesFromDirectory($element, ++$depth) );
							}
						}
					}
				}

				closedir( $dir );
			}
		}

		return $resources;
	}

	/**
	 * Resolves static path for given file using provided configuration.
	 *
	 * @param  string $file  File name.
	 * @param  array  $conf  Staticpub crawler configuration.
	 * @return string        Static path string.
	 * @author Chetan Thapliyal <chetan.thapliyal@aoe.com>
	 */
	protected function getResourcePrefix( $file, array $conf ) {

		$prefix = '';
		static $parsedConf;

		if ( !is_array($parsedConf) ) {
			$parsedConf['fileTypes'] = array();

			if ( array_key_exists('resources.', $conf) && is_array($conf['resources.']) ) {
				foreach( $conf['resources.'] as $index => $resourceConf ) {
					if ( is_array($resourceConf) && array_key_exists('fileTypes', $resourceConf) ) {
						$resourceTypes = explode( ',', $resourceConf['fileTypes'] );
						$resourceTypes = array_map( 'trim', $resourceTypes );
						$resourceTypes = array_map( 'strtoupper', $resourceTypes );

						$pathPrefix = array_key_exists('pathPrefix', $resourceConf) ? $resourceConf['pathPrefix'] : '';
						$parsedConf['pathPrefixes'][$index] = rtrim($pathPrefix, '/');

						foreach ( $resourceTypes as $type ) {
							$parsedConf['fileTypes'][$type] = $index;
						}
					}
				}

				# Set defaults
				if ( isset($conf['resources.']['default.']['pathPrefix']) ) {
					$parsedConf['pathPrefixes']['default'] = rtrim( $conf['resources.']['default.']['pathPrefix'], '/' );
				} else {
					$parsedConf['pathPrefixes']['default'] = '';
				}
			}
		}

		# Get file extension
		$fileExt = strtoupper( substr($file, -3) );

		if ( array_key_exists($fileExt, $parsedConf['fileTypes']) ) {
			$prefix = $parsedConf['pathPrefixes'][$parsedConf['fileTypes'][$fileExt]];
		} else {
			$prefix = $parsedConf['pathPrefixes']['default'];
		}

		return $prefix . '/';
	}

	/**
	 * Writes content string to static filename in publishing directory
	 *
	 * @param	string		$path Path in which to publish file (relative to $pubDir)
	 * @param	string		$file Filename (if blank, writes to "index.html")
	 * @param	string		$content Content to write
	 * @param	string		$pubDir Absolute dir in which to publish the content
	 * @param	integer		$page_id Page id
	 * @param	boolean		$isResource Is resource
	 * @return	string		Success-message
	 */
	function createFile($path,$file,$content,$pubDir,$page_id,$isResource=FALSE)	{
		$this->errorMsg = '';



			// If there is a path prefix then create the path if not created already...:
		if (!@is_dir($pubDir.$path) && strcmp($path,'') && $path!='/')	{
			$this->errorMsg = $this->createPath($path,$pubDir);
			if ($this->errorMsg) {
				return;
			}
		}

			// Write file to dir:
		if (@is_dir($pubDir.$path) && substr($pubDir.$path,-1)=='/' && (!$isResource || $file))	{

				// Set filename to "index.html" if not given (assumed default filename of webserver)
			$fN = $file ? $file : 'index.html';

			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['staticpub/class.tx_staticpub.php']['createFile_processContent'])) {
				reset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['staticpub/class.tx_staticpub.php']['createFile_processContent']);
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['staticpub/class.tx_staticpub.php']['createFile_processContent'] as $classRef) {
					$userObj = &GeneralUtility::getUserObj($classRef);
					$content = $userObj->createFile_processContent($path,$file,$content,$pubDir,$page_id,$isResource);
				}
			}
				// Ask for existing record:
			$existRec = $this->getRecordForFile($path.$fN);
			if (is_array($existRec))	{

				if (!is_file($pubDir.$path.$fN) || (md5_file($pubDir.$path.$fN) != md5($content))) {

					// Overwrite file if it has changed or does not exist
					if(FALSE !== GeneralUtility::writeFile($pubDir.$path.$fN, $content)){
						if (TYPO3_DLOG) GeneralUtility::devLog(sprintf('File "%s" was  changed and written', $path.$fN), 'staticpuc', 0);
						$result = self::FILE_CHANGED;
					}else{
						$this->errorMsg = 'File "'.$pubDir.$path.$fN.'" could not be created.';
					}
				} else {
					if (TYPO3_DLOG) GeneralUtility::devLog(sprintf('File "%s" has not changed', $path.$fN), 'staticpuc', 0);

					$result = self::FILE_NOCHANGE;
				}

					// Create record for published file:
				$this->createRecordForFile($path.$fN, $isResource?0:$page_id, TRUE);

				return $result;
			} else {
					// Write new file:
				if(FALSE !== GeneralUtility::writeFile($pubDir.$path.$fN, $content)){
					// Create record for published file:
					$this->createRecordForFile($path.$fN, $isResource?0:$page_id);
					return self::FILE_CREATED;
				}else{
					$this->errorMsg = 'File "'.$pubDir.$path.$fN.'" could not be created.';
				}
			}
		} else {
			$this->errorMsg = 'Path "'.$pubDir.$path.'" was not valid.';
		}
	}

	/**
	 * Create path in publishing directory
	 *
	 * @param	string		$path Path to create
	 * @param	string		$pubDir Absolute publishing directory
	 * @return	boolean		Returns false on success, otherwise error message
	 */
	function createPath($path,$pubDir)	{
		//remove leading slash from path
		if ($path{0}=='/')
			$path=substr($path,1);

		if (substr($path,-1)=='/')	{
			$pathParts = explode('/',substr($path,0,-1));
			foreach($pathParts as $c => $partOfPath)	{
				if (strcmp($partOfPath,''))	{
					if (!@is_dir($pubDir.$partOfPath))	{
						GeneralUtility::mkdir($pubDir.$partOfPath);
					}

					if (@is_dir($pubDir.$partOfPath))	{
						$pubDir.=$partOfPath.'/';
					} else return 'ERROR: Directory "'.$partOfPath.'" was still not created!';
				} else return 'ERROR: part of path ('.$partOfPath.') "'.$path.'" was empty! pubdir:'.$pubDir;
			}
			return FALSE;
		} else return 'ERROR: Directory "'.$path.'" did not end with "/" for pubdir:'.$pubDir;
	}

	/**
	 * Creates or updates the record for a written file.
	 * All published files have a record 1-1
	 *
	 * @param	string		$filepath Filepath (primary key for record in hashed form)
	 * @param	integer		$page_id Page ID where the file belongs
	 * @param	boolean		$update If set, an EXISTING record is updated instead of creating a new. This flag MUST be set based on an actual test if a record already exists!
	 * @return	void
	 */
	function createRecordForFile($filepath, $page_id, $update=FALSE)	{

			// Set
		$field_values = array(
			'filepath' => $filepath,
			'filepath_hash' => GeneralUtility::md5int($filepath),
			'page_id' => $page_id,
			'tstamp' => time(),
		);

		if ($update)	{
				// Update in database:
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_staticpub_pages', 'filepath_hash='.GeneralUtility::md5int($filepath), $field_values);
		} else {
				// Insert in database:
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_staticpub_pages', $field_values);
		}
	}










	/**********************************
	 *
	 * Remove static file
	 *
	 **********************************/

	/**
	 * Remove file ID from filesystem and database registration
	 *
	 * @param	integer		$filepath_hash File registration ID (hash of filepath)
	 * @return	string		False if OK, otherwise error string
	 */
	function remove_fileId($filepath_hash)	{

			// Get record for file:
		list($rec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'tx_staticpub_pages',
					'filepath_hash='.intval($filepath_hash)
				);

			// If found, delete:
		if (is_array($rec))	{

				// Remove the file from file system:
			$err = $this->removeFile($rec['filepath']);
			if ($err)	{
				return $err;
			} else {

					// Delete record now:
				$GLOBALS['TYPO3_DB']->exec_DELETEquery(
						'tx_staticpub_pages',
						'filepath_hash='.intval($filepath_hash)
					);
			}
		}
	}

	/**
	 * Removes all files registered for a certain page ID
	 *
	 * @param	integer		$page_id Page ID
	 * @return	string		False if OK, otherwise error string
	 */
	function remove_filesFromPageId($page_id)	{
		$filesOnPage =   $this->getRecordForPageID($page_id);

		foreach($filesOnPage as $frec)	{
			$this->remove_fileId($frec['filepath_hash']);
		}
	}

	/**
	 * Removes a single file from the file systems publishing directory
	 * Removes directories also if empty
	 *
	 * @param	string		$filePath File path, relative to publishing directory
	 * @return	string		False if OK, otherwise error string
	 */
	function removeFile($filePath)	{

			// Initialize:
		$pubDir = $this->getPublishDir();
		$file = GeneralUtility::getFileAbsFileName($pubDir.$filePath);

		if ($pubDir)	{

				// DELETE file if present:
			if (@is_file($file) && $this->finalIntegrityCheck($file, $pubDir))	{
				unlink($file);
			}

				// Check if it is gone...:
			if (@is_file($file))	{
				return 'File "'.$filePath.'" still exists!';
			} else {
				$this->removeDirRecursively($file, $pubDir);
			}
		} else return 'No publishing dir!';
	}

	/**
	 * Remove directory recursively backwards until not possible anymore.
	 *
	 * @param	string		$file Filename / Directory name
	 * @param	string		$pubDir Publishing directory (makes sure deletion doesn't happend all the way down...)
	 * @return	void
	 */
	function removeDirRecursively($file, $pubDir)	{
		$fileDir = preg_replace('/\/[^\/]*$/','',$file);
		if (@is_dir($fileDir) && GeneralUtility::isFirstPartOfStr($fileDir, $pubDir) && $this->finalIntegrityCheck($fileDir, $pubDir))	{
			if (@rmdir($fileDir))	{
				$this->removeDirRecursively($fileDir, $pubDir);
			}
		}
	}

	/**
	 * Integrity check of a file/directory before being unlinked/removed. This is making sure the path is valid, absolute path and within the publishing directory!
	 *
	 * @param	string		$fileOrDirToDelete File/Directory to delete!
	 * @param	string		$pubDir Publishing directory
	 * @return	boolean		Returns TRUE if OK, otherwise it DIES! (because it really shouldn't fail! You should have checked it all on beforehand! This is only an emergency brake against your bad coding...)
	 */
	function finalIntegrityCheck($fileOrDirToDelete, $pubDir)	{
		if ($fileOrDirToDelete === GeneralUtility::getFileAbsFileName($fileOrDirToDelete) && $pubDir && GeneralUtility::isFirstPartOfStr($fileOrDirToDelete, $pubDir))	{
			return TRUE;
		} else die('INTEGRITY CHECK on "'.$fileOrDirToDelete.'" FAILED!');
	}









	/**********************************
	 *
	 * Various SQL stuff
	 *
	 **********************************/

	/**
	 * Returns record for a file, if any
	 *
	 * @param	string		$filepath Filepath relative to publishing directory
	 * @return	array		Returns array IF there was a record.
	 */
	function getRecordForFile($filepath)	{
		list($rec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'tx_staticpub_pages',
					'filepath_hash='.GeneralUtility::md5int($filepath)
				);
		return $rec;
	}

	/**
	 * Returns records for a page id
	 *
	 * @param	integer		$page_id Page id
	 * @return	array		Array of records
	 */
	function getRecordForPageID($page_id)	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'tx_staticpub_pages',
					'page_id='.intval($page_id)
				);
	}
	/**
	 * @param string $publishDir default publishdir
	 * @param array $options
	 * @return string
	 */
	private function getPublishDirForResources($publishDir,array $options){
		if(isset($options['publishDirForResources'])){
			$publishDir = $options['publishDirForResources'];
			$publishDir = GeneralUtility::getFileAbsFileName($publishDir);
			if (substr($publishDir,-1)!='/')	{
				$publishDir.='/';
			}
		}

		if(FALSE === $this->autoCreatePublishDir($publishDir)){
			$GLOBALS['TSFE']->applicationData['tx_crawler']['log'][] = 'EXT:staticpub getPublishDirForResources - could no create publishdir '.$publishDir;
		}
		return $publishDir;
	}
	/**
	 * @param string $pubDirAbs
	 * @return boolean
	 */
	private function autoCreatePublishDir($pubDirAbs){
		if(FALSE === is_dir($pubDirAbs)){
			if (FALSE === mkdir($pubDirAbs, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']),TRUE)){
				return FALSE;
			}
			GeneralUtility::fixPermissions($pubDirAbs);
		}
		return TRUE;
	}
}
