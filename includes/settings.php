<?php

namespace Gitkit;

defined( 'ABSPATH' ) || die();

class Settings {

	/**
	 * AIU_Image_Uploader constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_gitkit_save_settings', [ $this, 'save_settings' ] );
	}

	public function save_settings() {
		wp_verify_nonce( 'gitkit','nonce' );

		$github_access_token = filter_input( INPUT_POST, 'gitkit_github_access_token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		wp_options_manager()->update( 'gitkit_github_access_token', sanitize_text_field( $github_access_token ) )->save();

		wp_send_json_success( __( 'The data has been saved.', 'gitkit' ) );
	}
}

new Settings();