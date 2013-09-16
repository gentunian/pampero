<?php

	require_once( __DIR__ . '/../php/KLogger.php' );

	/* TODO: REVIEW */
	spl_autoload_register();
	spl_autoload_register( function ( $class ) {
		if ( class_exists( $class ))
			return true;
		$fileName = $class . ".php";
		$it = new RecursiveDirectoryIterator( __DIR__ . "/../php", FilesystemIterator::SKIP_DOTS );
		foreach( new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::LEAVES_ONLY ) as $file ) {
			if ( $file->getFilename() == $fileName ) {
				require_once( $file );
				return true;
			}
		}
		return false;
	});

	define( 'CONFIG_INI', __DIR__ . '/config.ini' );

	if (! file_exists( CONFIG_INI )) {
		die( "No se encontró el archivo de configuración: ".CONFIG_INI );
	}

	$iniContents = parse_ini_file( CONFIG_INI, true );
	define( 'UNIX_ADMIN', $iniContents['General']['UNIX_ADMIN'] );
	define( 'NT_ADMIN', $iniContents['General']['WINDOWS_ADMIN'] );
	define( 'NATIVE_OS', strtoupper(explode( ' ', php_uname(), 2)[0]) );
	define( 'UNKNOWN', 'Unknown' );
	define( 'OS_UNKNOWN', UNKNOWN );
	define( 'OS_UNIX', 'Unix' );
	define( 'OS_WINDOWS', 'Windows' );
	define( 'OS_ARCH_X86', $iniContents['General']['OS_ARCH_X86'] );
	define( 'OS_ARCH_X86_64', $iniContents['General']['OS_ARCH_X86_64'] );
	define( 'MANIFEST_FILENAME', $iniContents['General']['MANIFEST_FILENAME'] );
	define( 'PASSWORD_FILE', $iniContents['General']['PASSWORD_FILE'] );
	define( 'PACKAGES_DIR', $iniContents['General']['PACKAGES_DIR'] );
	define( 'PACKAGES_SHARE', $iniContents['General']['PACKAGES_SHARE'] );
	define( 'PACKAGES_GROUP_FILE', PACKAGES_DIR . DIRECTORY_SEPARATOR . 'groups.json' );
	define( 'LOG_DIR', $iniContents['General']['LOG_DIR'] );
	define( 'TMP_DIR', $iniContents['General']['TMP_DIR'] );
	define( 'WINDOWS_WINDOWS_INSTALLER', $iniContents['Installers']['WINDOWS_WINDOWS'] );
	define( 'CONSOLE_OUTPUT', 'console' );
	define( 'JSON_OUTPUT', 'jsonplain' );
	define( 'HTML_OUTPUT', 'html' );
?>