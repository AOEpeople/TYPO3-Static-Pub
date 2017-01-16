<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 AOE (dev@aoe.com)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * sheduler task to export static pages
 * @package TYPO3
 * @subpackage tx_staticpub
 */
class tx_staticpub_tasks_export extends AbstractTask {
	/**
	 * @var string
	 */
	public $folders;
	/**
	 * @return boolean	Returns true on successful execution, false on error
     * @throws Exception
	 */
	public function execute() {
		/* @var $export tx_staticpub_export */
		try{
			$export = GeneralUtility::makeInstance('tx_staticpub_export');
			$export->exportContent($this->folders);
			return true;
		}catch (Exception $e){
			GeneralUtility::devLog('staticpub export error: '.$e->getMessage(), 'staticpub',2);
			throw $e;
		}
	}
}
