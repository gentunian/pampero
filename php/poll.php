<?php

	session_start();
	if ( isset( $_GET['target'] ) && isset( $_SESSION['data'] ) && $_GET['target'] == $_SESSION['target'] ) {
		echo json_encode( $_SESSION['data'] );
		session_write_close();
	} else {
		echo json_encode( array("nada"=>1));
	}

?>