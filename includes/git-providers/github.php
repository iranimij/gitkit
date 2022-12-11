<?php

namespace Gitkit\Git_Providers;

use Gitkit\Downloader;

defined( 'ABSPATH' ) || die();

class Github implements Git_Provider_Interface {

	/**
	 * @param string $repository_link Repo link
	 * @param string $branch_name Branch name.
	 */
	public function download_repository( $repository_link, $branch_name ) {
		$repository_data = $this->get_repository_data( $repository_link );

		if ( is_wp_error( $repository_data ) ) {
			wp_send_json_error( $repository_data->get_error_message() );
		}

		$github_api = wp_options_manager()->select( 'gitkit_github_access_token' );

		$args = [];

		if ( ! empty( $github_api ) ) {
			$args = [
				'headers' => [
					'Authorization' => 'Bearer ' . $github_api,
				],
			];
		}

		require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';

		WP_Filesystem();

		$skin = new \WP_Upgrader_Skin();

		$upgrader_class = new \WP_Upgrader( $skin );

		$download = $this->download_package( 'https://api.github.com/repos/' . $repository_data['repository_owner'] . '/' . $repository_data['repository_name'] . '/zipball/' . $branch_name, $args );

		if ( is_wp_error( $download ) ) {
			return $download;
		}

		$skin->set_upgrader( $upgrader_class );

		$skin->upgrader->strings['unpack_package']     = '';
		$skin->upgrader->strings['installing_package'] = '';
		$skin->upgrader->strings['remove_old']         = '';

		$working_dir = $upgrader_class->unpack_package( $download );

		if ( is_wp_error( $working_dir ) ) {
			return $working_dir;
		}

		$result = $upgrader_class->install_package(
			array(
				'source' => $working_dir,
				'destination' => WP_PLUGIN_DIR,
				'clear_destination' => true,
				'clear_working'     => true,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		foreach ( glob($result['destination'] . '/*.php') as $file ) {
			$plugin_data = get_file_data( $file, array( 'Plugin Name', 'Version' ), 'sellkit' );

			if ( ! empty( $plugin_data[ 0 ] ) ) {
				rename( $result['destination'], WP_PLUGIN_DIR . '/' . str_replace( ' ', '-', strtolower( $plugin_data[ 0 ] ) ) );

				$plugin_name = str_replace( ' ', '-', strtolower( $plugin_data[ 0 ] ) );

				// TODO Adding activate automatically feature in future.
//				activate_plugin( $plugin_name .'/' . $plugin_name . '.php' );
			}
		}

		if ( empty( $plugin_name ) ) {
			return new \WP_Error( 'wrong_plugin_repo', __( 'The repository is not a plugin', 'gitkit' ) );
		}

		return true;
	}

	/**
	 * @param string $url URL.
	 * @param array $args Args.
	 * @return array|bool|mixed|string|\WP_Error
	 */
	public function download_package( $url, $args ) {
		// WARNING: The file is not automatically deleted, the script must unlink() the file.
		if ( ! $url ) {
			return new \WP_Error( 'http_no_url', __( 'Invalid URL Provided.' ) );
		}

		$url_path     = parse_url( $url, PHP_URL_PATH );
		$url_filename = '';
		if ( is_string( $url_path ) && '' !== $url_path ) {
			$url_filename = basename( $url_path );
		}

		$tmpfname = wp_tempnam( $url_filename );
		if ( ! $tmpfname ) {
			return new \WP_Error( 'http_no_file', __( 'Could not create temporary file.' ) );
		}

		$new_args = array_merge( $args, [
			'timeout'  => 300,
			'stream'   => true,
			'filename' => $tmpfname,
		] );

		$response = wp_safe_remote_get(
			$url,
			$new_args
		);

		if ( is_wp_error( $response ) ) {
			unlink( $tmpfname );
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			$data = array(
				'code' => $response_code,
			);

			// Retrieve a sample of the response body for debugging purposes.
			$tmpf = fopen( $tmpfname, 'rb' );

			if ( $tmpf ) {
				/**
				 * Filters the maximum error response body size in `download_url()`.
				 *
				 * @since 5.1.0
				 *
				 * @see download_url()
				 *
				 * @param int $size The maximum error response body size. Default 1 KB.
				 */
				$response_size = apply_filters( 'download_url_error_max_body_size', KB_IN_BYTES );

				$data['body'] = fread( $tmpf, $response_size );
				fclose( $tmpf );
			}

			unlink( $tmpfname );

			return new \WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ), $data );
		}

		$content_disposition = wp_remote_retrieve_header( $response, 'content-disposition' );

		if ( $content_disposition ) {
			$content_disposition = strtolower( $content_disposition );

			if ( 0 === strpos( $content_disposition, 'attachment; filename=' ) ) {
				$tmpfname_disposition = sanitize_file_name( substr( $content_disposition, 21 ) );
			} else {
				$tmpfname_disposition = '';
			}

			// Potential file name must be valid string.
			if ( $tmpfname_disposition && is_string( $tmpfname_disposition )
				&& ( 0 === validate_file( $tmpfname_disposition ) )
			) {
				$tmpfname_disposition = dirname( $tmpfname ) . '/' . $tmpfname_disposition;

				if ( rename( $tmpfname, $tmpfname_disposition ) ) {
					$tmpfname = $tmpfname_disposition;
				}

				if ( ( $tmpfname !== $tmpfname_disposition ) && file_exists( $tmpfname_disposition ) ) {
					unlink( $tmpfname_disposition );
				}
			}
		}

		$content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );

		if ( $content_md5 ) {
			$md5_check = verify_file_md5( $tmpfname, $content_md5 );

			if ( is_wp_error( $md5_check ) ) {
				unlink( $tmpfname );
				return $md5_check;
			}
		}

		return $tmpfname;
	}

	/**
	 * Returns repository data.
	 *
	 * @param string $repository_url Repo url.
	 */
	public function get_repository_data( $repository_url ) {
		$exploded_repository_url = explode( '/', $repository_url );

		if ( empty( $exploded_repository_url[ 3 ] ) || empty( $exploded_repository_url[ 4 ] ) ) {
			return new \WP_Error( 'wrong_github_repo_url', __( "The url is not a github repo.", "gitkit" ) );
		}

		return [
			'repository_owner' => $exploded_repository_url[ 3 ],
			'repository_name' => $exploded_repository_url[ 4 ],
		];
	}
}