<?php
	/*
	if ( !isPOSTok() )
		exit(1);
	*/
	define( "PACKAGE_DIR", __DIR__ . "/../../admin/packages" . DIRECTORY_SEPARATOR . $_POST['appKey'] );
	define( "MANIFEST_FILE", PACKAGE_DIR . DIRECTORY_SEPARATOR . "manifest.json" );
	// Review: try to find a portable way
	define( "PACKAGES_SHARE", "\\\\" . gethostname() . "\\packages" );

	/**
	*
	*/
	function main() {
		$installerData = getArrayFromPOST();
		if ( file_exists( MANIFEST_FILE ) ) {
			$packageData = json_decode( file_get_contents( MANIFEST_FILE ), true );
			array_push( $packageData[ $_POST['appKey'] ], $installerData);
		} else {
			$packageData = array( $_POST['appKey'] => array( $installerData ) );
		}

		if ( !file_exists( PACKAGE_DIR ) )
			mkdir( PACKAGE_DIR );

		uploadFile( PACKAGE_DIR );
		file_put_contents( MANIFEST_FILE, json_encode( $packageData, JSON_PRETTY_PRINT ) );
	}

	/**
	*
	*/
	function uploadFile( $packageDir ) {
		$temp = $_FILES['appFile']['tmp_name'];
		$file = "$packageDir" . DIRECTORY_SEPARATOR . $_FILES['appFile']['name'];
		move_uploaded_file( $temp , $file );
	}

	/**
	*
	*/
	function isPOSTok() {
		return ( isset( $_POST['appName'] ) &&
			isset( $_POST['appArgs'] ) &&
			isset( $_POST['appArch'] ) &&
			isset( $_POST['appOS'] ) &&
			isset( $_POST['appVersion'] ) &&
			isset( $_POST['appDescription'] ) &&
			isset( $_FILES['appFile'] )
			);
	}

	/**
	*
	*/
	function getArrayFromPOST() {
		$output = array(
			"id" => $_POST['appKey']."-".$_POST['appVersion'].".".$_POST['appArch'],
			"installer" => PACKAGES_SHARE . "\\" . $_POST['appKey'] . "\\" . $_FILES['appFile']['name'],
			"installerArgs" => $_POST['appArgs'],
			"arch" => $_POST['appArch'],
			"os" => $_POST['appOS'],
			"version" => $_POST['appVersion'],
			"name" => $_POST['appName'],
			"description" => $_POST['appDescription']
			);

		return $output;
	}

	/**
	*
	*/
	function getJSONStringFromPOST() {
		$output = "{\n";
		//$output .= "\t\"installer\":\"" . $_FILES['appFile']['name'] . "\"\n";
		$output .= "\t\"installerArgs\":\"" . $_POST['appArgs'] . "\"\n";
		$output .= "\t\"arch\":\"" . $_POST['appArch'] . "\"\n";
		$output .= "\t\"os\":\"" . $_POST['appOS'] . "\"\n";
		$output .= "\t\"version\":\"" . $_POST['appVersion'] . "\"\n";
		$output .= "\t\"name\":\"" . $_POST['appName'] . "\"\n";
		$output .= "\t\"description\":\"" . $_POST['appDescription'] . "\"\n";
		$output .= "\n}";

		return $output;
	}


	main();
?>