<?php
	/**
	* packages.php:
	* -------------
	*
	* php-cgi -f packages.php <module> [ {arg1=value1} ... ]
	*
	* or
	* http://myserver/php/packages.php?command=<modcule>&arg1[]=value1&...
	*/

	// Include config.php
	require_once( __DIR__ . '/../admin/config.php' );
	require_once( __DIR__ . '/sysinfo/sysinfo.php' );

	// Modules shouldn't be called directly. Use this constant
	// to decide inside a module
	define( 'FROM_PACKAGES', 'defined' );
	spl_autoload_register();
	spl_autoload_register( function ( $class ) {
		if ( class_exists( $class ))
			return true;
		$fileName = $class . ".php";
		$it = new RecursiveDirectoryIterator( __DIR__ , FilesystemIterator::SKIP_DOTS );
		foreach( new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::LEAVES_ONLY ) as $file ) {
			if ( $file->getFilename() == $fileName ) {
				require_once( $file );
				return true;
			}
		}
		return false;
	});


	/**
	*
	*/
	class Options {
		private $store = [];
		private $args = NULL;

		public function __construct( $args, $options, $required = NULL ) {
			$this->args = $args;

			if (( $o = $this->hasRequiredOptions( $required )) !== true ) {
				throw new Exception("Required option $o is missing", 1);
			}
				
			// Set only valid arguments
			foreach ($args as $key => $value) {
				if ( array_key_exists( $key, $options ))
					$this->__set( $key, $value );
			}

			$this->store = Utils::array_merge_non_null( $options, $this->store );
		}

		private function hasRequiredOptions( $required ) {
			// If required options has been set, check for existence
			if ( $required != NULL ) {
				foreach ($required as $key => $value) {
					if (! array_key_exists( $value, $this->args ))
						return $value;
				}
			}
			// Return true if no required option was set, or
			// required already options exists
			return true;
		}

		public function __get( $name ) {
			return isset( $this->store[$name] ) ? $this->store[$name] : null;
		}

		public function __set( $name, $value ) {
			$this->store[$name] = $value;
		}

		public function getActualOptions() {
			return $this->store;
		}

		public function getArgs() {
			return $this->args;
		}
	}

	/**
	*
	**/
	function parseArgs() {

		try {

			$packagesOpts = new Options( 
				$_GET,
				array(
					"command" => NULL,
					"output" => "jsonplain",
					"target" => Utils::getInvokingHostname()
					),
				array( "command" )
				);

			// Create a credentials provider
			$credProv = new MyCredentialsProvider();

			// Create a machine placeholder in order to retrieve
			// host information
			$machine = new Machine( $packagesOpts->target, $credProv );
		    
		    // Import the module that has the same name as the command
			do_import( $packagesOpts->command );
		    
		    // Call the command with the desired args.
	        // Prepend 'do_' as that is the format that modules should follow.
			do_it( "do_" . $packagesOpts->command, $packagesOpts->getArgs() ); //$args );
		
		} catch( Exception $e ) {
			echo $e;
		}
	}

	

	/**
	*
	*/
	function do_import( $module ) {
		$path = __DIR__ . "/modules/$module.php";
		if (! include_once( $path ))
			throw new Exception ( "No se pudo incluir el modulo $module desde $path" );
		if (! file_exists( $path )) {
			throw new Exception ( "No existe el archivo $module desde $path" );
		} else {
			require_once( $path ); 
		}
	}

	/**
	*
	*/
	function do_it( $command, $args = NULL ) {
		// Call the function $command with $args arguments
		$result = call_user_func( $command, $args );

		// Echo back the result
		// TODO: output can vary depending on from where the request was made.
		// AJAX request should output default plain JSON. 
		// Standard HTTP request should output default JSON with <pre> </pre> and JSON_PRETTY_PRINT.
		// Console request should output default to text output.
		// All this output behaviour is managed by 'output' option.
		// This should be the place to autodetect from where the request is made
		// and in turn, if no 'output' option was provided, set it to something.
		$output = call_user_func( $command."_output", $result, $args );

		echo $output;
	}


	parseArgs();

?>
