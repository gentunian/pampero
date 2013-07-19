<?php

	//
	//
	if ( !defined( 'FROM_PACKAGES' ))
	{
		die( "Should be invoked by packages.php" );
	}

	function do_poll($args)
	{
		$result = "";
		$opts = getOptions($args);
		$target = $opts->getOption("target");
		if ($target != NULL) {
	        // Create a machine placeholder in order to retrieve
	        // host information
			$machine = new Machine($target);
			$machineName = $machine->getSystemInfo()->getHostname();
			$df = new DataFile("install", $machineName);
			$result = $df->read();

		} else {
			foreach(glob(TMP_DIR . "/install_*") as $filepath){
				$filename = explode('_', basename($filepath));
				$df = new InstallationDataFile($filename[0], $filename[1]);
				$result[$filename[1]] = $df->read();
			}
		}

		return $result;
	}

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

	function do_poll_output($result, $args)
	{
		$opts = getOptions($args);
		$outputType = $opts->getOption("output");
		$output = "";
		if ($outputType == "console") {
			$target = $opts->getOption("target");
			if ($target != NULL) {
				$output .= getStatusOutput( $result );
			} else {
				$output .= sprintf("Installations found:\n\n");
				foreach ($result as $target => $data) {
					$output .= sprintf("%s:\n%s\n", $target, str_repeat("-", strlen($target)));
					$output .= getStatusOutput($data);
					$output .= "\n";
				}
			}
		} else if ($outputType == "jsonplain") {
			$output = json_encode($result);
		}

		echo $output;
	}

	function getStatusOutput( $data )
	{
		$output = "";
		if ($data != NULL) {
			$output .= sprintf("Date: %s\n", $data->datetime);
			$output .= sprintf("Status: %s\n", $data->status);
			$output .= sprintf("Current: %s\n", $data->current);
			$output .= sprintf("Installed: %d\n", $data->installed);
			$output .= sprintf("To install: %d\n", $data->toInstall);
			$output .= sprintf("Progress: %d%%\n", ($data->installed/$data->toInstall*100));
		}
		return $output;
	}
?>