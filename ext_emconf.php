<?php

########################################################################
# Extension Manager/Repository config file for ext "staticpub".
#
# Auto generated 06-09-2010 17:05
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Static Publishing',
	'description' => 'Allows pages to be staticly published as real HTML pages.',
	'category' => 'module',
	'shy' => 0,
	'version' => '1.4.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/staticpub',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj, Daniel Pötzinger-AOE GmbH',
	'author_email' => 'kasper@typo3.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
	       'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:27:{s:22:"class.tx_staticpub.php";s:4:"1da8";s:29:"class.tx_staticpub_export.php";s:4:"4c07";s:25:"class.tx_staticpub_fe.php";s:4:"5da5";s:16:"ext_autoload.php";s:4:"c692";s:21:"ext_conf_template.txt";s:4:"c3ea";s:12:"ext_icon.gif";s:4:"4037";s:17:"ext_localconf.php";s:4:"a360";s:14:"ext_tables.php";s:4:"b804";s:14:"ext_tables.sql";s:4:"e2eb";s:16:"locallang_db.php";s:4:"cc09";s:12:"doc/TODO.txt";s:4:"ea82";s:14:"doc/manual.sxw";s:4:"9b14";s:19:"doc/manuelnotes.txt";s:4:"675b";s:40:"modfunc1/class.tx_staticpub_modfunc1.php";s:4:"4e99";s:22:"modfunc1/locallang.php";s:4:"6aed";s:41:"tasks/class.tx_staticpub_tasks_export.php";s:4:"651a";s:65:"tasks/class.tx_staticpub_tasks_export_AdditionalFieldProvider.php";s:4:"58a9";s:44:"tests/class.tx_staticpub_export_testcase.php";s:4:"45c0";s:37:"tests/class.tx_staticpub_testcase.php";s:4:"6760";s:17:"tests/phpunit.xml";s:4:"d513";s:25:"tests/fixtures/image1.gif";s:4:"4037";s:25:"tests/fixtures/image2.gif";s:4:"4037";s:25:"tests/fixtures/image3.gif";s:4:"4037";s:25:"tests/fixtures/image4.gif";s:4:"4037";s:23:"tests/fixtures/test.css";s:4:"d6d9";s:24:"tests/fixtures/welt.html";s:4:"b266";s:32:"tests/fixtures/welt_mit_css.html";s:4:"e46b";}',
	'suggests' => array(
	),
);
?>
