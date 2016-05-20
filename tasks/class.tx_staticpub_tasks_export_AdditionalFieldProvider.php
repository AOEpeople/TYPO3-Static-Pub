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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Field provider to configure the sheduler task
 * @package TYPO3
 * @subpackage tx_ncstaticfilecache
 */
class tx_staticpub_tasks_export_AdditionalFieldProvider implements AdditionalFieldProviderInterface {
	/**
	 * This method is used to define new fields for adding or editing a task
	 * In this case, it adds an email field
	 *
	 * @param array &$taskInfo  reference to the array containing the info used in the add/edit form
	 * @param object $task when editing, reference to the current task object. Null when adding.
	 * @param SchedulerModuleController $schedulerModule reference to the calling object (Scheduler's BE module)
	 * @return array
	 */
	public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule) {
		$additionalFields = array();
		if (empty($taskInfo['folders'])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo['folders'] = '';
			} else {
 				$taskInfo['folders'] = $task->folders;
			}
		}
		$fieldID = 'folders';
		$fieldCode  = '<textarea name="tx_scheduler[folders]" id="folders" cols="50" rows="10">'.$taskInfo['folders'].'</textarea>';
		
		$additionalFields[$fieldID] = array(
				'code' => $fieldCode,
				'label' => 'LLL:EXT:staticpub/locallang_db.php:staticpub_tasks_export.folders',
		);
		return $additionalFields;
	}

	/**
	 * Validates the additional fields' values
	 * 
	 * @param array &$submittedData
	 * @param SchedulerModuleController $schedulerModule
	 * @return boolean
	 */
	public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule) {
		if ( !empty($submittedData['folders']) ) {
			return true;
		} else {
			$schedulerModule->addMessage('Please define the folders to export!', FlashMessage::ERROR);
			return false;
		}
	}
	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param	array $submittedData
	 * @param	AbstractTask $task
	 */
	public function saveAdditionalFields(array $submittedData, AbstractTask $task) {
		$task->folders = $submittedData['folders'];
	}

}
