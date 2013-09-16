<?php

	function do_remove($args)
	{
		$opts = new Options(
			$args,
			array(),
			array("id")
			);

		$id = $opts->getOption("id");
		if (is_array($id)) {

			for($i = 0; $i < count($id); $i++) {
				removePackageItem($id[$i]);
			}

		} else {
			removePackageItem($id);
		}

	}

	function do_remove_output($result, $args)
	{

	}

	function removePackageItem($id) {
		if (($index = strpos($id, "-")) !== FALSE) {
			$packageName = substr($id, 0, $index);

			try {
				$packageData = new PackageData($packageName, true);

				$packageData->removePackageItem($id);

				$packageData->save();
				
			} catch(Exception $e) {
				var_dump($packageName);
			}
		}
	}
	
?>