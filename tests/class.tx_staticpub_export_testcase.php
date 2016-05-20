<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009 AOE GmbH <dev@aoe.com>
 * All rights reserved
 *
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once (dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'class.tx_staticpub_export.php');
/**
 * Test for class tx_staticpub_export
 * @package static_pub
 */
class tx_staticpub_export_testcase extends Tx_Phpunit_TestCase {
	/**
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;
	/**
	 * @var tx_staticpub_export
	 */
	private $tx_staticpub_export;
	/**
	 * @var string
	 */
	private $pubDir;
	/**
	 * prepare the test
	 *
	 * no need to mkdir on $this->pubDir because staticpub
	 * will create this folder by its own in tx_staticpub_export::autoCreateTarget
	 */
	protected function setUp() {
		$this->tx_staticpub_export = new tx_staticpub_export ();
		$tempPath = realpath ( dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'typo3temp' );
		$this->pubDir = $tempPath . DIRECTORY_SEPARATOR . uniqid ( 'testPubDir' );
	}
	/**
	 * test the method exportContent
	 * @test
	 * @expectedException RuntimeException
	 */
	public function exportContentWithEmptyFolders() {
		$this->tx_staticpub_export->exportContent ( '' );
	}
	/**
	 * test the method exportContent
	 * @test
	 * @expectedException RuntimeException
	 */
	public function exportContentWithInvalidSourceFolder() {
		$this->tx_staticpub_export->exportContent ( 'aassadad' . tx_staticpub_export::TARGET_SEPERATOR . 'sdsdds' . tx_staticpub_export::FOLDER_SEPERATOR );
	}
	/**
	 * test the method exportContent
	 * @test
	 */
	public function exportContent() {
		$source = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
		$this->tx_staticpub_export->exportContent ( $source . tx_staticpub_export::TARGET_SEPERATOR . $this->pubDir . tx_staticpub_export::FOLDER_SEPERATOR );
		$this->assertFileExists ( $this->pubDir . DIRECTORY_SEPARATOR . 'welt.html', 'file not created' );
	}
	/**
	 * clean up after test
	 */
	protected function tearDown() {
		unset ( $this->tx_staticpub_export );
		GeneralUtility::rmdir ( $this->pubDir, TRUE );
	}
}
