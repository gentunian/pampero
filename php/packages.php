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

	// Modules shouldn't be called directly. Use this constant
	// to decide inside a module
	define( 'FROM_PACKAGES', 'defined' );
	
	/**
	*
	**/
	function parseArgs() {
		// This array will yield commands to scripts
		//$commandArray = buildCommandArray();

		try {
			// Set default value if 'output' option was not provided
			// TODO: See TODO in do_it() function.
			if (! isset( $_GET['output'] )) $_GET['output'] = "jsonplain";

			// Try to get calling host
			$hostnameTarget = Settings::getInvokingHostname( $args );
			if ( !$hostnameTarget )
				die( "No se pudo determinar el host de destino." );

			// Set target 
			$_GET['target'] = $hostnameTarget;

			// Copy $_GET array
			$args = $_GET;

			// Get the command to be ran.
			$command = $args['command'];

			// Import the module that has the same name as the command
			do_import( $command );

			// Remove the 'command' key and pass the rest of the arguments
			// to be handled by the script
			unset( $args['command'] );

			// Call the command with the desired args.
			// Prepend 'do_' as that is the format that modules should follow.
			do_it( "do_" . $command, $args );

		
		} catch( Exception $e ) {
			echo $e;
		}
	}

	

	/**
	*
	*/
	function do_import( $module ) {
		$path = __DIR__ . "/modules/$module.php";
		try {
			if (! @include_once( $path ))
				throw new Exception ( "No se pudo incluir el modulo $path" );
			if (! file_exists( $path )) {
				throw new Exception ( "No existe el archivo $path" );
			} else {
				require_once( $path ); 
			}
			return true;
		}
		catch(Exception $e) {    
			//echo $e->getMessage();
			//echo $e->getCode();
			return false;
		}
	}

	/**
	*
	*/
	function do_it( $command, $args = array()) {

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
