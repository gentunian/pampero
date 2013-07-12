<?php
	
	//
	//
	if ( !defined( 'FROM_PACKAGES' )) {
		die( "Should be invoked by packages.php" );
	}

	/**
	*
	*/
	class PackageFinder {
		private $filter;
		private $path;
		private $manifestfilename;

		/**
		*
		*/
		public function __construct( $filter, $path, $manifestfilename ) {
			$this->filter = $filter;
			$this->path = $path;
			$this->manifestfilename = $manifestfilename;
		}

		/**
		*
		*/
		public function getPackages() {
			$di = new RecursiveDirectoryIterator( $this->path, FilesystemIterator::SKIP_DOTS );
			$it = new  RecursiveIteratorIterator( $di, RecursiveIteratorIterator::SELF_FIRST | RecursiveIteratorIterator::LEAVES_ONLY );
			$it->setMaxDepth( 1 );
			$data = array();
			foreach ( $it as $filename => $file ) {
				if ( $this->isPackageFile( $file )) {
					$packageData = $this->getPackageDataFromFile( $file );
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
		private function isPackageFile( $file ) {
			if ( strcasecmp( $this->manifestfilename, $file->getFilename()) != 0 )
				return false;

			json_decode( file_get_contents( $file ) );
			return ( json_last_error() == JSON_ERROR_NONE );
		}

		/**
	    * 
    	**/
    	private function getPackageDataFromFile( $file ) {
		    // Retrieve the text from the manifest.json file and
		    // decode the JSON to an Array Object.
    		$json = file_get_contents( $file->getPathname() );
    		$data = json_decode( $json, true );

		    // Get the array that has the installers arrays. $packageData
		    // now is an array that has installers arrays.
    		$packageData = $data[ key( $data ) ];

    		return $this->getFilteredData( $packageData );
    	}

    	/**
	    * Given a data array, return only those array that its keys
	    * mathces all keys in the $filter array.
	    **/
    	private function getFilteredData( $packageData ) {
    		$result = array();

    		foreach ($packageData as $key => $installerData) {
    			if ( $this->filterMatchArray( $installerData, $this->filter['filter'], $this->filter['options']['exact'] ) ) {
    				$result[] = $installerData;
    			}
    		}

    		return $result;
    	}

	    /**
	    *
	    */
	    private function filterMatchArray( $array, $filterArray, $exact = false ) {
	    	$matches = 0;

	    	foreach( $filterArray as $key => $value ) {
	    		$array1 = $this->wrapArray( $array[$key] );
	    		$array2 = $this->wrapArray( $value );
	    		if ( $array2[0] == NULL || $array2[0] == "*" || $this->shouldFilterArray( $array1, $array2, $exact ))
	    			$matches++;
	    	}

	    	return ( count( $filterArray ) == $matches );
	    }

	    /**
	    *
    	*/
	    private function wrapArray( $something ) {
	    	if ( !is_array( $something ) ) {
	    		return array( $something );
	    	}
	    	return $something;
	    }

    	/**
    	*
    	*/
    	private function shouldFilterArray( $array1, $array2, $exact ) {
    		foreach( $array1 as $key1 => $value1 ) {
    			foreach( $array2 as $key2 => $value2 ) {
    				if ( $this->shouldFilterValue( $value1, $value2, $exact )) {
    					return true;
    				}
    			}
    		}
    		return false;
    	}

    	/**
    	*
    	*/
    	private function shouldFilterValue( $value1, $value2, $exact ) {
    		if ( strlen($value1) < strlen($value2) ) {
    			$temp = $value2;
    			$value2 = $value1;
    			$value1 = $temp;
    		}
    		if ( $exact ) {
    			return ( strcasecmp( $value1, $value2 ) == 0 );
    		} else {
    			return ( stripos( $value1, $value2 ) !== false );
    		}
    	}

    	/**
    	*
    	*/
    	private function valueMatchValue( $value1, $value2, $exact ) {
    		if ( $exact ) {
    			return ( strcasecmp( $value1, $value2 ) == 0 );
    		} else {
    			if ( is_array($value1) ) {
    				$value1 = implode(',', $value1);
    			}
    			return ( stripos( $value1, $value2 ) !== false );
    		}
    	}
	}

	

	/**
	*
	*/
	function do_list( $args = NULL ) {

		// Create the filter in order to filter output
		$filterData = createFilter( $args );

		// Create a PackageFinder object passing by the filter, the path
		// where to find packages and the manifest file name it should find.
		$finder = new PackageFinder( $filterData, PACKAGES_DIR, MANIFEST_FILENAME );

		// Retrieve found packages
		$data = $finder->getPackages();

		// Return data back
		return $data;
	}

	/**
	*
	*/
	function createFilter( $args) {
		$opts = new Options(
        	$args,
        	array(
        		// Category named "options". This will group
        		// below arguments together within the "options"
        		// category, i.e.:
        		//        $listOpts->getOption("options");
        		// in order to get 'output' in options category do:
        		//        $listOpts->getOption("options-output");
        		"options" => array(
        			"target" => NULL,
        			"full" => false,
        			"exact" => false,
        			"output" => "jsonplain"
        			),
        		"filter" => array(
        			"id" => NULL,
        			"name" => NULL,
        			"arch" => NULL,
        			"os" => NULL,
        			"description" => NULL,
        			"installer" => NULL,
        			"installerArgs" => NULL
        			)
        		)
        	);

		// Create the filter array based on options
		$filter = array(
			"options" => $opts->getOption("options"),
			"filter" => $opts->getOption("filter")
			);

		// Get the target (if any)
		$target = $opts->getOption( "options-target");

		// If target was provided, we then filter the results based on target info.
		// If don't, we filter based on filter options.
		if ( $target != NULL ) {
	        // Create a credentials provider
			$credProv = new MyCredentialsProvider();

		    // Create a machine placeholder in order to retrieve
		    // host information
			$machine = new Machine( $target, $credProv );

			// Get system information
			$sysinfo = $machine->getSystemInfo();

			// Update filter information based on target but only if some options
			// were not provided. This allows us to add an option that could
			// override target architechture (for example, 32bit applications can
			// be installed in some 64bit OSes).
			if ( $filter['filter']['os'] == NULL )
				$filter['filter']['os'] = $sysinfo->getOSName();

			if ( $filter['filter']['arch'] == NULL )
				$filter['filter']['arch'] = $sysinfo->getOSArchitecture();
		}

		// Return the filter array
		return $filter;
	}

	/**
	*
	*/
	function do_list_output( $data, $args ) {
		$filterData = createFilter( $args );

		$output = "";
		$outputType = $filterData['options']['output'];

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
			$usedFilters = arrayOutput( $filterArray, "\t(%s => %s)\n" );
			$output .= "\n\nFilter:".(($usedFilters)? "\n$usedFilters\n": "\tNone\n");
			$output .="Opciones del filtro:\n".arrayOutput( $filterData['options'], "\t(%s => %s)\n");

			if ( $outputType == "html" ) {
				$output = "<pre>".$output."</pre>";
			}
		}

		return $output;
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
		if ( $key ) {
			$output = sprintf( $mask, $key, $array[$key] );	
		} else {
			foreach( $array as $field => $value ) {
				if ( $value != NULL ) {
					if ( is_array( $value ) ) $value = implode(',', $value);
					if ( is_bool( $value ) ) $value = $value?'Yes':'No';
					$output .= sprintf( $mask, $field, $value);	
				}
			}
		}
		return $output;
	}

?>