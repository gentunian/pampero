	<?php

	//
	//
	if ( !defined( 'FROM_PACKAGES' )) {
		die( "Should be invoked by packages.php" );
	}

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
	function createFilter( $args ) {
		$listOpts = new Options(
			$args,
			array(
				"option-output" => "jsonplain",
				"option-exact" => false,
				"option-full" => false,
				"filter-id" => NULL,
				"filter-name" => NULL,
				"filter-arch" => NULL,
				"filter-os" => NULL,
				"filter-description" => NULL,
				"filter-installer" => NULL,
				"filter-installerArgs" => NULL
				)
			);

		$opts = $listOpts->getActualOptions();

		$filter = array( 
			"options" => getArrayWithPrefixKeys( $opts, "option-" ),
			"filter"  => getArrayWithPrefixKeys( $opts, "filter-" )
			);
		    
		return $filter;
	}

	/**
	*
	**/
	function getArrayWithPrefixKeys( $array, $prefix) {
		$result = array();
		foreach ($array as $key => $value) {
			if ( keyHasPrefix( $key, $prefix ) == 0) {
				$result[str_replace( $prefix, "", $key )] = $value;
			}
		}

		return $result;
	}

	/**
	*
	**/
	function keyHasPrefix( $key, $prefixKey ) {
		return strncmp( $key, $prefixKey, strlen( $prefixKey ));
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
		if ( strcasecmp( MANIFEST_FILENAME, $file->getFilename()) != 0 )
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
	function do_list_output( $data, $args ) {
		$filterData = createFilter( $args );

		$output = "";
		$outputType = $filterData->output;

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

?>