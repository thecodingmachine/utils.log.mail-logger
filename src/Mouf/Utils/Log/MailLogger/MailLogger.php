<?php
namespace Mouf\Utils\Log\MailLogger;

use Mouf\Utils\Log\LogInterface;
use \Exception;
/**
 * A logger class that writes messages into the php error_log.
 *
 * @Component
 */
class MailLogger implements LogInterface {
	
	/**
	 * The service used to send mails.
	 * 
	 * @Property
	 * @Compulsory
	 * @var MailServiceInterface
	 */
	public $mailService;
	
	/**
	 * The model of the mail sent when an error occurs.
	 * This is in this object that you will specify the mail address.
	 * 
	 * @Property
	 * @Compulsory
	 * @var ErrorMail
	 */
	public $mail;
	
	/**
	 * If true, errors will be aggregated in one big mail that is sent at the end
	 * of the script. If false, each error will trigger a mail.
	 *
	 * @Property
	 * @var bool
	 */
	public $aggregateErrorsInOneMail = true;
	
	/**
	 * The maximum number of errors that can be put in one mail.
	 * If this number is reached, additional errors will be discarded.
	 * 
	 * @Property
	 * @Compulsory
	 * @var int
	 */
	public $maxNbErrorsInOneMail = 30;
	
	/**
	 * Number of errors displayed so far.
	 * 
	 * @var int
	 */
	private $nbErrors = 0;
	
	/**
	 * A text that will be displayed as a prefix to the title of the mail.
	 * Very useful to add informations about your environement for instance.
	 *
	 * @Property
	 * @var string
	 */
	public $titlePrefix;
	
	/**
	 * The text body of the email (only used if aggregateErrorsInOneMail is true).
	 * 
	 * @var string
	 */
	public $bodyText = null;
	
	/**
	 * The HTML body of the email (only used if aggregateErrorsInOneMail is true).
	 * 
	 * @var string
	 */
	public $bodyHtml = null;
	
	const TRACE = 1;
	const DEBUG = 2;
	const INFO = 3;
	const WARN = 4;
	const ERROR = 5;
	const FATAL = 6;
	
	/**
	 * The minimum level that will be tracked by this logger.
	 * Any log with a level below this level will not be logger.
	 *
	 * @Property
	 * @Compulsory 
	 * @OneOf "1","2","3","4","5","6"
	 * @OneOfText "TRACE","DEBUG","INFO","WARN","ERROR","FATAL"
	 * @var int
	 */
	public $level;
	
	/**
	 * The prefix of the text mail (contains the URL of the web page displayed)
	 * @var string
	 */
	public $mailTextPrefix;
	
	/**
	* The prefix of the HTML mail (contains the URL of the web page displayed)
	* @var string
	*/
	public $mailHTMLPrefix;
	
