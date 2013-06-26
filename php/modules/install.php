<?php
	
	include_once( __DIR__ . "/../../admin/config.php" );

	/**
	*
	*/
	function do_install( $args ) {
		session_start();

		// Include list.php in order to retrieve a list of available
		// packages based on search arguments
		require_once( "list.php" );

		// Get the host that is invoking the action or the 
		// target machine
		$hostname = getInvokingHostname( $args );
		if ( $hostname == FALSE )
			return "No se pudo realizar la operacion porque no se especificó maquina destino para la instalación.";

		// Save the target in the SESSION data
		$_SESSION['target'] = wrapDomain( $hostname );

		// Save the output argument in order to change it for list.php module
		$outputType = $args['output'];

		// Retrive the array list of packages based on arguments from list.php module
		$installData = do_list( $args );

		// No matter if 'output' option was provided, we already saved that.
		// Override the key in order to always have a JSON list of packages.
		$args = array_merge( $args, array( "output" => "jsonplain" ));

		// Get a json list from list.php module.
		$installJSON = do_list_output( $installData, $args );

		// Create an instance of InstallerCreator in order to retrieve
		// an installer based on package data.
		$installerCreator = new InstallerCreator();

		// The resulting array with data about the installation process
		$result = array();

		// Store the array that defines our progress per session
		$_SESSION['data'] = array();
		session_write_close();

		foreach( json_decode( $installJSON, true ) as $key => $value ) {
			// Get an installer instance for this package data.
			$installer = $installerCreator->getInstaller( $value['os'] );

			// Retrieve the target object based on hostname. This object should contain
			// target credentials
			$target = getTarget( $hostname );

			// Run the installation in target
			$result[ $value['id'] ] = $installer->run( $value['installer'], $value['installerArgs'], $target );

			session_start();
			$_SESSION['data'][] = getDataArray(
				$value['id'],
				//count( $result ) - 1,
				$result[ $value[ 'id' ]]->getExitString(),
				$result[ $value[ 'id' ]]->getExitCode(),
				$result[ $value[ 'id' ]]->getStderr()
				);
			session_write_close();
		}

		// Restore the output option provided.
		$args['output'] = $outputType;

		return $_SESSION['data'];
	}

	/**
	*
	*/
	function getDataArray( $id, $status, $exitCode, $stderr ) {
		return array(
			"id" => $id,
			"status" => $status,
			"exitCode" =>$exitCode,
			"stderr" => $stderr
			);
	}

	/**
	*
	*/
	function do_install_output( $result, $args ) {
		$output = "";
		$outputType = strtolower( $args['output'] );

		// Encode JSON if plain json is desired
		if ( $outputType == "jsonplain" ) {
			$output = json_encode( $result );
		}

	    // Encode JSON  with pretty print if html json is desired
	    // and add extra <pre> tags.
		else if ( $outputType == "jsonhtml" ) {
			$output = "<pre>" . json_encode( $result, JSON_PRETTY_PRINT ) . "</pre>";
		}

		// The options left are html or console. HTML only adds pre tags
		else {
			$output = sprintf("\nSumario:\n%s\n", str_repeat("-", strlen("sumario")));
			$okCount = 0;
			foreach( $result as $key => $value) {
				$output .= sprintf("\t%s:\n\t%s\n\t* %s\n\n", $value['id'], str_repeat("-", strlen( $value['id'] )), $value['status']);
				if ( $value['exitCode'] == 0 )
					$okCount++;
			}
			$output .= sprintf("\nSe instalaron %d programas correctamente de un total de %d.\n", $okCount, count( $result ));

			if ( $outputType == "html" ) {
				$output = "<pre>".$output."</pre>";

			} else if ( $args['output'] == "console" ) {

			}
		}

		return $output;
	}


	/**
	*
	*/
	function getInvokingHostname( $args ) {
		$hostname = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );

		if ( $hostname == FALSE) {
			if ( is_array( $args ) && isset( $args['target'] ))
				$hostname = $args['target'];
			else
				$hostname = gethostname();
		}

		return $hostname;
	}

	/**
	*
	*/
	function getTarget( $host ) {

		// TODO: get credentials based on target for any user
		$pwd = getHostPassword( $host );

		$admin = getAdminUsername( $host );
		return new Target( $admin, $pwd, $host );
	}


	/**
	*
	*/
	class CommandResult {
		protected $stdout = NULL;
		protected $stderr = NULL;
		protected $exitCode = NULL;
		protected $exitString = NULL;

		public function __construct( $stdout, $stderr, $exitCode, $exitString ) {
			$this->stdout = $stdout;
			$this->stderr = $stderr;
			$this->exitCode = $exitCode;
			$this->exitString = $exitString;
		}

		function getStdout() {
			return $this->stdout;
		}

		function getStderr() {
			return $this->stderr;
		}

		function getExitCode() {
			return $this->exitCode;
		}

		function getExitString() {
			return $this->exitString;
		}
	}

	/**
	*
	*/
	class Target {
		public $user;
		public $password;
		public $machine;

		public function __construct( $user, $password, $machine ) {
			$this->user = $user;
			$this->password = $password;
			$this->machine = $machine;
		}
	}

	/**
	*
	*/
	interface RemoteInstaller {

		public function run( $command, $args, $target );
		
	}

	/**
	*
	*/
	class PsexecRemoteInstaller implements RemoteInstaller {
		private $scriptFileName = NULL;
		private $stderrFileName = NULL;
		private $stdoutFileName = NULL;
		private $TMP_DIR =  "/../../admin/tmp";

		private function make_seed() {
			list($usec, $sec) = explode(' ', microtime());
			return (float) $sec + ((float) $usec * 100000);
		}

		private function createStderrFile() {
			srand( $this->make_seed() );
			$this->stderrFileName = tempnam( $this->TMP_DIR, "err");
		}

		private function createStdoutFile() {
			srand( $this->make_seed() );
			$this->stdoutFileName = tempnam( $this->TMP_DIR, "out");
		}

		private function createScriptFile() {
			srand( $this->make_seed() );
			$this->scriptFileName = tempnam( $this->TMP_DIR, "js");
		}

		private function escapePath( $path ) {
			return str_replace( '\\', '\\\\', $path );
		}

		public function __destruct() {
			// Deletes temporal files
			unlink( $this->scriptFileName );
			unlink( $this->stdoutFileName );
			unlink( $this->stderrFileName );
		}

		public function __construct( $target = NULL) {
			$this->TMP_DIR = __DIR__ . $this->TMP_DIR;
			$this->createScriptFile();
			$this->createStdoutFile();
			$this->createStderrFile();

			$stderrFile = $this->escapePath( $this->stderrFileName );
			$stdoutFile = $this->escapePath( $this->stdoutFileName );

			$this->target = $target;

			$wscript = "var wsh = new ActiveXObject( 'WScript.Shell' );\n";
			//$wscript .= "var fso = new ActiveXObject( 'Scripting.FileSystemObject' );\n\n";
			$wscript .= "var psexecArgs = WScript.Arguments.Named;\n";
			$wscript .= "var commandArgs = WScript.Arguments.Unnamed;\n\n";
			$wscript .= "var cmd = 'cmd.exe /C psexec \\\\\\\\'+psexecArgs.Item('machine')+' -n 60 -u '+psexecArgs.Item('user')+' -p '+psexecArgs.Item('password')+' cmd.exe /C \"';\n";
			$wscript .= "for (var i = 0; i < commandArgs.length; i++) {\n";
			$wscript .= "\tvar arg = commandArgs.Item( i );\n";
			$wscript .= "\tif ( arg.indexOf( \" \" ) >= 0 ) {\n";
			$wscript .= "\t\targ = '\"'+arg+'\"';\n\t}\n";
			$wscript .= "\tcmd += ' '+arg+' ';\n}\n";
			$wscript .= "cmd += '\"';\n\n";
			$wscript .= "var env = wsh.Environment(\"PROCESS\");\n";
			$wscript .= "env(\"SEE_MASK_NOZONECHECKS\") = 1;\n";
			$wscript .= "var exitCode = wsh.Run(cmd+\" > ".$stdoutFile." 2> ".$stderrFile."\", 0, true);\n";
			$wscript .= "env.Remove(\"SEE_MASK_NOZONECHECKS\");\n";
			$wscript .= "WScript.Quit( exitCode )\n";

			$file = fopen( $this->scriptFileName, "w");
			fwrite( $file, $wscript );
			fclose( $file );
		}

		

		public function run( $command, $args, $target ) {
			$psexecOptions = "/user:".$target->user;
			$psexecOptions .= " /password:".$target->password;
			$psexecOptions .= " /machine:".$target->machine;

			$string = "cscript //E:JScript ".$this->scriptFileName." $psexecOptions \"$command\" $args";

			$process = popen( $string, "r" );
			$exitCode = pclose( $process );

			$exitString = iconv('CP1252', 'UTF-8//IGNORE', preg_replace('/\n/m', '', shell_exec("net helpmsg $exitCode")));
			$stdout = iconv('CP1252', 'UTF-8', file_get_contents( $this->stdoutFileName ));
			$stderr = iconv('CP1252', 'UTF-8//IGNORE', file_get_contents( $this->stderrFileName ));

			return new CommandResult( $stdout, $stderr, $exitCode, $exitString );
		}
	}

	/**
	*
	*/
	class InstallerCreator {

		private $installers = NULL;

		function getInstaller( $os ) {
			if ( is_array( $os )) $os = implode( ',', $os );

			if ( stripos( $os, "win") !== false ) {
				$os = "windows";
			}

			if ( !isset( $installers[$os] )) {
				$installers[$os] = $this->createInstallerForOS( $os );
			}

			return $installers[ $os ];
		}

		function createInstallerForOS( $os ) {
			if ( stripos( "windows", $os) !== FALSE ) {
				return new PsexecRemoteInstaller();
			}
		}
	}

	?>