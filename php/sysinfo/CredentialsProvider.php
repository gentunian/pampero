<?php

	/**
	*
	*/
	interface CredentialsProvider {
		function getCredentials( $hostname );
	}

?>