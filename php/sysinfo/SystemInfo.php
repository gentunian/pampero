<?php

	/**
	*
	*/
	interface SystemInfo {
		
		// Returns the hostname without the domain
		function getHostname();

		// Returns the domain if any
		function getDomain();

		// Returns the operating system name
		function getOSName();

		//
		function getOSVersion();

		// Returns the OS architecture. Possible values:
		//   . "i686" 
		//   . "x86_64"
		function getOSArchitecture();
	}

?>