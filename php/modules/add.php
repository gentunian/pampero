<?php
	
	/**
	* AddCommand class:
	* -----------------
	*/
	class AddCommand extends Command {

		/**
		* Constructor.
		*/
		public function __construct($args = NULL)
		{
			if ($args != NULL) {
				Command::__construct("add", $args);
				$this->setOptions(new Options(
					$args,
					array("output" => Utils::getDefaultOutput()),
					array("name", "description", "os", "arch", "installer", "installerArgs", "version")
					)
				);
				optionsCheck();
			}
		}

		private function optionsCheck()
		{
			if (array_search(strtolower($this->getOptions()->getOption("os")), array_map('strtolower', $AVAILABLE_OS)) === FALSE)
				throw new Exception("'os' option must be a valid operating system listed in 'OperatingSystem' section at config.ini.");

			if (array_search(strtolower($this->getOptions()->getOption("arch")), array_map('strtolower', $AVAILABLE_ARCH)) === FALSE)
				throw new Exception("'arch' option must be a valid architecture listed in 'Architectures' section at config.ini.");

			if (preg_match("/^[a-z ]*$/i", $this->getOptions()->getOptions("name")) == 0)
				throw new Exception("Package name must have only letters and spaces.");
		}

		/**
		*
		*/
		public function getUsage()
		{
			return array(
				"optional" => array("output" => "How command output will be printed (default value is based on environment call, HTTP will output JSON, cli will be console, etc.)"),
				"required" => array(
					"name" => "The package name without symbols neither punctuations.",
					"description" => "The package description.",
					"os" => "The operating system where the package can be installed. Must be a valid operating system, see section 'OperatingSystem' in config.ini",
					"arch" => "The operating system architecture where the package can run on. Must be a valid architecture, see section 'Architectures' in config.ini",
					"installer" => "The path to an executable, script, etc, that can silently install the package.",
					"installerArgs" => "The arguments the installer needs. Note: it is marked as required but can be an empty string.",
					"version" => "The version the package has."
					)
				);
		}

		/**
		*
		*/
		public function execute()
		{
			// Gets the Options object
			$opts = $this->getOptions();
			if ($opts == NULL)
				return;

			$packageName = $opts->getOption("name");

			try {
            	// Name cannot have symbols, punctuations and numbers...Just letters and spaces.
				$packageData = new PackageData($packageName);
				// Gets the options as array
				$options = $opts->getOptions();
				// When called from HTML forms, 'os' field will be array based. Not the same when called from command-line.
				// FIX THIS: use Utils class
				if (is_array($options['os'])) {
					$count = count($options['os']);
					for($i = 0; $i < $count; $i++) {
					    // Creates a PackageItem object with options passed by
						$packageItem = PackageItem::create()->fromArray(
							array(
								"name" => $options['name'],
								"description" => $options['description'],
								"arch" => $options['arch'][$i][0],
								"os" => $options['os'][$i],
								"version" => $options['version'][$i],
								"installerArgs" => $options['installerArgs'][$i],
								"installer" => PACKAGES_SHARE . "\\" . basename($packageData->getPackagePath()) . "\\" . $options['installer']['name'][$i]
								)
							);
				        // Adds the package and if successfull, moves the uploaded files
						$packageData->addPackageItem($packageItem);
						move_uploaded_file($options['installer']['tmp_name'][$i], $packageData->getPackagePath() . "/" . $options['installer']['name'][$i]);
						// Save package data
						$packageData->save();
					}
					$this->setData($packageData);
				} else {
					// Creates a PackageItem object with options passed by
					$packageItem = PackageItem::create()->fromArray($options);
					// Copy the file if we were able to add the package
					$packageData->addPackageItem($packageItem);
					copy($options['installer'], $packageData->getPackagePath() . "/" . basename($options['installer']));
					// Save package data
					$packageData->save();
					$this->setData($packageItem);
				}
			} catch(Exception $e) {
				$this->setError($e->getMessage());
			}
		}
	}

	function do_add($args)
	{
		$command = new AddCommand($args);
		$command->execute();
		return $command;
	}

	function do_add_output($result, $args)
	{
		$outputType = $result->getOptions()->getOption("output");
		if ($outputType === "jsonplain") {
			$output = new JSONResult();
		}
		return $output->getOutput($result);
	}
	
?>