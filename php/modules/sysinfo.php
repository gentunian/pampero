<?php

	function do_sysinfo($args) {
		$opts = new Options(
			$args,
			array("output" => Utils::getDefaultOutput()),
			array("target")
			);

		foreach(explode(',', $opts->getOption("target")) as $key => $target) {
			try {
				$machine = new Machine($target);
				$result[] = $machine;
			} catch(Exception $e) {

			}
		}

		return $result;
	}

	function do_sysinfo_output($result, $args) {
		$opts = new Options(
			$args,
			array("output" => Utils::getDefaultOutput())
			);
		$outputType = $opts->getOption("output");
		$output = "";
		foreach ($result as $key => $machine) {
			$sysinfo = $machine->getSystemInfo();
			if ($outputType == "jsonplain") {
				$output .= $sysinfo->toJSON();
			} elseif ($outputType == "console") {
				$output .= $sysinfo->toString();
			}
		}
		return $output;
	}
?>