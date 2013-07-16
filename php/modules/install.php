<?php
	
	//
	//
	if ( !defined( 'FROM_PACKAGES' ))
	{
		die( "Should be invoked by packages.php" );
	}


	/**
	*
	*/
	function do_install( $args )
	{
		try {

			// Use session to implement a simple progress meter.
		    // NOTE: This will only works for HTTP/AJAX requests
			Utils::my_session_( "start" );

			// Get options
			$opts = getOptionsFromArgs( $args );
			$target = $opts->getOption( "target" );

			// Get the target machine
			$machine = getTargetMachine( $target);

 		    // We have a target, go on...
			$_SESSION['target'] = $machine->getSystemInfo()->getHostname();

			// $opts->getArgs() ???
	        $installData = getPackageData( $args );

	        // Create an instance of InstallerCreator in order to retrieve
		    // an installer based on package data.
	        $installerCreator = new InstallerCreator();

	    	// The resulting array with data about the installation process
	        $result = array();

		    // Store the array that defines our progress per session
	        $_SESSION['data'] = array();
	        Utils::my_session_( "write_close" );
	        $credProv = new MyCredentialsProvider();

	        foreach( $installData as $key => $value ) {
			    // Get an installer instance for this package data.
	        	$installer = $installerCreator->getInstaller( $value['os'] );

			    // target credentials
	        	$credentials = $credProv->getCredentials( $target );

			    // get the package id, installer file and arguments
	        	$id = $value['id'];
	        	$installerFile = $value['installer'];
	        	$installerArgs = $value['installerArgs'];

			    // Run the installation of $installer for $target
	        	$result[ $id ] = $installer->install( $installerFile, $installerArgs, $target, $credentials );

			    // Start session to get a lock on $_SESSION
	        	Utils::my_session_ ( "start" );

			    // Get the array from CommandResult object
	        	$data = $result[ $id ]->toArray();

			     // Merge the array with $id data
	        	$data = array_merge( array( "id" => $id ), $data );

			    // Remove 'stdout' key, it could contains sensitive data.
			    // Q: Does this imply to much knowledge of CommandResult object ??
	        	unset( $data['stdout'] );

			    // Save data in $_SESSION
	        	$_SESSION['data'][] = $data;

	            // Remove the lock on $_SESSION
	        	Utils::my_session_( "write_close" );
	        }

	        // Improve this?
	        return $_SESSION['data'];

	    } catch( Exception $e ) {

	    	// Exception was thrown. Do not continue.
	    	echo $e->getMessage();

	    	// Die hard 2
	    	die( $e->getMessage() );
	    }
	}

	function getOptionsFromArgs( $args )
	{
		// Parse $args
		$opts = new Options(
			$args,
			array( "output" => Utils::getDefaultOutput(),
				"filter" => array(
					"id" => NULL,
					"name" => NULL,
					"arch" => NULL,
					"os" => NULL,
					"description" => NULL,
					"installer" => NULL,
					"installerArgs" => NULL )
				),
			array( "target" )
			);

		// If no filter option was provided, do not search for packages.
		// Doing so will get a list of all packages and thats not what we
		// want when installing.
		$filter = $opts->getOption( "filter" );
		
		if ( count( $filter ) == 0 ) {
			throw new Exception( "No search criteria provided." );
		}

		return $opts;
	}

	function getTargetMachine( $target )
	{
		// Create machine based on $target and $credProv
		$machine = new Machine( $target );

		return $machine;
	}

	function getPackageData( $args )
	{
        // Include list.php in order to retrieve a list of available
		// packages based on search arguments
		require_once( "list.php" );

		// Retrive the array list of packages based on arguments from list.php module
		// do_list() returns an array based data.
		$installData = do_list( $args );

		return $installData;
	}

	/**
	*
	*/
	function do_install_output( $result, $args )
	{
		$opts = new Options(
			$args,
			array( "output" => Utils::getDefaultOutput() )
			);

		$output = "";
		$outputType = $opts->getOption( "output" );

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
				$output .= sprintf("\t%s:\n\t%s\n\t* %s\n\n", $value['id'], str_repeat("-", strlen( $value['id'] )), $value['exitString']);
				if ( $value['exitCode'] == 0 )
					$okCount++;
			}
			$output .= sprintf("\nSe instalaron %d programas correctamente de un total de %d.\n", $okCount, count( $result ));

			if ( $outputType == "html" ) {
				$output = "<pre>".$output."</pre>";

			} else if ( $output == "console" ) {

			}
		}

		return $output;
	}

	/**
	* A placeholder object for storing command results provided by installers.
	*/
	class CommandResult
	{
		protected $stdout = NULL;
		protected $stderr = NULL;
		protected $exitCode = NULL;
		protected $exitString = NULL;

		/**
		* Constructor.
		*
		* @param stdout The standard output
		* @param stderr The standard error output
		* @param exitCode The exit code
		* @param exitString The exit message
		*
		*/
		public function __construct( $stdout, $stderr, $exitCode, $exitString )
		{
			$this->stdout = $stdout;
			$this->stderr = $stderr;
			$this->exitCode = $exitCode;
			$this->exitString = $exitString;
		}

		function getStdout()
		{
			return $this->stdout;
		}

		function getStderr()
		{
			return $this->stderr;
		}

		function getExitCode()
		{
			return $this->exitCode;
		}

		function getExitString()
		{
			return $this->exitString;
		}

		/**
		* Wraps this object into an array.
		* @return an array representing this object.
		*/
		function toArray()
		{
			return array(
				"exitString" => $this->exitString,
				"exitCode" =>$this->exitCode,
				"stdout" => $this->stdout,
				"stderr" => $this->stderr
				);
		}
	}

	

	/**
	* RemoteInstaller interface
	* -------------------------
	* Interface that installers must implements in order to provide a higher level
	* of abstraction and portability.
	*/
	interface RemoteInstaller
	{

		/**
		* Returns on which Operating System this RemoteInstaller could work.
		* @return The operating system this installer works in.
		*/
		public function getHostOS();

		/**
		* Returns in which operating system this installer is capable of installing applications.
		* @return The operating system this installer will install applications.
		*/
		public function getTargetOS();

		/**
		* Perform an installation defined by command and args into the system described by
		* machine with authentication provided by credentials.
		* @param command The executable to be called by this installer
		* @param args The arguments to be passed to $command
		* @param machine The machine object where to perform the installation
		* @param credentials Credentials that should contain admin username and password in order to grant installation permission
		*/
		public function install( $command, $args, $machine, $credentials );
		
	}

	/**
	* InstallerCreator class
	* ----------------------
	* This class should provide a system abstraction in order to gather and retrieve the correct
	* installer for the system that this toolkit is being run and the system where this toolkit
	* will install a particular package.
	*
	* Packages should contain enough information to provide for which system the package was
	* designed to be installed. In the other hand, NATIVE_OS is the info to retrived to determine
	* which OS the toolkit is running on.
	*
	* Config.ini should have a [Installers] section to specify class installers names for target/host.
	* WINDOWS_WINDOWS=MyWindowsToWindowsInstaller determines that 'MyWindowsToWindowsInstaller' is
	* the name of class implementing RemoteInstaller interface, and, it can install software *from* a
	* Windows OS *to* a Windows OS respectively.
	*
	* TODO: Try to implement fallbacks installers, e.g. WINDOWS_WINDOWS_FALLBACK_1
	*/
	class InstallerCreator
	{

		private $installers = NULL;

		/**
		* Clients will invoke this method with the desired OS name as argument.
		* @param os A string determining from which OS we want an installer to get, e.g. "Windows XP".
		* @return An instance of an installer or null
		*
		* TODO: IMPROVE THIS METHOD
		*/
		function getInstaller($os)
		{
			if ( is_array($os )) 
				$os = implode( ',', $os );

			if ( stripos($os, "win") !== false ) {
				$os = OS_WINDOWS;
			}

			if ( !isset( $installers[$os] )) {
				$installers[$os] = $this->createInstallerForOS( $os );
			}

			return $installers[ $os ];
		}

		// This should be the method that creates the concrete installer
		private function createInstallerForOS( $os )
		{
			$installer = constant( strtoupper(NATIVE_OS . '_' . $os . '_INSTALLER' ) );
			if ( $installer != NULL ) {
				$installer = new $installer();
			}
			return $installer;
		}
	}

	/**
	* FIX: REVIEW THIS AND SPL METHODS IN PACKAGES (CONFLICT??)
	*/
	function __autoload( $class_name )
	{
		require_once( 'installers/' . $class_name . '.php');
	}

?>