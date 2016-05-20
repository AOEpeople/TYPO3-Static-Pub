<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009 AOE (dev@aoe.com)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Export static pub content
 *
 * @package TYPO3
 * @subpackage tx_staticpub
 */
class tx_staticpub_export {
	const FOLDER_SEPERATOR = ';';
	const TARGET_SEPERATOR = ':';
	/**
	 * @param string $str_folders
	 * @throws RuntimeException
	 */
	public function exportContent($str_folders) {
		$folders = $this->parseFolders ( $str_folders );
		if (empty ( $folders )) {
			throw new RuntimeException ( 'tx_staticpub_export: invalid folders: ' . $str_folders );
		}
		$this->synchroniseFolders ( $folders );
	}
	/**
	 * @param string $str_folders
	 * @return array
	 */
	private function parseFolders($str_folders) {
		$folders = array ();
		foreach ( explode ( self::FOLDER_SEPERATOR, $str_folders ) as $folder ) {
			if (FALSE !== strpos ( $folder, self::TARGET_SEPERATOR )) {
				list ( $source, $target ) = explode ( self::TARGET_SEPERATOR, $folder );
				$folders [trim ( $source )] = trim ( $target );
			}
		}
		return $folders;
	}
	/**
	 * @param array $folders
	 * @throws RuntimeException
	 */
	private function synchroniseFolders(array $folders) {
		foreach ( $folders as $source => $target ) {
			$source = $this->getRealPath($this->getAbsolutePath ( $source ));
			$target = $this->getAbsolutePath  ( $target );

			if (FALSE === is_dir ( $source )) {
				throw new RuntimeException ( 'invalid source folder: ' . $source );
			}
			if (FALSE === is_dir ( $target )) {
				$this->autoCreateTarget($source,$target);
				$target = $this->getRealPath($target);
			}
			$this->checkPermission ( $source, $target );
			$this->sync ( $source, $target );
		}
	}
	/**
	 * @param string $path
	 */
	private function getAbsolutePath($path){
		if(substr($path,0,1) !== DIRECTORY_SEPARATOR){
			$path = PATH_site . $path;
		}
		return $path;
	}
	/**
	 * @param string $path
	 */
	private function getRealPath($path){
		if(FALSE === $realpath = realpath($path)){
			throw new RuntimeException('invalid path: '.$path);
		}
		return $realpath;
	}
	/**
	 * @param string $source
	 * @param string $target
	 */
	private function autoCreateTarget($source,$target){
		if(FALSE === mkdir($target, 0775, TRUE)){
			throw new RuntimeException ( 'could not ceate dir: ' . $target );
		}
		$perm = $this->getShortFilePerm( $source  );
		if (FALSE === chmod ( $target, octdec ( $perm ) )) {
			throw new RuntimeException ( 'could not chmod file from: ' . $target . ' to ' . $perm );
		}
	}
	/**
	 * @param string $source
	 * @param string $target
	 * @throws RuntimeException
	 */
	private function checkPermission($source, $target) {
		if (FALSE === is_readable ( $source )) {
			throw new RuntimeException ( 'source not is_readable: ' . $source );
		}
		if (FALSE === is_writeable ( $target )) {
			throw new RuntimeException ( 'target not writable: ' . $target );
		}
		if ((int)$this->getShortFilePerm( $source )  > (int) $this->getShortFilePerm( $target )) {
			throw new RuntimeException ( 'source (' . $source . ') and target (' . $target . ') do not have the same file permisons ' );
		}
	}
	/**
	 * Sync two directorys with each other.
	 *
	 * @param string $sourceLocation
	 * @param string $targetLocation
	 * @return string rsync command
	 * @throws RuntimeException
	 */
	private function sync($sourceLocation, $targetLocation) {
		$command = 'rsync --force --omit-dir-times --ignore-errors --archive --partial --perms  --delete';
		if (substr ( $sourceLocation, - 1 ) != DIRECTORY_SEPARATOR) {
			$sourceLocation .= DIRECTORY_SEPARATOR;
		}
		if (substr ( $targetLocation, - 1 ) != DIRECTORY_SEPARATOR) {
			$targetLocation .= DIRECTORY_SEPARATOR;
		}
		$command .= ' ' . $sourceLocation;
		$command .= ' ' . $targetLocation;
		$command = escapeshellcmd ( $command );
		if (FALSE === system ( $command )) {
			throw new RuntimeException ( 'Error on system command execution! Command:' . $command );
		}
	}
	/**
	 * @param string $paths
	 * @return string
	 */
	private function getShortFilePerm($paths){
		return substr ( decoct ( fileperms ( $paths ) ), 2 );
	}

}
