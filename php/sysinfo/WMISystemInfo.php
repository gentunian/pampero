<?php
	
class WMISystemInfo
{
	private $sysinfo = NULL;

	public function __construct( $hostname, $cred )
	{
		$user = $cred->getAdminUser();
		$password =  $cred->getAdminPassword();
		$wmiLocator = new COM("WbemScripting.SWbemLocator");
		if ( strtolower( $hostname ) == strtolower( gethostname() )) {
			$wmi = $wmiLocator->ConnectServer( $hostname, "root\\CIMV2" );
		} else {
			$wmi = $wmiLocator->ConnectServer( $hostname, "root\\CIMV2", $user, $password );
		}
		$this->sysinfo = array(
			/*'Win32Product' =>  $wmi->ExecQuery("Select * from Win32_Product"),*/
			'OperatingSystem' => $wmi->ExecQuery("Select * from Win32_OperatingSystem"),
			'ComputerSystem' => $wmi->ExecQuery("Select * from Win32_ComputerSystem")
			/*'Bios' => $wmi->ExecQuery("Select * from Win32_BIOS"),*/
			/*'Processor' => $wmi->ExecQuery("Select * from Win32_ComputerSystemProcessor"),*/
			/*'PhysicalMemory' => $wmi->ExecQuery("Select * from Win32_PhysicalMemory"),*/
			/*'BaseBoard' => $wmi->ExecQuery("Select * from Win32_BaseBoard"),*/
			/*'LogicalDisk' => $wmi->ExecQuery("Select * from Win32_LogicalDisk")*/
			);

	}

	function getHostname()
	{
		foreach ( $this->sysinfo['ComputerSystem'] as $wmi_call) 
			return $wmi_call->Name;
	}

	function getDomain()
	{
		foreach ( $this->sysinfo['ComputerSystem'] as $wmi_call) 
			return $wmi_call->Domain;
	}

	function getOSName()
	{
		foreach ( $this->sysinfo['OperatingSystem'] as $wmi_call) {
			$fullname =  explode('|', $wmi_call->Name, 2)[0];
			return $fullname;
		}
	}

	function getOSVersion()
	{
		foreach ( $this->sysinfo['OperatingSystem'] as $wmi_call)
			return $wmi_call->Version;
	}

	function getOSArchitecture()
	{
		foreach ( $this->sysinfo['OperatingSystem'] as $wmi_call)
			$arch = $wmi_call->OSArchitecture;
		if ( stripos( $arch, "64") !== FALSE ) {
			return "x86_64";
		} else  {
			return "i686";
		}
	}
}
?>