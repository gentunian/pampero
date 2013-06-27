<?php

	define( 'UNIX_ADMIN', 'root' );
	define( 'NT_ADMIN', 'Administrator' ); // localization?

	define( 'NATIVE_OS', strtoupper(explode( ' ', php_uname(), 2)[0]) );

	define( 'OS_UNKNOWN', 'Unknown' );
	define( 'OS_WINDOWS', 'Windows' );
	//define( 'OS_WINDOWS_7', 'Windows 7' );
	//define( 'OS_WINDOWS_XP', 'Windows XP' );

	define( 'OS_ARCH_X86', 'i686' );
	define( 'OS_ARCH_X86_64', 'x86_64' );

	define( 'MANIFEST_FILENAME', 'manifest.json' );

	define( 'PASSWORD_FILE', __DIR__ . '/pwd' );
	define( 'PACKAGES_DIR', __DIR__ . '/packages' );
	define( 'TMP_DIR', __DIR__ . '/tmp' );

	define( 'WINDOWS_WINDOWS_INSTALLER', 'PsexecRemoteInstaller' );



	/**
	* Helper method
	*/
	function my_session_( $sufix, $args = NULL ) {
		if (! isCommandLineInterface() )
			call_user_func( "session_$sufix", $args );
	}


	/**
	*
	**/
	function isCommandLineInterface()
	{
		$str = php_sapi_name();
		return ( stripos( $str, "cli" ) !== FALSE || stripos( $str, "cgi" ) !== FALSE );
	}

	/**
	*
	*/
	function setHostPassword( $host, $password ) {
		// If the file doesn't exists, create it
		if (! file_exists( PASSWORD_FILE ))
			touch( PASSWORD_FILE );

		// Get the file content for later processing
		$contents = file_get_contents( PASSWORD_FILE );
		
		// Remove domain 
		$host = wrapDomain( $host );

		// Does this host already have a password?
		if ( strstr( $contents, $host ) != FALSE ) {
			// If don't, append it
			$contents .= "$host=$password\n";

		} else {
			// If it does, replace it
			$contents = preg_replace( "/^$host=.*$/i", "$host=$password", $contents );
		}

		// Set the new file content
		file_put_contents( PASSWORD_FILE, $contents );
	}

	/**
	*
	*/
	function wrapDomain( $host ) {
		return preg_replace( "/([^.]*).*/", "$1", $host);
	}

	/**
	*
	*/
	function getHostPassword( $host ) {
		$pwd = "Pa55w0rd";

		// If the file doesn't exists, do nothing
		if ( file_exists( PASSWORD_FILE )) {
			// Remove domain 
			$host = wrapDomain( $host );

			// Get the file content for later processing
			$contents = file_get_contents( PASSWORD_FILE );

			if ( preg_match( "/^$host=(.*)$/ixm", $contents, $match ) == 1 )
				$pwd = $match[1];
		}

		return $pwd;		
	}

	/**
	*
	*/
	function getAdminUsername( $host ) {
		// TODO: Implement.
		return "Administrador";
	}
?>