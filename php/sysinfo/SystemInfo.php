<?php

/**
* SystemInfo interface:
* ---------------------
*
* SystemInfo interface provides methods for retrieve system information. Implementations should
* provide, if possible, the information regarding a specific host.
*
* NOTE: Implementations that are unable to retrieve some information should return "Unknown" for the
* required info.
*/
interface SystemInfo {

	/**
	* Gets the host name from this object is getting system information.
	* @return The host name this SystemInfo object is providing information for.
	*/
	function getHostname();

	/**
	* @return The domain, if any, where the host named $this->getHostname() belongs.
	*/
	function getDomain();

	/**
	* @return The operating system name 
	*/
	function getOSName();

	/**
	* @return The operating system version
	*/
	function getOSVersion();

	/**
	* Implementations should follow a single rule. 32 bits architectures should be named
	* "i686" and 64 bits "x86_64".
	* @return The operating system based architecture. Read above
	*/
	function getOSArchitecture();
}

?>