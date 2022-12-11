<?php

namespace Gitkit\Git_Providers;

defined( 'ABSPATH' ) || die();

interface Git_Provider_Interface {
	public function download_repository( $repository_link, $branch_name );
}