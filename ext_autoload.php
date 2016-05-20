<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('staticpub');
return array(
    'tx_staticpub' => $extensionPath . 'class.tx_staticpub.php',
    'tx_staticpub_export' => $extensionPath . 'class.tx_staticpub_export.php',
	'tx_staticpub_tasks_export' => $extensionPath . 'tasks/class.tx_staticpub_tasks_export.php',
	'tx_staticpub_tasks_export_additionalfieldprovider' => $extensionPath . 'tasks/class.tx_staticpub_tasks_export_AdditionalFieldProvider.php',
	'tx_staticpub_modfunc1' => $extensionPath . 'modfunc1/class.tx_staticpub_modfunc1.php',
);
