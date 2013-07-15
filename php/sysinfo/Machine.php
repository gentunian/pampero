<?php

	/**
	*
	*/
	class Machine {
		private $sysinfo = NULL;
		private $credentials = NULL;

		public function __construct( $hostname, $credProv ) {
			// Get credentials from a credentials provider
			$this->credentials = $credProv->getCredentials( $hostname );

			$systype = Utils::getSystemType( $hostname );
			$class = $systype."SystemInfo";
			$this->sysinfo = new $class( $hostname, $credProv );
		}
	
		public function getSystemInfo() {
			return $this->sysinfo;
		}

		public function getCredentials() {
			return $this->credentials;
		}
	}

?>