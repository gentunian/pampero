<?php

	// TODO: Provide fallback option if WMI isn't available try guessing with other tools
	class WindowsSystemInfo {
		private $sysinfo = NULL;
		private $wmi = NULL;

		public function __construct( $hostname, $cred ) {
			try {
				$this->wmi = new WMISystemInfo( $hostname, $cred );
			} catch( Exception $e ) {
				$user = $cred->getAdminUser();
				$password =  $cred->getAdminPassword();
				$this->sysinfo['hostname'] = $hostname;
				$this->sysinfo['domain'] = "Unknown";
				$this->sysinfo['OSName'] = "Unknown";
				$this->sysinfo['OSVersion'] = "Unknown";
				$this->sysinfo['OSArchitecture'] = "Unknown";
				echo $e->getMessage();
			}
		}

		function getHostname() {
			if ( $this->wmi == NULL)
				return $this->sysinfo['hostname'];

			return $this->wmi->getHostname();
		}

		function getDomain() {
			if ( $this->wmi == NULL)
				return $this->sysinfo['domain'];

			return $this->wmi->getDomain();
		}

		function getOSName() {
			if ( $this->wmi == NULL)
				return $this->sysinfo['OSName'];

			return $this->wmi->getOSName();
		}

		function getOSVersion() {
			if ( $this->wmi == NULL)
				return $this->sysinfo['OSVersion'];

			return $this->wmi->getOSVersion();
		}

		function getOSArchitecture() {
			if ( $this->wmi == NULL)
				return $this->sysinfo['OSArchitecture'];

			return $this->wmi->getOSArchitecture();
		}
	}
?>