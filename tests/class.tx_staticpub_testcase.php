<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE GmbH <dev@aoe.com>
 *  All rights reserved
 *
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once (dirname(__FILE__) .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR. 'class.tx_staticpub.php');
/**
 * Test for class tx_staticpub
 * @package static_pub
 */
class tx_staticpub_testcase extends Tx_Phpunit_TestCase {
	/**
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;
	/**
	 * @var tx_staticpub
	 */
	private $tx_staticpub;
	/**
	 * @var string
	 */
	private $pubDir;
	/**
	 * prepare the test
	 */
	protected function setUp(){
		$this->tx_staticpub = new tx_staticpub();
		$tempPath = realpath(dirname(__FILE__) .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'typo3temp');
		$this->pubDir = $tempPath.DIRECTORY_SEPARATOR.uniqid('testPubDir');
		GeneralUtility::mkdir($this->pubDir);
	}
	/**
	 * test the method createStaticFile
	 * @test
	 */
	public function createStaticFile(){
		$path = 'hallo/';
		$file = 'welt.html';
		$content = '<html></html>';
		$page_id = 0;
		$pubDir = $this->pubDir.DIRECTORY_SEPARATOR;
		$options = array();
		
		$this->assertNotNull($this->tx_staticpub->createStaticFile($path,$file,$content,$pubDir,$page_id,$options),'suscces message expected');
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$file,'file not created');
	}
	/**
	 * test the method createStaticFile
	 * @test
	 */
	public function createStaticFileWithBasUrlFromUid(){
		$this->tx_staticpub = $this->getMock('tx_staticpub',array('getUrlFromSysDomainUid'),array(),'',FALSE);
		$this->tx_staticpub->expects($this->once())->method('getUrlFromSysDomainUid')->will($this->returnValue('http://www.test.de'));
		$path = 'hallo/';
		$file = 'welt.html';
		$content = '<html><base href="http://www.integration.test.de/" /></html>';
		$page_id = 0;
		$pubDir = $this->pubDir.DIRECTORY_SEPARATOR;
		$options = array();
		$options['sys_domain_base_url'] = 0;
		$this->assertNotNull($this->tx_staticpub->createStaticFile($path,$file,$content,$pubDir,$page_id,$options),'suscces message expected');
		
		$file = $this->pubDir.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$file;
		$this->assertFileExists($file,'file not created');
		$this->assertContains('http://www.test.de', file_get_contents($file) ,'file not created');
	}
	/**
	 * test the method includeResources
	 * @test
	 */
	public function includeResources(){
		$path = 'hallo/';
		$file = 'welt.html';
		$content = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'welt.html');
		$page_id = 0;
		$pubDir = $this->pubDir.DIRECTORY_SEPARATOR;
		$options = array('includeResources'=>TRUE);
		$this->tx_staticpub->createStaticFile($path,$file,$content,$pubDir,$page_id,$options);
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'ext_icon.gif','file not created');
	}
	/**
	 * test the method getPublishDirForResources
	 * @test
	 */
	public function getPublishDirForResources(){
		$path = 'hallo/';
		$file = 'welt.html';
		$content = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'welt.html');
		$page_id = 0;
		$pubDir = $this->pubDir.DIRECTORY_SEPARATOR;
		$publishDirForResources = $pubDir.'res';
		GeneralUtility::mkdir($publishDirForResources);
		$options = array('includeResources'=>TRUE,'publishDirForResources'=>$publishDirForResources.DIRECTORY_SEPARATOR);
		$this->tx_staticpub->createStaticFile($path,$file,$content,$pubDir,$page_id,$options);
		$this->assertFileNotExists($this->pubDir.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'ext_icon.gif','file not created');
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'ext_icon.gif','file not created');
	}
	/**
	 * test method extractResources
	 * @test
	 */
	public function extractResources(){
		$path = 'hallo/';
		$file = 'welt_mit_css.html';
		$content = file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'welt_mit_css.html');
		$page_id = 0;
		$pubDir = $this->pubDir.DIRECTORY_SEPARATOR;
		$publishDirForResources = $pubDir.'res';
		GeneralUtility::mkdir($publishDirForResources);
		$options = array('includeResources'=>TRUE,'publishDirForResources'=>$publishDirForResources.DIRECTORY_SEPARATOR);
		$this->tx_staticpub->createStaticFile($path,$file,$content,$pubDir,$page_id,$options);
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'test.css','file not created');
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'image1.gif','file not created');
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'image2.gif','file not created');
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'image3.gif','file not created');
		$this->assertFileExists($this->pubDir.DIRECTORY_SEPARATOR.'res'.DIRECTORY_SEPARATOR.'typo3conf'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.'staticpub'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'image4.gif','file not created');
	}
	/**
	 * clean up after test
	 */
	protected function tearDown(){
		unset($this->tx_staticpub);
		GeneralUtility::rmdir($this->pubDir,TRUE);
	}
}
