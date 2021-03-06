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

	/**
	* Command abstract class:
	* -----------------------
	*
	*/
	abstract class Command {
		private $name = NULL;
		private $data = NULL;
		private $options = NULL;
		private $error = NULL;

		public function __construct($name, $args = NULL)
		{
			$this->setOptions(new Options($args, array()));
			$this->name = $name;
		}

		public abstract function execute();
		public abstract function getUsage();

		public function getName()
		{
			return $this->name;
		}

		public function setError($error)
		{
			$this->error = $error;
		}

		public function getError()
		{
			return $this->error;
		}

		public function getOptions()
		{
			return $this->options;
		}

		public function setOptions($opts)
		{
			$this->options = $opts;
		}

		public function getData()
		{
			return $this->data;
		}

		public function setData(JsonSerializable $data = NULL)
		{
			if ($data === NULL) $data = new NullCommandData();
			$this->data = $data;
		}
	}

	/**
	* NullCommandData class:
	* ----------------------
	*/
	class NullCommandData implements JsonSerializable {
		function jsonSerialize()
		{
			return array();
		}
	}

	/**
	* CommandOutput interface:
	* ------------------------
	*/
	interface CommandResult {
		function getOutput(Command $cmd);
	}

	/**
	* JSONResult class:
	* -----------------
	*/
	class JSONResult implements CommandResult {

		function getOutput(Command $cmd)
		{
			$output = array(
				"name" => $cmd->getname(),
				"error" => $cmd->getError(),
				"data" => $cmd->getData()->jsonSerialize()
				);
			return json_encode($output);
		}
	}

	/**
	* 
	*/
	class ConsoleResult implements CommandResult {
		function getOutput(Command $cmd)
		{
			$output = sprintf("%s:\n%s\n\n", $cmd->getName(), str_repeat("-", strlen($cmd->getName()) + 1));
			$output .= sprintf("'%s' Command Data:\n", $cmd->getName());
			/*foreach($cmd->getData()->jsonSerialize() as $key => $value) {
				$output .= sprintf("\t* %s\n", $key);
			}
			*/
			$output .= $this->printArray($cmd->getData()->jsonSerialize());
			return $output;
		}

		private function printArray($array, $spaces = "  ")
		{
			$output = "";
			foreach($array as $key => $value) {
				if (is_array($value)) {
					$output .= sprintf("\n${spaces}* %s:\n%s", $key, $this->printArray($value, "${spaces}  "));
				} else {
					$output .= sprintf("${spaces} %s: %s\n", $key, $value);
				}
			}
			return $output;
		}
	}


	/**
	* DataFile class:
	* ---------------
	*
	* Encapsulates the reading and writing of JSON data into files located in LOG_DIR/$prefix directory with
	* name $suffix. The idea of this class is to atomically read and write from files and store JSON encoded
	* data keeping the details to a minimun.
	*
	* TODO: remove LOG_DIR and allow to be saved anywhere by subclasses
	*/
	class DataFile
	{
		private $filename;

		/**
		* Constructor.
	    *
	    * @param prefix LOG_DIR/prefix will be the directory to be created
	    * @param suffix LOG_DIR/prefix/sufix will be the filename to be created
	    * @param create Specifies wether or not the file should be created.
		*/
		public function __construct($parent, $name, $create = true)
		{
			$dir = $parent;
			if (!file_exists($dir)){
				mkdir($dir);
			}
			$this->filename = $dir . "/" . $name;
			if ($create) {
				$fd = fopen($this->filename, "a+");
				fclose($fd);
			}
		}

		/**
		* Writes data to $this->filename previously JSON encoded.
		*
		* @param data Array to be encoded in JSON before write
		*/
		public function write($data)
		{
			$fd = @fopen($this->filename, "w+");
			if ($fd != FALSE) {
				if (flock($fd, LOCK_EX))
				{
					ftruncate($fd, 0);
					fwrite($fd, json_encode($data, JSON_PRETTY_PRINT));
					fflush($fd);
					flock($fd, LOCK_UN);
				}
				fclose($fd);
			}
		}

		/**
		* Reads data back
		*
		* @return the JSON encoded data
		*/
		public function read()
		{
			$content = "{}";
			$fd = @fopen($this->filename, "r");
			if ($fd != FALSE) {
				if (flock($fd, LOCK_SH))
				{
					$content = file_get_contents($this->filename);
					fflush($fd);
					flock($fd, LOCK_UN);
				}
				fclose($fd);
			}
			return $content;
		}

		public function chmod($mod) {
			$fd = @fopen($this->filename, "r");
			if (flock($fd, LOCK_EX))
			{
				chmod($this->filename, $mod);
				flock($fd, LOCK_UN);
			}
			fclose($fd);
		}

		/**
		* Detelets $this->filename
		*/
		public function delete()
		{
			@unlink($this->filename);
		}
	}

	/**
	* InstallationDataFile class:
	* ---------------------------
	*
	* This class provides specific output formatting for an installation file. InstallationDataFile
	* should contain installation based data in order to poll progress changed or leave last
	* installation history.
	*/
	class InstallationDataFile extends DataFile
	{

		public function __construct($parent, $name, $create = true) {
			DataFile::__construct(LOG_DIR . "/" . $parent, strtoupper($name), $create);
		}

		/**
		* Returns a formatted string.
		* @return the string representing the installation data
		*/
		public function toString()
		{
			$output = "";
			$data = json_decode($this->read());
			if ($data != NULL && $data != "{}") {
				$output .= sprintf("Date: %s\n", @$data->datetime);
				$output .= sprintf("Status: %s\n", @$data->status);
				$output .= sprintf("Current: %s\n", @$data->current);
				$output .= sprintf("Errors: %d\n", @$data->errors);
				$output .= sprintf("Procesed: %d\n", @$data->processed);
				$output .= sprintf("To install: %d\n", @$data->toInstall);
				$output .= sprintf("Progress: %d%%\n", @($data->installed/$data->toInstall*100));
				$output .= sprintf("Install data:\n");
				$installData = @$data->installData;
				if ($installData != NULL) {
					foreach($data->installData as $key => $value) {
						$output .= sprintf("\t%s:%s (%s)[%d]\n", $key, $value->id, $value->exitString, $value->exitCode);
					}
				}
			}
			return $output;
		}

		/**
		* Same as $this->read() with a more intuitive name
		* @return the JSON data stored in file.
		*/
		public function toJSON()
		{
			return $this->read();
		}
	}

	/**
	* PackageData class:
	* ------------------
	*/
	class PackageData extends DataFile implements JsonSerializable {

		private $data;
		private $packagePath;

		public function __construct($packageName, $mustExists = false)
		{
			$directoryName = str_replace(" ", "", $packageName);
			$directories = glob(PACKAGES_DIR . "/*", GLOB_ONLYDIR);
			$found = false;
			$this->packagePath = PACKAGES_DIR . "/${directoryName}";
			foreach ($directories as $dir) {
				if (strtolower($dir) === strtolower($this->packagePath)) {
					$this->packagePath = $dir;
					$found = true;
				}
			}
			DataFile::__construct($this->packagePath, MANIFEST_FILENAME, !$found);
			if (! $found) {
				if ($mustExists) {
					throw new Exception("Package not found, $mustExists was set to true.");
				}
				$this->write(array($directoryName => array()));
			}
			$this->data = json_decode($this->read(), true);
		}

		public function getPackagePath()
		{
			return $this->packagePath;
		}

		public function hasPackageItem($id)
		{
			return $this->indexOf($id) != -1;
		}

		public function addPackageItem(PackageItem $item)
		{
			if ($this->hasPackageItem($item->getId())) {
				throw new Exception("Package '" . $item->getId() . "' already exists.");
			}
			array_push($this->data[basename($this->getPackagePath())], $item->jsonSerialize());
		}

		public function editPackageItem(PackageItem $item)
		{

		}

		public function removePackageItem($id)
		{
			if (! $this->hasPackageItem($id)) {
				throw new Exception("Package '" . $item->getId() . "' was not removed.");
			}
			array_splice($this->data[basename($this->getPackagePath())], $this->indexOf($id), 1);
		}

		public function save()
		{
			if (count($this->data[basename($this->getPackagePath())]) == 0) {
				$files = glob($this->getPackagePath() . "/*");
				foreach ($files as $file) {
					unlink($file);
				}
				rmdir($this->getPackagePath());
			} else {
				$this->write($this->data);
			}
		}

		public function jsonSerialize()
		{
			return json_decode($this->read(), true);
		}

		private function indexOf($id)
		{
			foreach ($this->data[basename($this->getPackagePath())] as $index => $item) {
				if ($item['id'] === $id) {
					return $index;
				}
			}
			return -1;
		}
	}

	/**
	* PackageItem class:
	* ------------------
	*/
	class PackageItem implements JsonSerializable {
		private $data = [];

		private function __construct() {}

		public static function create()
		{
			return new self();
		}

		public function fromValues($name, $os, $arch, $version, $installer, $installerArgs, $description)
		{
			$this->data['name'] = $name;
			$this->data['os'] = $os;
			$this->data['arch'] = $arch;
			$this->data['version'] = $version;
			$this->data['installer'] = $installer;
			$this->data['installerArgs'] = $installerArgs;
			$this->data['description'] = $description;
			$this->data['id'] = $this->getId();
			return $this;
		}

		public function fromArray($arrayData)
		{
			$this->fromValues(
				$arrayData['name'],
				$arrayData['os'],
				$arrayData['arch'],
				$arrayData['version'],
				$arrayData['installer'],
				$arrayData['installerArgs'],
				$arrayData['description']
				);
			return $this;
		}
		public function getId()
		{
			return $this->data['name'] . "-" . $this->data['version'] . "." . $this->data['arch'];
		}
		public function getName()
		{
			return $this->data['name'];
		}
		public function getDescription()
		{
			return $this->data['description'];
		}
		public function getOS()
		{
			return $this->data['os'];
		}
		public function getArch()
		{
			return $this->data['arch'];
		}
		public function getVersion()
		{
			return $this->data['version'];
		}
		public function getInstaller()
		{
			return $this->data['installer'];
		}
		public function getInstallerArgs()
		{
			return $this->data['installerArgs'];
		}

		public function jsonSerialize() {
			return $this->data;
		}
	}

	/**
	* Options class:
	* --------------
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
					throw new Exception("Required argument '${o}' is missing", 1);
				// Merge both optional and required arguments
				$argsTemplate = array_merge( $this->optional, $this->required );

			} else {
				$argsTemplate = $this->optional;
			}

			// Work with the required and optional arguments setting the
			// correct value for each option using the templateç
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
				} elseif ( $changed = ( $key === $findKey )) {
					$value = $newValue;
					break;
				}
			}
			unset( $value );
			return $changed;
		}

		// Returns the option or option category based on dsc
		public function getOption( $dsc ) {
			$split = explode( "-", $dsc );
			$value = NULL;

			if ( isset( $this->store[$split[0]] ))
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

		public function getOptionsAsString() {
			return $this->arrayToString($this->getOptions());
		}

		// Returns the options that aren't used by this object. When using
		// $strictSet == true and constructor doesn't throw an exception
		// then, this set should be empty.
		public function getDisposedOptions() {
			return $this->args;
		}

		public function getDisposedOptionsAsString() {
			return $this->arrayToString($this->getDisposedOptions());
		}

		private function arrayToString($array) {
			$output = "";
			foreach ($array as $key => $value) {
				if (is_array($value)) {
					$output .= $this->arrayToString($value);
				} else {
					$output .= "${key}=${value} ";
				}
			}
			return $output;
		}
	}

	
	function getArgs() {
		$args = array();
		global $argv;

		if (! empty($_GET)) $args = $_GET;

		if (! empty($_POST)) $args = array_merge($args, $_POST);

		if (! empty($_FILES)) $args = array_merge($args, $_FILES);

		if (! is_null($argv)) $args = array_merge($args, $argv);

		return $args;
	}

	/**
	*
	**/
	function parseArgs() {
		
		$args = getArgs();

		try {

			// Create options for this module based on the object description passed in
			// to the constructor. 
			$packagesOpts = new Options(
				// $argv or $_GET
				$args,
				// List of optional arguments (keys) with default values.
				// Missing arguments will be assigned to default values.
				array("output" => Utils::getDefaultOutput()),
				// Required options, if any.
				array("command")
				);

		    // Get the command module to import
		    $command = $packagesOpts->getOption("command");
		    $ip = Utils::getInvokingIP();
		    $host = Utils::getInvokingHostname();
		    $arguments = $packagesOpts->getOptionsAsString() . $packagesOpts->getDisposedOptionsAsString();
 		    Utils::log("Request from ${host} (${ip}): '${arguments}'", KLogger::INFO);

		    // Import the module that has the same name as the command
			do_import($command);
		    
		    // Call the command with the desired args.
	        // Prepend 'do_' as that is the format that modules should follow.
			do_it("do_${command}", $args);
		
		} catch(Exception $e) {
			Utils::log($e->getMessage(), KLogger::ERR);
			echo "Error: ".$e->getMessage()."\n";
		}
	}

	

	/**
	*
	*/
	function do_import( $module ) {
		$path = __DIR__ . "/modules/${module}.php";
		if (! include_once( $path ))
			throw new Exception ( "Could not include '${module}' module from '${path}'" );
		if (! file_exists( $path )) {
			throw new Exception ( "'${module}' does not exists at '${path}'" );
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

		// TODO: make $result be some command wrapper class that implements a method called 'getOutput()'.
		// For example:
		//
		// $cmdClass = "${command}Command";
		// $command = new AddCommand($args) ;
		// $command->execute();
		// echo $command->getOutput();
		//
		// The object should know which kind of output provide based on $args.

		// Echo back the result
		// AJAX request should output default plain JSON. 
		// Standard HTTP request should output default JSON with <pre> </pre> and JSON_PRETTY_PRINT.
		// Console request should output default to text output.
		// All this output behaviour is managed by 'output' option.
		$output = call_user_func( "${command}_output", $result, $args );

		echo $output;
	}

	parseArgs();
?>
