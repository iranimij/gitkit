<?php

namespace Gitkit;

use Gitkit\Git_Providers\Git_Provider_Factory;

defined( 'ABSPATH' ) || die();

class Plugin {

	/**
	 * Gitkit main class
	 *
	 * @since NEXT
	 */
	public function __construct() {
        add_action( 'wp_ajax_gitkit_download_and_upload_plugin', [ $this, 'upload_and_download_plugin']);
		add_action( 'install_plugins_upload', [ $this, 'add_upload_form_wrapper'] );
	}

	/**
     * Adds upload form wrapper.
     *
	 * @return void
	 */
    public function add_upload_form_wrapper() {
	    ?>
        <div id="gitkit-upload-plugin"></div>
	    <?php
    }

	/**
     * Uploads and downloads plugin
     *
	 * @return void
	 */
    public function upload_and_download_plugin() {
        $git_provider_factory = new Git_Provider_Factory();

	    $git_provider = $git_provider_factory->create_git_provider( 'github' );

        $repository_url = filter_input( INPUT_POST, 'gitkit_github_repository_url', FILTER_SANITIZE_URL );
        $branch_name    = filter_input( INPUT_POST, 'gitkit_github_branch_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

	    $result = $git_provider->download_repository( sanitize_url( $repository_url ), sanitize_text_field( $branch_name ) );

        if ( ! is_wp_error( $result ) ) {
            wp_send_json_success( __( 'The operation was successful', 'gitkit') );
        }

        wp_send_json_error( $result->get_error_message() );
    }
}

new Plugin;