var Dialog = {

	createProgressDialog: function( id, options ) {
		// Creates a normal dialog
		var pd = this.createDialog( id, options );
				
		// Wraps "progress" class within title
		$( '.dialog-title' ).wrap( '<div class="progress">' );

		// Inserts "bar" class to "progress" class element
		$( '.progress' ).prepend( '<div class="bar"/>' );

		// Max value initial set to 0
		pd.max = 0;

		// Step value initial set to 1
		pd.step = 1;

		// Current value inital set to 0
		pd.value = 0;

	    // AJAX settings
	 	pd.ajaxSettings = options.ajaxSettings;

	 	pd.pollingInterval = 1500;

		// setValue() will update progress bar upon current value and max value
		// as a percentage value
		pd.setValue = function( value ) {
			if ( value > this.max ) value = this.max;
			this.value = value;
			this.setProgress( ((this.value/this.max)*100)+"%" );
		}

		// increment() will add this.step to the current value
		pd.increment = function() {
			this.value = this.value + this.step;
			this.setValue( this.value );
		}

		// Adds setProgress() method
		pd.setProgress = function( percent ) {
			$( '.bar' ).width( percent );
		}

		// Adds getProgres() method
		pd.getProgress= function() {
			var width = $( '.bar' ).width();
			var parentWidth = $( '.bar' ).offsetParent().width();
			var percent = 100*width/parentWidth;
			return percent
		}

		// Rewrites setState()
		pd.setState = function( status ) {
			var button = $( '.button' );
			var bar = $( '.bar' );
			var state = status.toLowerCase();
			button.removeClass( "error_state ok_state busy_state" );
			bar.removeClass( "error_state ok_state busy_state" );
			button.addClass( state );
			bar.addClass( state );
		}

		pd.startProgressQuery = function() {
			pd.intervalId = setInterval( function() {
				$.ajax( pd.ajaxSettings );
			}, pd.pollingInterval );
		}

		pd.stopProgressQuery = function() {
			clearInterval( pd.intervalId );
		}

		return pd;
	},
	createDialog: function( id, options ) {
		// Removes childs nodes of element id if any
		$( id ).empty();
		// Removes an element with "dialog-overlay" id if exists
		$( '#dialog-overlay' ).remove();
		// Prepends a div to be used as overlay
		$( 'body' ).prepend( '<div id="dialog-overlay"/>' );

		// Append divs to id element
		$( id ).append( '<div class="dialog-title">'+options.title+'</div>'
			+'<div class="dialog-content"><div id="dialog-message">'
			+'<p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span></p><span>'+options.message+'</span></div>'
			+'<div id="content"></div><div class="button">'+options.closeText+'</div>'
			);

		// The dialog object
		var dialog = {
			id: id,
			show: function() {
				var maskHeight = $( document ).height();  
				var maskWidth = $( window ).width();
				var dialogTop =  ( maskHeight/3 ) - ($( this.id ).height());  
				var dialogLeft = ( maskWidth/2 ) - ($( this.id ).width()/2); 
				$( '#dialog-overlay' ).css({
					height:maskHeight,
					width:maskWidth
				}).show();
				$( this.id ).css({
					top:150,
					left:dialogLeft
				}).show();
			},
			hide: function() {
				$( '#dialog-overlay,'+this.id ).hide();
				//$( this.id ).hide();
			},
			setTitle: function( title ) {
				$( '.dialog-title' ).text( title );
			},
			setMessage: function( msg ) {
				$( this.id+'>span:last-child' ).text( msg );
			},
			setContent: function( html ) {
				$( '#content' ).html( html );
			},
			setState: function( status ) {
				var button = $( '.button' );
				var state = status.toLowerCase();
				button.removeClass( "error_state ok_state busy_state" );
				button.addClass( state );
			}
		}

		// Dialog cannot be closed in busy state
		$( '.button' ).click( function () {
			console.log(this);
			if (! $(this).hasClass( 'busy_state' )) {
				dialog.hide();
			}
		});

		dialog.setState( "ok_state" );

		return dialog;
	}
}