<?php

/**
*
*/
class Utils
{

	final private function __construct() {}
	final private function __clone() {}
	static private $logger;

	public static function log($msg, $sev = KLogger::INFO)
	{
		if (self::$logger == NULL)
			self::$logger = new KLogger(LOG_DIR, KLogger::INFO);

		self::$logger->log($msg, $sev);
	}

	/**
	*
	*/
	public static function my_session_( $sufix, $args = NULL )
	{
		if (! self::isCommandLineInterface() )
			if ( is_array($args)) {
				return call_user_func_array( "session_$sufix", $args );
			} else {
				return call_user_func( "session_$sufix" );
			}
		else
			return true;
	}

	public static function getDefaultOutput()
	{
		if ( self::isCommandLineInterface() ) {
			$defaultOutput = CONSOLE_OUTPUT;
		} elseif ( Utils::isAJAXRequest() ) {
			$defaultOutput = JSON_OUTPUT;
		} else {
			$defaultOutput = HTML_OUTPUT;
		}
		return $defaultOutput;
	}

	public static function isCommandLineInterface()
	{
		$str = php_sapi_name();
		return ( stripos( $str, "cli" ) !== FALSE || stripos( $str, "cgi" ) !== FALSE );
	}

	/**
	* TODO: Improved this solution by using session cookies
	*/
	public static function isAJAXRequest()
	{
		return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	public static function getInvokingIP()
	{
		$ip = "";
	    // From command line should be localhost if 'target' option
		// was not provided
		if ( self::isCommandLineInterface() ) {
			$ip = gethostbyname(gethostname());

		} else {
			// From http request use remote IP
			if ( isset( $_SERVER['REMOTE_ADDR'] ))
				$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	public static function getInvokingHostname( $removeDomain = true )
	{
		$hostname = "";

		// From command line should be localhost if 'target' option
		// was not provided
		if ( self::isCommandLineInterface() ) {
			$hostname = gethostname();

		} else {
			// From http request use remote IP
			if ( isset( $_SERVER['REMOTE_ADDR'] ))
				$hostname = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );
		}

		if ( $hostname && $removeDomain ) {
			$hostname = self::removeDomainFromHostname( $hostname );
		}

		return $hostname;
	}

	public static function getSystemType( $host )
	{
		$display_errors = ini_get( 'display_errors' );
		ini_set('display_errors', '0');

		require_once "Net/Ping.php";

		$ping = Net_Ping::factory();
		$ping->setArgs( array( 'count'=> 1 ));
		$output = $ping->ping( $host );

		$system = "Unknown";
		if ( $output->_ttl == NULL && $output->_target_ip == NULL ) {
			$system = ucfirst( strtolower( NATIVE_OS ));
		} elseif ( $output->_ttl != NULL ) {
			$system =  ( $output->_ttl > 64 )? OS_WINDOWS : OS_UNIX;
		}

		ini_set('display_errors', $display_errors);
		return $system;
	}

	public static function removeDomainFromHostname( $host )
	{
		return preg_replace( "/([^.]*).*/", "$1", $host);
	}

	public static function array_merge_non_null( $array1, $array2 )
	{
		$result = array_merge( $array1, $array2 );
		$result = array_filter( $result, 'is_not_null' );
		return $result;
	}
}

function is_not_null( $var )
{
	return !is_null( $var );
}
?>