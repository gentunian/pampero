<?php

/**
*
*/
class Machine {
	private $sysinfo = NULL;
	private $credentials = NULL;

	public function __construct( $hostname ) {
		$credProv = new MyCredentialsProvider();
		// Get credentials from a credentials provider
		$this->credentials = $credProv->getCredentials( $hostname );

		$systype = Utils::getSystemType( $hostname );
		$class = $systype."SystemInfo";
		$this->sysinfo = new $class( $hostname, $this->credentials );
	}
	
	public function getSystemInfo() {
		return $this->sysinfo;
	}

	public function getCredentials() {
		return $this->credentials;
	}
}

?>