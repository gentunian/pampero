<?php
	define( 'PASSWORD_FILE', __DIR__ . '/pwd' );

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
		$pwd = "pa55w0rd";

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