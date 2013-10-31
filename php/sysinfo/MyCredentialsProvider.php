<?php

/**
* MyCredentialsProvider class:
* ----------------------------
*
*/
class MyCredentialsProvider implements CredentialsProvider
{
	private $contents = "";

	/**
	* Constructor.
	*/
	public function __construct() {
		// If the file doesn't exists, do nothing
		if ( file_exists( PASSWORD_FILE ))
		{

			// Get the file content for later processing
			$this->contents = file_get_contents( PASSWORD_FILE );

		} else throw new Exception("Password file " . PASSWORD_FILE . " not found.");
	}

	/**
	*
	* @param hostname
	* @return Credentials object
	*/
	public function getCredentials( $hostname )
	{
		$pwd = "P4ssw0rD";
		$adm = "4dm1n";

		if ( preg_match( "/^$hostname=(.*)$/ixm", $this->contents, $match ) == 1 ) {
			$pwd = $match[1];
		}

		$sysType = Utils::getSystemType( $hostname );

		if ( $sysType == OS_WINDOWS_FAMILY ) {
			$adm = NT_ADMIN;
		}

		return new Credential( $adm, $pwd );
	}
}
?>