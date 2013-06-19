<?php

	//do_install($_GET);

	function do_install( $args ) {
		// Include list.php in order to retrieve a list of available
		// packages based on search arguments
		require_once( "list.php" );

		// Get the host that is invoking the action or the 
		// target machine
		$hostname = getInvokingHostname( $args );
		if ( $hostname == FALSE )
			return "No se pudo realizar la operacion porque no se especificó maquina destino para la instalación.";

		// No matter is 'json' key was provided. Override the key in order to
		// always have a JSON list of packages
		$args = array_merge( $args, array( "json" => 1 ) );

		// Retrive the JSON list of packages based on arguments
		$installData = do_list( $args );

		// Create an instance of InstallerCreator in order to retrieve
		// an installer based on package data.
		$installerCreator = new InstallerCreator();

		// The resulting array with data about the installation process
		$result = array();

		foreach( json_decode( $installData, true ) as $key => $value ) {
			// Get an installer instance for this package data.
			$installer = $installerCreator->getInstaller( $value['os'] );

			// Retrieve the target object based on hostname. This object should contain
			// target credentials
			$target = getTarget( $hostname );

			// Run the installation in target
			$result[ $value['id'] ] = $installer->run( $value['installer'], $value['installerArgs'], $target );
		}

		return $result;
	}

	/**
	*
	*/
	function do_install_output( $result ) {
		$output = sprintf("\nSumario:\n%s\n", str_repeat("-", strlen("sumario")));
		$okCount = 0;

		foreach( $result as $key => $value) {
			$output .= sprintf("\t%s:\n\t%s\n\t* %s\n\n", $key, str_repeat("-", strlen( $key )), $value->getExitString());

			if ( $value->getExitCode() == 0 )
				$okCount++;
		}
		$output .= sprintf("\nSe instalaron %d programas correctamente de un total de %d.\n", $okCount, count( $result ));
		
		/*
		if ( !isCommandLineInterface() ) {
			$output = "<pre>".$output."</pre>";
		}
		*/

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
		return new Target( "Administrador", "RCribera2013*", $host );
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
		const $TMP_DIR = "admintmp/files";

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

			$exitString = preg_replace('/\n/m', '', shell_exec("net helpmsg $exitCode"));
			$stdout = file_get_contents( $this->stdoutFileName );
			$stderr = file_get_contents( $this->stderrFileName );

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