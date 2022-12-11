<?php

namespace Gitkit;

defined( 'ABSPATH' ) || die();

class Downloader extends \WP_Background_Process  {

	/**
	 * @var string
	 */
	protected $action = 'gitkit_download_process';

	protected function task( $data ) {
		wp_options_manager()->update('iman','test33')->save();
		$myfile = fopen( gitkit()->plugin_dir() . "../check24.zip", "w" ) or die( "Unable to open file!" );
		fwrite( $myfile, 'fff' );
		fclose( $myfile );

		return false;
	}


	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
				wp_options_manager()->update('iman','test33')->save();
		wp_options_manager()->update('iman','test33');

	}
}