<?php
	

	function do_add($args)
	{
	
		$opts = new Options(
			$args,
			array(),
			array("name", "description", "os", "arch", "installer", "installerArgs", "version")
			);
		$packageName = $opts->getOption("name");

		if (preg_match("/[a-zA-Z ].*/", $packageName) == 1) {
			
			$packageData = new PackageData($packageName);
			$data = $opts->getOptions();

			if (is_array($data['os'])) {

				$count = count($data['os']);
				for($i = 0; $i < $count; $i++) {
                    
                    // Wraps up item data in an array
					$packageItem = PackageItem::create()->fromArray(
						array(
							"name" => $data['name'],
							"description" => $data['description'],
							"arch" => $data['arch'][$i],
							"os" => $data['os'][$i],
							"version" => $data['version'][$i],
							"installerArgs" => $data['installerArgs'][$i],
							"installer" => PACKAGES_SHARE . "\\" . basename($packageData->getPackagePath()) . "\\" . $data['installer']['name'][$i]
							)
						);

				    // Adds the package and if successfull, moves the uploaded files
					if ($packageData->addPackageItem($packageItem)) {
						$installerPath =  $packageData->getPackagePath() . "/" . $data['installer']['name'][$i];
						move_uploaded_file($data['installer']['tmp_name'][$i], $installerPath);
					}

				}
			} else {

				$packageItem = PackageItem::create()->fromArray($data);

				if ($packageData->addPackageItem($packageItem)) {
					copy($data['installer'], $packageData->getPackagePath() . "/" . basename($data['installer']));
				}
			}

			$packageData->save();
		}

	}

	function do_add_output($result, $args)
	{
	}

	
?>