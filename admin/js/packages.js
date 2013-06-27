function arrangeOptions( options ) {
	result = "";
	for (var i = 1; i < options.length; i++ ) {
		var opt = options(i);
		if ( isValidArg( opt )) {
			var args = opt.split( '=', 2 );
			var values = args[1].split( ',' );
			args[0] = values.length > 1? args[0]+'[]=' : args[0]+'=';
			for (var j = 0; j < values.length; j++) {
				result = result + args[0]+values[j] + " ";
			}
		}
	}
	return result;
}

function isValidArg( option ) {
	var regex = /^[a-zA-Z]+=[a-zA-Z0-9.\-_]*(,[a-zA-Z0-9_\-.]*)*$/;
	return option.match(regex);
}

function readFile( file ) {
    var result = null;
    
    if (fso.FileExists( file ))
    {
        if (fso.GetFile( file ).Size > 0)
        {
            var otfFile = fso.OpenTextFile( file );
            result = otfFile.ReadAll();
            otfFile.Close();
        }
        
        fso.DeleteFile( file );
    }
    
    return result;
}

var wsh = new ActiveXObject( "WScript.Shell" );
var fso = new ActiveXObject( "Scripting.FileSystemObject" );

var action = WScript.Arguments( 0 );
var options = arrangeOptions( WScript.Arguments );

fOut = fso.GetTempName();
fErr = fso.GetTempName();

var exitCode = wsh.Run( "cmd.exe /C php-cgi -n -f ..\\..\\php\\packages.php command="+action+" "+options+" > "+fOut, 0, true);
WScript.Echo( readFile( fOut ));
WScript.Quit( exitCode );