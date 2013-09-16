<?php

/**
* SystemInfo abstract class:
* --------------------------
*
* SystemInfo provides methods for retrieve system information. Implementations should
* provide, if possible, the information regarding a specific host.
*
* NOTE: Implementations that are unable to retrieve some information should return "Unknown" for the
* required info.
*/
abstract class SystemInfo {

	private $hostname;

	/**
	* Gets the host name from this object is getting system information.
	* @return The host name this SystemInfo object is providing information for.
	*/
	abstract function getHostname();

	/**
	* @return The domain, if any, where the host named $this->getHostname() belongs.
	*/
	abstract function getDomain();

	/**
	* @return The operating system name 
	*/
	abstract function getOSName();

	/**
	* @return The operating system version
	*/
	abstract function getOSVersion();

	/**
	* @return The IP from this hostname
	*/
	function getIP() {
		return gethostbyname($this->hostname);
	}

	/**
	* Constructor.
	*/
	public function __construct($hostname) {
		$this->hostname = $hostname;
	}

	/**
	* Implementations should follow a single rule. 32 bits architectures should be named
	* "i686" and 64 bits "x86_64".
	* @return The operating system based architecture. Read above
	*/
	abstract function getOSArchitecture();

	public function toString() {
		$output = "Host name: " . $this->getHostname() . "\n";
		$output .= "Domain: " . $this->getDomain() . "\n";
		$output .= "OS name: " . $this->getOSName() . "\n";
		$output .= "OS version: " . $this->getOSVersion() . "\n";
		$output .= "OS architecture: " . $this->getOSArchitecture() . "\n";
		return $output;
	}

	public function toJSON() {
		$output = "{";
		$output .= "hostname: " . $this->getHostname() . ",";
		$output .= "ip: " . $this->getIP() . ",";
		$output .= "domain: " . $this->getDomain() . ",";
		$output .= "osname: " . $this->getOSName() . ",";
		$output .= "osversion: " . $this->getOSVersion() . ",";
		$output .= "osarch: " . $this->getOSArchitecture() . ",";
		$output .= "}";
		return $output;
	}
}

?>