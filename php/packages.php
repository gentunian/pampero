<?php
	/**
	* packages.php:
	* -------------
	*
	* php packages.php command=<module> [ {arg1=value1} ... ]
	*
	* or	
	*
	* php-cgi packages.php command=<module> [ arg1=value1 ... ]
	*
	* or
	*
	* http://myserver/php/packages.php?command=<module>&arg1[]=value1&...
	*/

	// Include config.php
	require_once( __DIR__ . '/../admin/config.php' );


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

		// $store holds the valid arguments this object needs to have
		private $store = NULL;
		// $args should holds the arguments passed by to be evaluated
		private $args = NULL;
		// $optional are the optional argument this objects should handle
		private $optional = NULL;
		// $required are the required argument this objects should handle
		private $required = NULL;

		/**
		* This will fail if:
		*    . required options are not found in $args
		*    . strict set is true and $args does not match that description
		*
		* It wont fail if:
		*    . strict set is false and required options is NULL, it simply
		*      will leave unused $args in $this->args.
		*/
		public function __construct( $args, $optional, $required = NULL, $strictSet = false ) {
			if (! ( is_array( $args ) && is_array( $optional )))
				throw new InvalidArgumentException( "Array required" );

			if (! (is_null( $required ) || is_array( $required )))
				throw new InvalidArgumentException( "Array required" );

			// Save and convert to an associative array.
			$this->args = $this->toAssoc( $args );
			$this->optional = $this->toAssoc( $optional );
			if (! is_null( $required )) {
				$this->required = $this->toAssoc( $required );

			    // Throw an exception if required arguments are missing
				if (( $o = $this->hasRequiredArguments() ) !== true )
					throw new Exception("Required argument '$o' is missing", 1);
				// Merge both optional and required arguments
				$argsTemplate = array_merge( $this->optional, $this->required );

			} else {
				$argsTemplate = array_merge( $this->optional );
			}

			// Work with the required and optional arguments setting the
			// correct value for each option using the template
			foreach( $this->args as $key => $value ) {
				$changed = $this->setKeyValueInArray( $key, $value, $argsTemplate );

				// if the argument was stored, remove it from $args
				if ( $changed ) {
					unset( $this->args[$key] );
				}
			}

			// Copy the modified template to be the actual argument list
			$this->store = $argsTemplate;

			if ( $strictSet && count( $this->getDiscardOptions() ) != 0 ) {
				throw new Exception("Arguments are invalid ('". implode("', '", array_keys( $this->getDiscardOptions()) )."')");
			}

		}

		// TODO: FIX and change in order to allow required arguments with category
		//
		// Returns an associative array with keys set as arguments.
		// Each value that corresponds to a numeric key is splited by
		// the equal sign. The first part is set as the key, and the
		// rest of it as its value.
		private function toAssoc( $array ) {
			$copy = $array;
			foreach ($array as $key => $value) {
				if ( is_numeric( $key )) {
					$split = explode( "=", $value, 2 );
					$copy[$split[0]] = (isset( $split[1] ))? $split[1] : "";
					unset( $copy[$key] );
				}
			}
			return $copy;
		}

		// Returns true wether or not $this->args has the arguments listed
		// in $required.
		private function hasRequiredArguments() {
			// If required arguments has been set, check for any existence
			// of them.
			foreach ($this->required as $key => $value) {
				if (! array_key_exists( $key, $this->args )) {
					return $key;
				}
			}
			// Return true if no required argument was set, or
			// required arguments already exists
			return true;
		}

		//
		private function setKeyValueInArray( $findKey, $newValue, &$array ) {
			$changed = false;
			foreach ( $array as $key => &$value ) {
				if ( is_array( $value )) {
					$changed = $this->setKeyValueInArray( $findKey, $newValue, $value );
				} elseif ( $changed = ( $key == $findKey )) {
					$value = $newValue;
				}
			}
			unset( $value );
			return $changed;
		}

		// TODO: CHECK FOR KEY EXISTENCE
		//
		// Returns the option or option category based on dsc
		public function getOption( $dsc ) {
			$split = explode( "-", $dsc );
			$value = $this->store[$split[0]];
			for( $i = 1; $i < count( $split ); $i++ )
				$value = $value[$split[$i]];
			return $value;
		}

		// Returns all options that are in both,
		// optional and required sets that has been processed
		// and represents the actual values of the options.
		public function getOptions() {
			return $this->store;
		}

		// Returns the options that aren't used by this object. When using
		// $strictSet == true and constructor doesn't throw an exception
		// then, this set should be empty.
		public function getDisposedOptions() {
			return $this->args;
		}
	}

	/**
	*
	**/
	function parseArgs() {
		global $argv;
		if (! is_null( $argv ))
			$args = $argv;
		else
			$args = $_GET;
		
		try {
			// Create options for this module based on the object description passed in
			// to the constructor. 
			$packagesOpts = new Options(
				// $argv or $_GET
				$args,
				// List of optional arguments (keys) with default values.
				// Missing arguments will be assigned to default values.
				array(
					"output" => "jsonplain"
					//"target" => Utils::getInvokingHostname()
					),
				// Required options, if any.
				array( "command" )
				);

			// In order to continue, try creating a Machine object instance.
			// If we can create it, then, other modules will.
			// Create a credentials provider
			//$credProv = new MyCredentialsProvider();

			// Create a machine placeholder in order to retrieve
			// host information
			//$machine = new Machine( $packagesOpts->getOption( "target" ), $credProv );
		    
		    // Get the command module to import
		    $command = $packagesOpts->getOption( "command" );

		    // Import the module that has the same name as the command
			do_import( $command );
		    
		    // Call the command with the desired args.
	        // Prepend 'do_' as that is the format that modules should follow.
			do_it( "do_" . $command, $args );
		
		} catch( Exception $e ) {
			echo "Error: ".$e->getMessage()."\n";
		}
	}

	

	/**
	*
	*/
	function do_import( $module ) {
		$path = __DIR__ . "/modules/$module.php";
		if (! @include_once( $path ))
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
