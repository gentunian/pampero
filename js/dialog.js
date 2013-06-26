function ProgressDialog() {

	this.setTitle = function( title ) {
		$( '.dialog-title' ).html( title );
	}

	this.setMessage = function( msg ) {
		$( '#dialog-message' ).html( '<p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span><p>'+msg );
	}

	this.setContent = function( content ) {

	}

	this.setProgress = function( percent ) {
		$( '.bar' ).width( percent );
	}

	this.setStatus = function( status ) {
		var button = $( '.button' );
		var bar = $( '.bar' );
		var state = status.toLowerCase();
		button.removeClass( "error_state ok_state busy_state" );
		bar.removeClass( "error_state ok_state busy_state" );
		if ( state == "ok_state" || state == "error_state" || state == "busy_state" ) {
			button.addClass( state );
			bar.addClass( state );
		}
	}

	this.show = function( title, message ) {
		var maskHeight = $( document ).height();  
		var maskWidth = $( window ).width();
		var dialogTop =  ( maskHeight/3 ) - ($( '#dialog-box' ).height());  
		var dialogLeft = ( maskWidth/2 ) - ($( '#dialog-box' ).width()/2); 
		$( '#dialog-overlay' ).css({
			height:maskHeight,
			width:maskWidth
		}).show();
		$( '#dialog-box' ).css({
			top:150,
			left:dialogLeft
		}).show();
		this.setTitle( title );
		this.setMessage( message );
		this.setProgress( "0%" );
	}
}