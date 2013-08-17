<?php

	// 
	if (!defined('FROM_PACKAGES'))
	{
		die( "Should be invoked by packages.php" );
	}

	/**
	* Simulates polling by reading installation file data. If a specific target is provided
	* creates the correspondent InstallationDataFile in order to read installation data.
	* If its not provided, read all installation files.
	*/
	function do_poll($args)
	{
		$result = [];
		$opts = getOptions($args);
		$target = $opts->getOption("target");
		// Do not iterate through all files if a target was provided
		if ($target != NULL) {
			$machine = new Machine($target);
			$df = new InstallationDataFile("install", $machine->getSystemInfo()->getHostname(), false);
			$result = $df;
		} else {
			// Gather all information from installations made
			foreach(glob(LOG_DIR . "/install/*") as $filepath){
				$filename = basename($filepath);
				$df = new InstallationDataFile("install", $filename, false);
				$result[$filename] = $df;
			}
		}

		// $result will be an array of InstallationDataFile objects
		return $result;
	}

	/**
	* Creates and returns and Options object based on this module arguments.
	*/
	function getOptions($args)
	{
		$opts = new Options(
			$args,
			array( 
				"target" => NULL,
				"output" => Utils::getDefaultOutput()
				)
			);
		return $opts;
	}

	/**
	* We know what to do with $result as we are the ones that generates that data
	* in do_poll() method.
	*/
	function do_poll_output($result, $args)
	{
		$opts = getOptions($args);
		$outputType = $opts->getOption("output");
		$output = "";
		$target = $opts->getOption("target");

		if (! is_array($result)) {
			if ($outputType == "console") {
				$output .= $result->toString();
			} elseif ($outputType == "jsonplain") {
				$output .= $result->toJSON();
			}
		} else {
		// Iterate through each InstallationDataFile object an prints its status
			foreach ($result as $target => $data) {
				if ( $outputType == "console") {
					$output .= sprintf("%s:\n%s\n", $target, str_repeat("-", strlen($target)));
					$output .= $data->toString();
					$output .= "\n";
				} else if ($outputType == "jsonplain") {
					$output .= "\"${target}\":" . $data->toJSON() . ",";
				}
			}
		    // If "jsonplain" output was set, remove las comma and wrap it up with enclosing brackets
			if ($outputType == "jsonplain") {
				$output = "{" . rtrim($output, ",") . "}";
			}
		}
		return $output;
	}
?>