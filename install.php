<?php
// No direct access.
defined ( '_JEXEC' ) or die ();

jimport ( 'joomla.filesystem.file' );
jimport ( 'joomla.filesystem.folder' );
class plgJshoppingMailerLiteInstallerScript {
	function install(&$mod) {
		$uri = JUri::getInstance ();
		$src = dirname ( __FILE__ ) . '/libraries';
		$dest = JPATH_LIBRARIES;
		
		if (! JFolder::exists ( $dest ))
			return false;
		
		if (JFolder::exists ( $src )) {
			foreach ( JFolder::folders ( $src ) as $f ) {
				JFolder::copy ( $src . '/' . $f, $dest . '/' . $f, '', true );
			}
		}

		return true;
	}

	function update(&$mod) {
		$this->install ( $mod );
	}
}
