<?php

	/**
	*
	*/
	class PsexecRemoteInstaller implements RemoteInstaller {
		private $scriptFileName = NULL;
		private $stderrFileName = NULL;
		private $stdoutFileName = NULL;


		private function make_seed() {
			list($usec, $sec) = explode(' ', microtime());
			return (float) $sec + ((float) $usec * 100000);
		}

		private function createStderrFile() {
			srand( $this->make_seed() );
			$this->stderrFileName = tempnam( TMP_DIR, "err");
		}

		private function createStdoutFile() {
			srand( $this->make_seed() );
			$this->stdoutFileName = tempnam( TMP_DIR, "out");
		}

		private function createScriptFile() {
			srand( $this->make_seed() );
			$this->scriptFileName = tempnam( TMP_DIR, "js");
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

		

		public function install( $command, $args, $target ) {
			$psexecOptions = "/user:".$target->getUser();
			$psexecOptions .= " /password:".$target->getPassword();
			$psexecOptions .= " /machine:".$target->getMachineName();

			$string = "cscript //E:JScript ".$this->scriptFileName." $psexecOptions \"$command\" $args";

			$process = popen( $string, "r" );
			$exitCode = pclose( $process );

			$exitString = iconv('CP1252', 'UTF-8//IGNORE', preg_replace('/\n/m', '', shell_exec("net helpmsg $exitCode")));
			$stdout = iconv('CP1252', 'UTF-8', file_get_contents( $this->stdoutFileName ));
			$stderr = iconv('CP1252', 'UTF-8//IGNORE', file_get_contents( $this->stderrFileName ));

			return new CommandResult( $stdout, $stderr, $exitCode, $exitString );
		}

		public function getHostOS() {
			return OS_WINDOWS;
		}

		public function getTargetOS() {
			return OS_WINDOWS;
		}
	}
?>