	public function trace($string, Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::TRACE) {
			$this->logMessage("TRACE", $string, $e, $additional_parameters);
		}
	}
	public function debug($string, Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::DEBUG) {
			$this->logMessage("DEBUG", $string, $e, $additional_parameters);
		}
	}
	public function info($string, Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::INFO) {
			$this->logMessage("INFO", $string, $e, $additional_parameters);
		}
	}
	public function warn($string, Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::WARN) {
			$this->logMessage("WARN", $string, $e, $additional_parameters);
		}
	}
	public function error($string, Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::ERROR) {
			$this->logMessage("ERROR", $string, $e, $additional_parameters);
		}
	}
	public function fatal($string, Exception $e=null, array $additional_parameters=array()) {
		if($this->level<=self::FATAL) {
			$this->logMessage("FATAL", $string, $e, $additional_parameters);
		}
	}

	private function logMessage($level, $string, $e=null, array $additional_parameters=array()) {
		
		$this->computeMailPrefix();
		$this->nbErrors++;
		if ($this->nbErrors < $this->maxNbErrorsInOneMail + 1) {
			if ($e == null) {
				if (!$string instanceof Exception) {
					$trace = debug_backtrace();
					$msg = $level.': '.$string;
					$msgHtml = $level.': '.$string;
				} else {
					$msg = self::getTextForException($string);
					$msgHtml = self::getHtmlForException($string);
				}
			} else {
				$trace = debug_backtrace();
				$msg = $level.': '.$trace[1]['file']."(".$trace[1]['line'].") ".(isset($trace[2])?($trace[2]['class'].$trace[2]['type'].$trace[2]['function']):"")." -> ".$string."\n".self::getTextForException($e);
				$msgHtml = $level.': '.$trace[1]['file']."(".$trace[1]['line'].") ".(isset($trace[2])?($trace[2]['class'].$trace[2]['type'].$trace[2]['function']):"")." -> ".$string."\n".self::getHtmlForException($e);
			}
		} elseif ($this->nbErrors == $this->maxNbErrorsInOneMail + 1) {
			$msg = "Maximum number of errors displayed in one mail reached... further errors are discarded.\n";
			$msgHtml = "Maximum number of errors displayed in one mail reached... further errors are discarded.<br/>";
		} else {
			return;
		}
				
		if (!$this->aggregateErrorsInOneMail) {
			$title = $this->titlePrefix;
			$title .= " An error occured in your application. Error level: ".$level.". ".substr($string,0,20);
			if (is_string($string)) {
				if (strlen($string)<20) {
					$title .= $string;
				} else {
					$title .= substr($string, 0, 19)."...";
				}
			}
			
			$this->mail->setTitle($title);
			
			$this->mail->setBodyText($this->mailTextPrefix.$msg);
			$this->mail->setBodyHtml($this->mailHTMLPrefix.$msgHtml);
			$this->mailService->send($this->mail);
		} else {
			// If this is the first time an error is logged, let's register the end function to be called.
			if ($this->bodyText == null) {
				register_shutdown_function(array($this, "sendAggregatedMail"));
			}
			$this->bodyText .= $msg."\n";
			$this->bodyHtml .= $msgHtml."<br/>\n";
		}
	}
	
	public function computeMailPrefix() {
		if ($this->mailTextPrefix) {
			return;
		}
		
		if (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['SERVER_PORT'])) {
			if (isset($_SERVER['HTTPS'])) {
				$url = "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			} else {
				if ($_SERVER['SERVER_PORT'] != 80) {
					$url = "http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
				} else {
					$url = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
				}
			}
				
			$this->mailTextPrefix = "URL: ".$url."\n\n"; 
			$this->mailHTMLPrefix = "URL: <a href='".htmlentities($url, ENT_QUOTES)."'>".htmlentities($url)."</a><br/><br/>";
		} else {
			$this->mailTextPrefix = "Script: ".$_SERVER['SCRIPT_FILENAME']."\n\n";
			$this->mailHTMLPrefix = "Script: ".htmlentities($_SERVER['SCRIPT_FILENAME'], ENT_QUOTES)."<br/><br/>";
		}
	}
	
	/**
	 * At the very end of the script, this will send all logged messages.
	 */
	public function sendAggregatedMail() {
		$title = $this->titlePrefix;
		$title .= " Errors occured in your application.";
		
		$this->mail->setTitle($title);
		$this->mail->setBodyText($this->mailTextPrefix.$this->bodyText);
		$this->mail->setBodyHtml($this->mailHTMLPrefix.$this->bodyHtml);
		$this->mailService->send($this->mail);
	}
	
	/**
	* Returns the Exception Backtrace as a nice HTML view.
	*
	* @param unknown_type $backtrace
	* @return unknown
	*/
	private static function getHTMLBackTrace($backtrace) {
		$str = '';
	
		foreach ($backtrace as $step) {
			if ($step['function']!='getHTMLBackTrace' && $step['function']!='handle_error')
			{
				$str .= '<tr><td style="border-bottom: 1px solid #EEEEEE">';
				$str .= ((isset($step['class']))?htmlspecialchars($step['class'], ENT_NOQUOTES, "UTF-8"):'').
				((isset($step['type']))?htmlspecialchars($step['type'], ENT_NOQUOTES, "UTF-8"):'').htmlspecialchars($step['function'], ENT_NOQUOTES, "UTF-8").'(';
	
				if (is_array($step['args'])) {
					$drawn = false;
					$params = '';
					foreach ( $step['args'] as $param)
					{
						$params .= self::getPhpVariableAsText($param);
						//$params .= var_export($param, true);
						$params .= ', ';
						$drawn = true;
					}
					$str .= htmlspecialchars($params, ENT_NOQUOTES, "UTF-8");
					if ($drawn == true)
					$str = substr($str, 0, strlen($str)-2);
				}
				$str .= ')';
				$str .= '</td><td style="border-bottom: 1px solid #EEEEEE">';
				$str .= ((isset($step['file']))?htmlspecialchars($step['file'], ENT_NOQUOTES, "UTF-8"):'');
				$str .= '</td><td style="border-bottom: 1px solid #EEEEEE">';
				$str .= ((isset($step['line']))?$step['line']:'');
				$str .= '</td></tr>';
			}
		}
	
		return $str;
	}
	
	/**
	 * Function called to display an exception if it occurs.
	 * It will make sure to purge anything in the buffer before calling the exception displayer.
	 *
	 * @param Exception $exception
	 */
	static function getHtmlForException(Exception $exception) {
		//global $sys_error_reporting_mail;
		//global $sys_error_messages;
		$msg='';
	
		$msg = '<table>';
	
	
		$display_errors = ini_get('display_errors');
		$color = "#FF0000";
		$type = "Uncaught ".get_class($exception);
		if ($exception->getCode() != null)
		$type.=" with error code ".$exception->getCode();
	
		$msg .= "<tr><td colspan='3' style='background-color:$color; color:white; text-align:center'><b>$type</b></td></tr>";
	
		$msg .= "<tr><td style='background-color:#AAAAAA; color:white; text-align:center'>Context/Message</td>";
		$msg .= "<td style='background-color:#AAAAAA; color:white; text-align:center'>File</td>";
		$msg .= "<td style='background-color:#AAAAAA; color:white; text-align:center'>Line</td></tr>";
	
		$msg .= "<tr><td style='background-color:#EEEEEE; color:black'><b>".nl2br($exception->getMessage())."</b></td>";
		$msg .= "<td style='background-color:#EEEEEE; color:black'>".$exception->getFile()."</td>";
		$msg .= "<td style='background-color:#EEEEEE; color:black'>".$exception->getLine()."</td></tr>";
		$msg .= self::getHTMLBackTrace($exception->getTrace());
		$msg .= "</table>";
	
		return $msg;
	
	}
	
	/**
	 * Function called to display an exception if it occurs.
	 * It will make sure to purge anything in the buffer before calling the exception displayer.
	 *
	 * @param Exception $exception
	 */
	static function getTextForException(Exception $exception) {
		// Now, let's compute the same message, but without the HTML markup for the error log.
		$textTrace = "Message: ".$exception->getMessage()."\n";
		$textTrace .= "File: ".$exception->getFile()."\n";
		$textTrace .= "Line: ".$exception->getLine()."\n";
		$textTrace .= "Stacktrace:\n";
		$textTrace .= self::getTextBackTrace($exception->getTrace());
		return $textTrace;
	}
	/**
	 * Returns the Exception Backtrace as a text string.
	 *
	 * @param unknown_type $backtrace
	 * @return unknown
	 */
	static private function getTextBackTrace($backtrace) {
		$str = '';
	
		foreach ($backtrace as $step) {
			if ($step['function']!='getTextBackTrace' && $step['function']!='handle_error')
			{
				if (isset($step['file']) && isset($step['line'])) {
					$str .= "In ".$step['file'] . " at line ".$step['line'].": ";
				}
				if (isset($step['class']) && isset($step['type']) && isset($step['function'])) {
					$str .= $step['class'].$step['type'].$step['function'].'(';
				}
	
				if (is_array($step['args'])) {
					$drawn = false;
					$params = '';
					foreach ( $step['args'] as $param)
					{
						$params .= self::getPhpVariableAsText($param);
						//$params .= var_export($param, true);
						$params .= ', ';
						$drawn = true;
					}
					$str .= $params;
					if ($drawn == true)
					$str = substr($str, 0, strlen($str)-2);
				}
				$str .= ')';
				$str .= "\n";
			}
		}
	
		return $str;
	}
	
	/**
	 * Used by the debug function to display a nice view of the parameters.
	 *
	 * @param mixed $var
	 * @return string
	 */
	public static function getPhpVariableAsText($var, $depth = 0) {
		if( is_string( $var ) )
		return( '"'.str_replace( array("\x00", "\x0a", "\x0d", "\x1a", "\x09"), array('\0', '\n', '\r', '\Z', '\t'), $var ).'"' );
		else if( is_int( $var ) || is_float( $var ) )
		{
			return( $var );
		}
		else if( is_bool( $var ) )
		{
			if( $var )
			return( 'true' );
			else
			return( 'false' );
		}
		else if( is_array( $var ) )
		{
			$result = 'array( ';
			$depth++;
			if ($depth < 2) {
				$comma = '';
				foreach( $var as $key => $val )
				{
					$result .= $comma.self::getPhpVariableAsText( $key ).' => '.self::getPhpVariableAsText( $val, $depth );
					$comma = ', ';
				}
			} else {
				$result .= "skipped";
			}
			$result .= ' )';
			return( $result );
		}

		elseif (is_object($var)) return "Object ".get_class($var);
		elseif(is_resource($var)) return "Resource ".get_resource_type($var);
		return "Unknown type variable";
	}
	
	
}

?>