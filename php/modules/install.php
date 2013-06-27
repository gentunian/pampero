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
		//session_start();
		my_session_( "start" );

		// Include list.php in order to retrieve a list of available
		// packages based on search arguments
		require_once( "list.php" );

		// Get the host that is invoking the action or the 
		// target machine
		$hostname = getInvokingHostname( $args );
		if ( $hostname == FALSE )
			return "No se pudo realizar la operacion porque no se especificó maquina destino para la instalación.";

		// Save the target in the SESSION data
		$_SESSION['target'] = wrapDomain( $hostname );

		// Save the output argument in order to change it for list.php module
		$outputType = $args['output'];

		// Retrive the array list of packages based on arguments from list.php module
		$installData = do_list( $args );

		// No matter if 'output' option was provided, we already saved that.
		// Override the key in order to always have a JSON list of packages.
		$args = array_merge( $args, array( "output" => "jsonplain" ));

		// Get a json list from list.php module.
		$installJSON = do_list_output( $installData, $args );

		// Create an instance of InstallerCreator in order to retrieve
		// an installer based on package data.
		$installerCreator = new InstallerCreator();

		// The resulting array with data about the installation process
		$result = array();

		// Store the array that defines our progress per session
		$_SESSION['data'] = array();
		//session_write_close();
		my_session_( "write_close" );

		foreach( json_decode( $installJSON, true ) as $key => $value ) {
			// Get an installer instance for this package data.
			$installer = $installerCreator->getInstaller( $value['os'] );

			// Retrieve the target object based on hostname. This object should contain
			// target credentials
			$target = getTarget( $hostname );

			// Run the installation in target
			$result[ $value['id'] ] = $installer->install( $value['installer'], $value['installerArgs'], $target );

			//session_start();
			my_session_ ( "start" );
			$_SESSION['data'][] = getDataArray(
				$value['id'],
				//count( $result ) - 1,
				$result[ $value[ 'id' ]]->getExitString(),
				$result[ $value[ 'id' ]]->getExitCode(),
				$result[ $value[ 'id' ]]->getStderr()
				);
			//session_write_close();
			my_session_( "write_close" );
		}

		// Restore the output option provided.
		$args['output'] = $outputType;

		return $_SESSION['data'];
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
	function getInvokingHostname( $args ) {
		$hostname = FALSE;
		if ( isset( $_SERVER['REMOTE_ADDR'] ))
			$hostname = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );

		if ( $hostname == FALSE) {
			if ( is_array( $args ) && isset( $args['target'] ))
				$hostname = $args['target'];
			else
				$hostname = gethostname();
		}

		return $hostname;
	}

	/**
	*
	*/
	function getTarget( $host ) {

		// TODO: get credentials based on target for any user
		$pwd = getHostPassword( $host );

		$admin = getAdminUsername( $host );
		return new Target( $admin, $pwd, $host );
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
	class Target {
		public $user;
		public $password;
		public $machine;

		public function __construct( $user, $password, $machine ) {
			$this->user = $user;
			$this->password = $password;
			$this->machine = $machine;
		}
	}

	/**
	*
	*/
	interface RemoteInstaller {

		public function getHostOS();

		public function getTargetOS();

		public function install( $command, $args, $target );
		//public function run( $command, $args, $target );
		
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