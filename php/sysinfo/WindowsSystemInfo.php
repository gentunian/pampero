<?php

	class WindowsSystemInfo {
		private $sysinfo = NULL;

		public function __construct( $hostname ) {
			$user = "Administrador";
			$password =  "RCribera2013*";
			$wmiLocator = new COM("WbemScripting.SWbemLocator");
			$wmi = $wmiLocator->ConnectServer( $hostname, "root\\CIMV2", $user, $password );
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

		function getHostname() {
			foreach ( $this->sysinfo['ComputerSystem'] as $wmi_call) 
				return $wmi_call->Name;
		}

		function getDomain() {
			foreach ( $this->sysinfo['ComputerSystem'] as $wmi_call) 
				return $wmi_call->Domain;
		}

		function getOperatingSystem() {
			foreach ( $this->sysinfo['OperatingSystem'] as $wmi_call) {
				$name =  explode('|', $wmi_call->Name, 2)[0];
				$version = $wmi_call->Version;
				$arch = $wmi_call->OSArchitecture;
				return new OperatingSystem( $name, $version, $arch );
			}
		}

		function getSystemArchitecture() {
			foreach( $this->sysinfo['Win32Product'] as $wmi_call ) {
				echo "\n".$wmi_call->Name. " - " .$wmi_call->Version ."\n";
			}
		}
	}
?>