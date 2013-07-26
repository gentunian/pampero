<?php

/**
* PsexecRemoteInstaller class:
* ----------------------------
*
* This class implements RemoteInstaller interface by using PSTools.
*
* PSTools provides the ability to connect to a remote computer and run almost anything you want.
* PsexecRemoteInstaller class makes use of this ability in order to invoke executable installers
* stored in PACKAGES_SHARE.
*
* It basically connects to the remote host and invokes cmd.exe passing by the remote installer
* executable file and arguments.
*
* Due to limitation with PHP, cmd.exe and psexec.exe, the call needs to be wrapped by a VBScript or
* JScript file in order to avoid deadlock when reading/writting from STDOUT/STDERR.
*
* I found this workarround usefull, not what I might expect from this tools, but it seems to work
* and, most important, you can get STDERR and STDOUT output as well.
*
* The class generates a temporal file with JScript code to be run by WSH (Windows Scripting Host)
* engine. The script wraps the call to the 'psexec.exe' binary, collects exit code, exit status,
* stderr and stdout. They are stored in 2 temporal files suffixed "err" and "out" generated with
* a random prefix. The "*err" and "*out" files are read as well as the exit code from 'psexec.exe'
* and they are all put in a ComandResult object.
*
* NOTE: getHostOS() and getTargetOS() determines in which platforms this installer could be used.
*/
class PsexecRemoteInstaller implements RemoteInstaller {
	private $scriptFileName = NULL;
	private $stderrFileName = NULL;
	private $stdoutFileName = NULL;

	// creates a seed in order to provide a random number;
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

	/**
	* Constructor.
	*/
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



	public function install( $command, $args, $machine, $credentials) {
		$psexecOptions = "/user:".$credentials->getAdminUser();
		$psexecOptions .= " /password:".$credentials->getAdminPassword();
		$psexecOptions .= " /machine:".$machine;

		$string = "cscript //E:JScript ".$this->scriptFileName." $psexecOptions \"$command\" $args";
		
		set_time_limit(120);
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