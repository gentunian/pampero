<?php

/**
* WMISystemInfo class:
* --------------------
*
* Provides system information for windows machines using WMI (Windows Management Interface)
* if available.
*
* WMISystemInfo implements SystemInfo interface. The implementation is made using COM objects
* and WMI.
*
* Hosts must be connected with credentials if their credentials differs from the local account.
* NOTE: The construction of this object may fail if WMI object can't be created.
*
*/
class WMISystemInfo implements SystemInfo
{
	private $sysinfo = NULL;

	/**
	* Constructor. 
	*
	* @param cred Credentials that should contain admin user and admin password for $hostname
	* @param hostname The host name from the machine we want to get system information
	* @throws Exception if WMI connection fails
	*/
	public function __construct( $hostname, $cred )
	{
		$user = $cred->getAdminUser();
		$password =  $cred->getAdminPassword();

		// Creates a COM Object SWBemLocator: 
		// . http://msdn.microsoft.com/en-us/library/windows/desktop/aa393719(v=vs.85).aspx
		$wmiLocator = new COM("WbemScripting.SWbemLocator");
		try {
			// Instead of checking if hostname is localhost, try connecting WMI with the local
			// credentials (this will work for both cases, when we are connecting to localhost and
			// also when the remote computer has the same credentials as the local process. This is
			// necessary because if found that ConnectServer() will fail if we try to connect to
			// a remote machine specifying the same credentials as the local ones)
			$wmi = $wmiLocator->ConnectServer( $hostname, "root\\CIMV2" );
		} catch( Exception $e ) {
			// If the above failed, then possible reasons are:
			//
			// . WMI isn't available in the remote machine, either by a firewall blocking ports or
			//   disabled by sysadmin. WMI could be started by running: 'net wmimgmnt start' and also
			//   'netsh firewall set service RemoteAdmin enable'.
			//
			// . wbemErrAccessDenied (2147749891 (0x80041003)):
			//   The remote machine does not share local credentials and should be specified.
			//
			// . Some others reasons: http://msdn.microsoft.com/en-us/library/windows/desktop/aa393720(v=vs.85).aspx
			//
			// Now, the only error we could catch to try to continue to a WMI connection is to provide credentials
			// assuming that the error above was produced by trying to connect to a remote machine that has different
			// credentials than the local process.
			//
			// If the below instruction fails, then it will throw another exception to be catched by class clients.
			$wmi = $wmiLocator->ConnectServer( $hostname, "root\\CIMV2", $user, $password /* American English ,"MS_409" */);
		}

		// We don't check for $wmi validity. Constructor must be enclosed by try/catch to prevent
		// errors. The above code will throw an exception if it fails.
		$this->sysinfo['OperatingSystem'] = $wmi->ExecQuery("Select * from Win32_OperatingSystem");
		$this->sysinfo['ComputerSystem'] = $wmi->ExecQuery("Select * from Win32_ComputerSystem");
		/*
		$this->sysinfo = array(
			/*'Win32Product' =>  $wmi->ExecQuery("Select * from Win32_Product"),*/
			/*'OperatingSystem' => $wmi->ExecQuery("Select * from Win32_OperatingSystem"),*/
			/*'ComputerSystem' => $wmi->ExecQuery("Select * from Win32_ComputerSystem")*/
			/*'Bios' => $wmi->ExecQuery("Select * from Win32_BIOS"),*/
			/*'Processor' => $wmi->ExecQuery("Select * from Win32_ComputerSystemProcessor"),*/
			/*'PhysicalMemory' => $wmi->ExecQuery("Select * from Win32_PhysicalMemory"),*/
			/*'BaseBoard' => $wmi->ExecQuery("Select * from Win32_BaseBoard"),*/
			/*'LogicalDisk' => $wmi->ExecQuery("Select * from Win32_LogicalDisk")*/
	    /*			);*/

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