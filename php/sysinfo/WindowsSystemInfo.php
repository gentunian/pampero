<?php

/**
* WindowsSystemInfo class:
* ------------------------
*
* WindowsSystemInfo class provides information for windows machines. It will try to adapt WMISytemInfo
* behaviour when possible. A no WMI approach should be made if WMISystemInfo is not available.
*/
class WindowsSystemInfo extends SystemInfo
{
	// TODO: Provide fallback option if WMI isn't available try guessing with other tools

	private $sysinfo = NULL;

	// Adapt this class to WMISystemInfo when possible.
	private $wmi = NULL;

	/**
	* Constructor.
	*
	* @param hostname The host name from where we want to retrieve info
	* @param cred Credentials for hostname
	*/
	public function __construct( $hostname, $cred )
	{
		SystemInfo::__construct($hostname);
		try {
			// Try creating a WMISystemInfo object in order to adapt the behaviour.
			$this->wmi = new WMISystemInfo( $hostname, $cred );

		} catch( Exception $e ) {
			// TODO: If we could not use WMISystemInfo for windows hosts, find out how we could
			// do this
			$user = $cred->getAdminUser();
			$password =  $cred->getAdminPassword();
			$this->sysinfo['hostname'] = $hostname;
			$this->sysinfo['domain'] = UNKNOWN;
			// We know it's a windows machine so set it to OS_WINDOWS
			$this->sysinfo['OSName'] = OS_WINDOWS_FAMILY;
			$this->sysinfo['OSArchitecture'] = UNKNOWN;
			$this->sysinfo['OSVersion'] = UNKNOWN;
		}
	}

	function getHostname()
	{
		if ( $this->wmi == NULL)
			return $this->sysinfo['hostname'];

		return $this->wmi->getHostname();
	}

	function getDomain()
	{
		if ( $this->wmi == NULL)
			return $this->sysinfo['domain'];

		return $this->wmi->getDomain();
	}

	function getOSName()
	{
		if ( $this->wmi == NULL)
			return $this->sysinfo['OSName'];

		return $this->wmi->getOSName();
	}

	function getOSVersion()
	{
		if ( $this->wmi == NULL)
			return $this->sysinfo['OSVersion'];

		return $this->wmi->getOSVersion();
	}

	function getOSArchitecture()
	{
		if ( $this->wmi == NULL)
			return $this->sysinfo['OSArchitecture'];

		return $this->wmi->getOSArchitecture();
	}
}
?>