<?php

	define( 'CONFIG_INI', __DIR__ . '/config.ini' );

	if (! file_exists( CONFIG_INI )) {
		die( "No se encontró el archivo de configuración: ".CONFIG_INI );
	}

	$iniContents = parse_ini_file( CONFIG_INI, true );
	define( 'UNIX_ADMIN', $iniContents['General']['UNIX_ADMIN'] );
	define( 'NT_ADMIN', $iniContents['General']['WINDOWS_ADMIN'] );
	define( 'NATIVE_OS', strtoupper(explode( ' ', php_uname(), 2)[0]) );
	define( 'OS_UNKNOWN', 'Unknown' );
	define( 'OS_WINDOWS', 'Windows' );
	define( 'OS_ARCH_X86', $iniContents['General']['OS_ARCH_X86'] );
	define( 'OS_ARCH_X86_64', $iniContents['General']['OS_ARCH_X86_64'] );
	define( 'MANIFEST_FILENAME', $iniContents['General']['MANIFEST_FILENAME'] );
	define( 'PASSWORD_FILE', $iniContents['General']['PASSWORD_FILE'] );
	define( 'PACKAGES_DIR', $iniContents['General']['PACKAGES_DIR'] );
	define( 'TMP_DIR', $iniContents['General']['TMP_DIR'] );
	define( 'WINDOWS_WINDOWS_INSTALLER', $iniContents['Installers']['WINDOWS_WINDOWS'] );
	define( 'CONSOLE_OUTPUT', 'console' );
	define( 'JSON_OUTPUT', 'jsonplain' );
	define( 'HTML_OUTPUT', 'html' );
?>