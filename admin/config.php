<?php

	class Settings {

		// Instance variable will hold this class object
		const INI_FILE = 'config.ini';
		private static $instance = NULL;
		private $iniContents = NULL;

		/**
		* Prevent consctructor calling
		*/
		final private function __construct() {
			if (! file_exists( self::$INI_FILE )) {

			}
			$this->iniContents = parse_ini_file( self::$INI_FILE, true );
			define( 'UNIX_ADMIN', $this->iniContents['General']['UNIX_ADMIN'] );
			define( 'NT_ADMIN', $this->iniContents['General']['WINDOWS_ADMIN'] );
			define( 'NATIVE_OS', strtoupper(explode( ' ', php_uname(), 2)[0]) );
			define( 'OS_UNKNOWN', 'Unknown' );
			define( 'OS_WINDOWS', 'Windows' );
			define( 'OS_ARCH_X86', $this->iniContents['General']['OS_ARCH_X86'] );
			define( 'OS_ARCH_X86_64', $this->iniContents['General']['OS_ARCH_X86_64'] );
			define( 'MANIFEST_FILENAME', $this->iniContents['General']['MANIFEST_FILENAME'] );
			define( 'PASSWORD_FILE', $this->iniContents['General']['PASSWORD_FILE'] );
			define( 'PACKAGES_DIR', $this->iniContents['General']['PACKAGES_DIR'] );
			define( 'TMP_DIR', $this->iniContents['General']['TMP_DIR'] );
			define( 'WINDOWS_WINDOWS_INSTALLER', $this->iniContents['Installers']['WINDOWS_WINDOWS'] );
		}

		/**
		* Prevent object cloning
		*/
		final private function __clone() {}

		/**
		*
		*/
		final public static function getInstance() {
			if ( self::$instance == NULL )
				self::$instance = new Settings();

			return self::$instance;
		}

		/**
		*
		*/
		private function getAdminUsername( $host ) {
			// TODO: implement
			return "Administrador";
		}

		private function getHostPassword( $host ) {
			// TODO: implement
			return "Pa55w0rd";
		}

		public function getTarget( $host ) {
			$adminUser = self::getHostPassword( $host );
			$adminPwd = self::getAdminUsername( $host );
			return new Target( $adminUser, $adminPwd, $host );
		}

		public static function my_session_( $sufix, $args = NULL ) {
			if (! isCommandLineInterface() )
				call_user_func( "session_$sufix", $args );
		}

		public static function isCommandLineInterface() {
			$str = php_sapi_name();
			return ( stripos( $str, "cli" ) !== FALSE || stripos( $str, "cgi" ) !== FALSE );
		}

		/**
		*
		*/
		public static function getInvokingHostname( $args, $removeDomain = true ) {
			$hostname = "";
			if ( isset( $_SERVER['REMOTE_ADDR'] ))
				$hostname = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );

			if ( $hostname == "" ) {
				if ( is_array( $args ) && isset( $args['target'] ))
					$hostname = $args['target'];
				else
					$hostname = gethostname();
			}

			if ( $hostname && $removeDomain ) {
				$hostname = self::removeDomainFromHostname( $hostname );
			}

			return $hostname;
		}

		/**
		*
		*/
		private static function removeDomainFromHostname( $host ) {
			return preg_replace( "/([^.]*).*/", "$1", $host);
		}
	}

	/**
	*
	*/
	class Target {
		private $user;
		private $password;
		private $machine;

		public function __construct( $user, $password, $machine ) {
			$this->user = $user;
			$this->password = $password;
			$this->machine = $machine;
		}

		public function getUser() {
			return $this->user;
		}

		public function getPassword() {
			return $this->password;
		}

		public function getMachineName() {
			return $this->machine;
		}
	}
?>