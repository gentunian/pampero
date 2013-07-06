<?php

	/**
	*
	*/
	class MyCredentialsProvider implements CredentialsProvider {
		private $contents = "";

		public function __construct() {
			// If the file doesn't exists, do nothing
			if ( file_exists( PASSWORD_FILE )) {

				// Get the file content for later processing
				$this->contents = file_get_contents( PASSWORD_FILE );

			} else throw new Exception("Password file ".PASSWORD_FILE." not found.");
		}

		public function getCredentials( $hostname ) {
			$pwd = "P4ssw0rD";
			$adm = "";

			if ( preg_match( "/^$hostname=(.*)$/ixm", $this->contents, $match ) == 1 ) {
				$pwd = $match[1];
			}

			// TODO: fix localization and portability
			return new Credential( "Administrador", $pwd );

		}
	}
?>