<?php

namespace Gitkit\Git_Providers;

defined( 'ABSPATH' ) || die();

class Git_Provider_Factory {
	public function create_git_provider( $provider_name ) {
		if ( $provider_name === 'github' ) {
			return new Github();
		}
	}
}