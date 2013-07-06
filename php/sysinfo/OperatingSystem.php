<?php

	/**
	*
	*/
	class OperatingSystem {
		private $name;
		private $version;
		private $arch;

		function __construct( $name, $version, $arch ) {
			$this->name = $name;
			$this->version = $version;
			$this->arch = $arch;
		}
		function getName() {
			return $this->name;
		}
		function getVersion() {
			return $this->version;
		}
		function getArchitecture() {
			return $this->arch;
		}
	}
	
?>