<?php

	include_once( __DIR__ . "/../admin/config.php" );

	//session_start();
	my_session_( "start" );
	if ( isset( $_GET['target'] ) && isset( $_SESSION['data'] ) && wrapDomain( $_GET['target'] ) == $_SESSION['target'] ) {
		echo json_encode( $_SESSION['data'] );
	} else {
		echo json_encode( array() );
	}

?>