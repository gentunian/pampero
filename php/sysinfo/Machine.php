<?php

	/**
	*
	*/
	class Machine {
		private $sysinfo = NULL;
		private $credentials = NULL;

		public function __construct( $hostname, $credProv ) {
			$systype = Utils::getSystemType( $hostname );
			//$systype = "Windows";
			$class = $systype."SystemInfo";
			$this->sysinfo = new $class( $hostname );

			// Get credentials from a credentials provider
			$this->credentials = $credProv->getCredentials( $hostname );
		}
	
		public function getSystemInfo() {
			return $this->sysinfo;
		}

		public function getCredentials() {
			return $this->credentials;
		}
	}

?>