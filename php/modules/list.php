	<?php

	define( 'OS_UNKNOWN', 'Unknown' );
	define( 'OS_WINDOWS_7', 'Windows 7' );
	define( 'OS_WINDOWS_XP', 'Windows XP' );
	define( 'OS_ARCH_X86', 'x86' );
	define( 'OS_ARCH_X86_64', 'x86_64' );
	define( 'MANIFEST_FILE', 'manifest.json' );
	define( 'PACKAGES_DIR', __DIR__ . '/../../admin/packages' );


	/**
	*
	*/
	function do_list( $args = NULL ) {
		// Create the filter in order to filter output
		$filterData = createFilter( $args );

		// Get the packages data
		$data = getDataFromPath( PACKAGES_DIR, $filterData );

		// Return data back
		return $data;
	}

	/**
	*
	*/
	function do_list_output( $data, $args ) {
		$output = "";
		$outputType = strtolower( $args['output'] );

		// Encode JSON if plain json is desired
		if ( $outputType == "jsonplain" ) {
			$output = json_encode( $data );
		}

	    // Encode JSON  with pretty print if html json is desired
	    // and add extra <pre> tags.
		else if ( $outputType == "jsonhtml" ) {
			$output = "<pre>" . json_encode( $data, JSON_PRETTY_PRINT ) . "</pre>";

		}

		// The options left are html or console. HTML only adds pre tags
		else {

			$filterData = createFilter( $args );
			$filterArray = $filterData['filter'];
			$output = getStringOutput( $data, $filterData['options']['full'] );
			$output .= "\n\nFiltro utilizado:\n".((count($filterArray)==0)? ("\tNinguno\n"): arrayOutput( $filterArray, "\t(%s => %s)\n" ));
			$output .="\nOpciones del filtro:\n".arrayOutput( $filterData['options'], "\t(%s => %s)\n");

			if ( $outputType == "html" ) {
				$output = "<pre>".$output."</pre>";

			} else if ( $args['output'] == "console" ) {

			}
		}

		return $output;
	}


	/**
	*
	*/
	function createFilter( $args ) {
		// The array that will contains options
		$options = array();

		foreach( array('exact', 'full') as $key => $value) {
			$options = array_merge($options, createBooleanOption( $args, $value ));
		}

		// Assign the options
		$filter = array( "options" => $options);

	    // Put only valid filter keys in the filter.
		$filter['filter'] = array();

		if ( $args != NULL && is_array( $args ) ) {
			foreach ($args as $key => $value) {
				if ( validSearchKey( $key ) )
					$filter['filter'] = array_merge( array( $key => $value ), $filter['filter'] );
			}
		}

		// Return our filter array
		return $filter;
	}

	/**
	*
	*/
	function createBooleanOption( $args, $option, $default = false ) {
		$result = array( $option => $default );
	
		if ( isset( $args[ $option ] ) && preg_match("/(yes|1|true|on)/", $args[ $option ]) )
			$result[$option] = true;

		return $result;
	}

	/**
	*
	**/
	function validSearchKey( $key ) {
		$searchKeys = "id,installer,installerArgs,arch,os,name,description,";
		foreach (explode(',', $searchKeys) as $value) {
			if ( strcmp( $key, $value ) == 0 )
				return true;
		}
		return false;
	}

	/**
	*
	**/
	function getDataFromPath( $path, $filter = NULL ) {
		$di = new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS );
		$it = new  RecursiveIteratorIterator( $di, RecursiveIteratorIterator::SELF_FIRST );
		$it->setMaxDepth( 1 );
		$data = array();
		foreach ( $it as $filename => $file ) {
			if ( isPackageFile( $file )) {
				$packageData = getPackageDataFromFile( $file, $filter );
				if ( count( $packageData ) > 0 ) {
					$data = array_merge( $packageData, $data );
				}
			}
		}
		return $data;
	}

	/**
	* Returns true is $file contains a JSON string called manifest.json.
	* False otherwise.
	**/
	function isPackageFile( $file ) {
		if ( strcasecmp( MANIFEST_FILE, $file->getFilename()) != 0 )
			return false;

		json_decode( file_get_contents( $file ) );
		return ( json_last_error() == JSON_ERROR_NONE );
	}


	/**
	* 
	**/
	function getPackageDataFromFile( $file, $filterData ) {
		// Retrieve the text from the manifest.json file and
		// decode the JSON to an Array Object.
		$json = file_get_contents( $file->getPathname() );
		$data = json_decode( $json, true );

		// Get the array that has the installers arrays. $packageData
		// now is an array that has installers arrays.
		$packageData = $data[ key( $data ) ];

		return getFilteredData( $packageData, $filterData );

	}

	/**
	* Given a data array, return only those array that its keys
	* mathces all keys in the $filter array.
	**/
	function getFilteredData( $packageData, $filter ) {
		$result = array();

		foreach ($packageData as $key => $installerData) {
			if ( filterMatchArray( $installerData, $filter['filter'], $filter['options']['exact'] ) ) {
				$result[] = $installerData;
			}
		}

		return $result;
	}

	/**
	*
	*/
	function filterMatchArray( $array, $filterArray, $exact = false ) {
		$matches = 0;

		foreach ( array_keys( $filterArray ) as $key ) {
			if ( shouldFilterArray( wrapArray( $array[$key] ), wrapArray( $filterArray[$key] ), $exact ) ) {
				$matches++;
			}
		}

		return ( count( $filterArray ) == $matches );
	}

	/**
	*
	*/
	function wrapArray( $something ) {
		if ( !is_array( $something ) ) {
			return array( $something );
		}
		return $something;
	}

	/**
	*
	*/
	function shouldFilterArray( $array1, $array2, $exact ) {
		foreach( $array1 as $key1 => $value1 ) {
			foreach( $array2 as $key2 => $value2 ) {
				if ( shouldFilterValue( $value1, $value2, $exact )) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	*
	*/
	function shouldFilterValue( $value1, $value2, $exact ) {
		if ( $exact ) {
			return ( strcasecmp( $value1, $value2 ) == 0 );
		} else {
			return ( stripos( $value1, $value2 ) !== false );
		}
	}

	/**
	*
	*/
	function valueMatchValue( $value1, $value2, $exact ) {
		if ( $exact ) {
			return ( strcasecmp( $value1, $value2 ) == 0 );
		} else {
			if ( is_array($value1) ) {
				$value1 = implode(',', $value1);
			}
			return ( stripos( $value1, $value2 ) !== false );
		}
	}

	/**
	*
	*/
	function getStringOutput( $jsonArray, $full ) {
		$output = sprintf( "\nSe encontraron %d paquete(s):\n", count( $jsonArray ));
		$id = ( $full )? "" : "id";

		foreach ( $jsonArray as $key => $installersArray ) {
			$output .= sprintf( "\nItem %s:\n%s", $key, str_repeat('-', strlen("item $key:")) );
			ksort( $installersArray );
			$output .= arrayOutput( $installersArray, "\n%15s: %s", $id );
		}

		return $output;
	}

	/**
	*
	*/
	function arrayOutput( $array, $mask, $key = "" ) {
		$output = "";
		foreach( $array as $field => $value ) {
			if ( is_array( $value ) ) $value = implode(',', $value);
			if ( is_bool( $value ) ) $value = $value?'si':'no';
			if ( $key == "" || $key == $field )			
				$output .= sprintf( $mask, $field, $value);	
		}
		return $output;
	}

	/*
	*
	**
	function isCommandLineInterface()
	{
		return (php_sapi_name() != 'apache2handler');
	}
	*/

	/**
	* Call the main method.
	*/
//	echo getList($_GET);

	?>