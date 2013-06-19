<?php

	/**
	*
	*/
	function do_it( $script, $command, $args = array()) {
		// Include the script file
		require_once( $script );

		// Call the function $command with $args arguments
		$result = call_user_func( $command, $args );

		// Echo back the result
		$output = call_user_func( $command."_output", $result );

		if ( !isCommandLineInterface() ) {
			$output = "<pre>$output</pre>";
		}


		echo $output;
	}

	/**
	*
	*/
	function buildCommandArray() {
		$array = array(
			"do_list" => "list.php",
			"do_install" => "install.php",
			"do_add" => "software/admin/add.php",
			"do_remove" => "software/admin/remove.php"
			);

		return $array;
	}

	/**
	*
	**/
	function parseArgs() {
		// This array will yield commands to scripts
		$commandArray = buildCommandArray();

		try {
			// Copy $_GET array
			$args = $_GET;

			// Get the command to be ran. Prepend 'do_' as that is
			// the format that scripts should follow.
			$command = "do_" . $args['command'];

			// Get the script that is supposed to have the command
			$script = $commandArray[ $command ];

			// Remove the 'command' key and pass the rest of the arguments
			// to by handled by the script
			unset( $args['command'] );

			// Call the script with the desired command and args
			do_it( $script, $command, $args );

		} catch( Exception $e ) {
			echo $e;
		}
	}

	/**
	*
	*/
	function print_output( $output ) {
		if ( !isCommandLineInterface() ) {
			$output .= "<pre>".$output."</pre>";
		}
		echo $output;
	}

	/**
	*
	**/
	function isCommandLineInterface()
	{
		$str = php_sapi_name();
		return ( stripos( $str, "cli" ) !== FALSE || stripos( $str, "cgi" ) !== FALSE );
	}

	parseArgs();
?>
