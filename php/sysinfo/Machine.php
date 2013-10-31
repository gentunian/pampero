<?php

/**
* Machine class:
* --------------
*/
class Machine {
	private $sysinfo = NULL;
	private $credentials = NULL;

	public function __construct( $hostname ) {
		if ( $hostname == NULL || !is_string( $hostname ))
			throw new Exception( "$hostname argument must be a non-null string." );

		$credProv = new MyCredentialsProvider();
		// Get credentials from a credentials provider
		$this->credentials = $credProv->getCredentials( $hostname );

		$systype = Utils::getSystemType( $hostname );
		$class = $systype."SystemInfo";
		if (class_exists($class)) {
			$this->sysinfo = new $class( $hostname, $this->credentials );
		} else {
			$this->sysinfo = new UnknowSystemInfo();
		}
	}
	
	public function getSystemInfo() {
		return $this->sysinfo;
	}

	public function getCredentials() {
		return $this->credentials;
	}

}

/**
* UnknowSystemInfo class:
* -----------------------
* 
*/
class UnknowSystemInfo {
	public function getHostname() {
		return $this->hostname;
	}
	public function getDomain() {
		return UNKNOWN;
	}
	public function getOSVersion() {
		return UNKNOWN;
	}
	public function getOSName() {
		return UNKNOWN;
	}

	public function getOSArchitecture() {
		return UNKNOWN;
	}
}

?>