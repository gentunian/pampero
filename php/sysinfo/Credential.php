<?php

	/**
	*
	*/
	final class Credential {
		private $user = "";
		private $pwd = "";

		public function __construct( $user, $pwd ) {
			$this->user = $user;
			$this->pwd = $pwd;
		}

		public function getAdminUser() {
			return $this->user;
		}

		public function getAdminPassword() {
			return $this->pwd;
		}
	}

?>