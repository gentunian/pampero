<?php
	
	//
	//
	if ( !defined( 'FROM_PACKAGES' )) {
		die( "Should be invoked by packages.php" );
	}


	/**
	*
	*/
	function do_install( $args ) {
		if (! somethingToSearchFor( $args ))
			return array();

		// session_start();
		Settings::my_session_( "start" );

		// Include list.php in order to retrieve a list of available
		// packages based on search arguments
		require_once( "list.php" );

		// Save the target in the SESSION data
		$_SESSION['target'] = $args['target'];

		// Retrive the array list of packages based on arguments from list.php module
		// do_list() returns an array based data.
		$installData = do_list( $args );

		// Create an instance of InstallerCreator in order to retrieve
		// an installer based on package data.
		$installerCreator = new InstallerCreator();

		// The resulting array with data about the installation process
		$result = array();

		// Store the array that defines our progress per session
		$_SESSION['data'] = array();
		Settings::my_session_( "write_close" );

		foreach( $installData /*json_decode( $installJSON, true )*/ as $key => $value ) {
			// Get an installer instance for this package data.
			$installer = $installerCreator->getInstaller( $value['os'] );

			// Retrieve the target object based on hostname. This object should contain
			// target credentials
			$target = Settings::getTarget( $args['target'] );

			// Run the installation in target
			$result[ $value['id'] ] = $installer->install( $value['installer'], $value['installerArgs'], $target );

			Settings::my_session_ ( "start" );
			$_SESSION['data'][] = getDataArray(
				$value['id'],
				//count( $result ) - 1,
				$result[ $value[ 'id' ]]->getExitString(),
				$result[ $value[ 'id' ]]->getExitCode(),
				$result[ $value[ 'id' ]]->getStderr()
				);
			Settings::my_session_( "write_close" );
		}

		// Improve this?
		return $_SESSION['data'];
	}

	/**
	*
	*/
	function somethingToSearchFor( $args ) {
		if ( is_array( $args ) ) {
			return ( isset( $args['id'] ) ||
				isset( $args['arch'] ) ||
				isset( $args['os'] ) ||
				isset( $args['description'] ) ||
				isset( $args['installer'] ) ||
				isset( $args['installerArgs'] ) ||
				isset( $args['name'] ));
		}
		return false;
	}

	/**
	*
	*/
	function getDataArray( $id, $status, $exitCode, $stderr ) {
		return array(
			"id" => $id,
			"status" => $status,
			"exitCode" =>$exitCode,
			"stderr" => $stderr
			);
	}

	/**
	*
	*/
	function do_install_output( $result, $args ) {
		$output = "";
		$outputType = strtolower( $args['output'] );

		// Encode JSON if plain json is desired
		if ( $outputType == "jsonplain" ) {
			$output = json_encode( $result );
		}

	    // Encode JSON  with pretty print if html json is desired
	    // and add extra <pre> tags.
		else if ( $outputType == "jsonhtml" ) {
			$output = "<pre>" . json_encode( $result, JSON_PRETTY_PRINT ) . "</pre>";
		}

		// The options left are html or console. HTML only adds pre tags
		else {
			$output = sprintf("\nSumario:\n%s\n", str_repeat("-", strlen("sumario")));
			$okCount = 0;
			foreach( $result as $key => $value) {
				$output .= sprintf("\t%s:\n\t%s\n\t* %s\n\n", $value['id'], str_repeat("-", strlen( $value['id'] )), $value['status']);
				if ( $value['exitCode'] == 0 )
					$okCount++;
			}
			$output .= sprintf("\nSe instalaron %d programas correctamente de un total de %d.\n", $okCount, count( $result ));

			if ( $outputType == "html" ) {
				$output = "<pre>".$output."</pre>";

			} else if ( $args['output'] == "console" ) {

			}
		}

		return $output;
	}

	/**
	*
	*/
	class CommandResult {
		protected $stdout = NULL;
		protected $stderr = NULL;
		protected $exitCode = NULL;
		protected $exitString = NULL;

		public function __construct( $stdout, $stderr, $exitCode, $exitString ) {
			$this->stdout = $stdout;
			$this->stderr = $stderr;
			$this->exitCode = $exitCode;
			$this->exitString = $exitString;
		}

		function getStdout() {
			return $this->stdout;
		}

		function getStderr() {
			return $this->stderr;
		}

		function getExitCode() {
			return $this->exitCode;
		}

		function getExitString() {
			return $this->exitString;
		}
	}

	

	/**
	*
	*/
	interface RemoteInstaller {

		public function getHostOS();

		public function getTargetOS();

		public function install( $command, $args, $target );
		
	}

	/**
	*
	*/
	class InstallerCreator {

		private $installers = NULL;

		function getInstaller( $os ) {
			if ( is_array( $os )) 
				$os = implode( ',', $os );

			if ( stripos( $os, "win") !== false ) {
				$os = OS_WINDOWS;
			}

			if ( !isset( $installers[$os] )) {
				$installers[$os] = $this->createInstallerForOS( $os );
			}

			return $installers[ $os ];
		}

		function createInstallerForOS( $os ) {
			$installer = constant( strtoupper(NATIVE_OS . '_' . $os . '_INSTALLER' ) );
			if ( $installer != NULL ) {
				$installer = new $installer();
			}
			return $installer;
		}
	}

	/**
	*
	*/
	function __autoload( $class_name ) {
		require_once( 'installers/' . $class_name . '.php');
	}

?>