<?php
/**
 * common Functions class
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License version 2, or (at your option) any later version
 * @version   SVN: $Id: class.CommonFunctions.inc.php 699 2012-09-15 11:57:13Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */
 /**
 * class with common functions used in all places
 *
 * @category  PHP
 * @package   PSI
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License version 2, or (at your option) any later version
 * @version   Release: 3.0
 * @link      http://phpsysinfo.sourceforge.net
 */
class CommonFunctions
{
    /**
     * holds codepage for chcp
     *
     * @var integer
     */
    private static $_cp = null;

    /**
     * value of checking run as administrator
     *
     * @var boolean
     */
    private static $_asadmin = null;

    public static function setcp($cp)
    {
        self::$_cp = $cp;
    }

    public static function getcp()
    {
        return self::$_cp;
    }

    public static function isAdmin()
    {
        if (self::$_asadmin == null) {
            if (PSI_OS == 'WINNT') {
                $strBuf = '';
                self::executeProgram('sfc', '2>&1', $strBuf, false); // 'net session' for detection does not work if "Server" (LanmanServer) service is stopped
                if (preg_match('/^\/SCANNOW\s/m', preg_replace('/(\x00)/', '', $strBuf))) { // SCANNOW checking - also if Unicode
                    self::$_asadmin = true;
                } else {
                    self::$_asadmin = false;
                }
            } else {
                self::$_asadmin = false;
            }
        }

        return self::$_asadmin;
    }

    private static function _parse_log_file($string)
    {
        if (defined('PSI_LOG') && is_string(PSI_LOG) && (strlen(PSI_LOG)>0) && ((substr(PSI_LOG, 0, 1)=="-") || (substr(PSI_LOG, 0, 1)=="+"))) {
            $log_file = substr(PSI_LOG, 1);
            if (file_exists($log_file)) {
                $contents = @file_get_contents($log_file);
                if ($contents && preg_match("/^\-\-\-[^-\r\n]+\-\-\- ".preg_quote($string, '/')."\r?\n/m", $contents, $matches, PREG_OFFSET_CAPTURE)) {
                    $findIndex = $matches[0][1];
                    if (preg_match("/\r?\n/m", $contents, $matches, PREG_OFFSET_CAPTURE, $findIndex)) {
                        $startIndex = $matches[0][1]+1;
                        if (preg_match("/^\-\-\-[^-\r\n]+\-\-\- /m", $contents, $matches, PREG_OFFSET_CAPTURE, $startIndex)) {
                            $stopIndex = $matches[0][1];

                            return substr($contents, $startIndex, $stopIndex-$startIndex);
                        } else {
                            return substr($contents, $startIndex);
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Find a system program, do also path checking when not running on WINNT
     * on WINNT we simply return the name with the exe extension to the program name
     *
     * @param string $strProgram name of the program
     *
     * @return string|null complete path and name of the program
     */
    public static function _findProgram($strProgram)
    {
        $path_parts = pathinfo($strProgram);
        if (empty($path_parts['basename'])) {
            return null;
        }
        $arrPath = array();

        if (empty($path_parts['dirname']) || ($path_parts['dirname'] == '.')) {
            if ((PSI_OS == 'WINNT') && empty($path_parts['extension'])) {
                $strProgram .= '.exe';
                $path_parts = pathinfo($strProgram);
            }
            if (PSI_OS == 'WINNT') {
                if (self::readenv('Path', $serverpath)) {
                    $arrPath = preg_split('/;/', $serverpath, -1, PREG_SPLIT_NO_EMPTY);
                }
            } else {
                if (self::readenv('PATH', $serverpath)) {
                    $arrPath = preg_split('/:/', $serverpath, -1, PREG_SPLIT_NO_EMPTY);
                }
            }
            if (defined('PSI_UNAMEO') && (PSI_UNAMEO === 'Android') && !empty($arrPath)) {
                array_push($arrPath, '/system/bin'); // Termux patch
            }
            if (defined('PSI_ADD_PATHS') && is_string(PSI_ADD_PATHS)) {
                if (preg_match(ARRAY_EXP, PSI_ADD_PATHS)) {
                    $arrPath = array_merge(eval(PSI_ADD_PATHS), $arrPath); // In this order so $addpaths is before $arrPath when looking for a program
                } else {
                    $arrPath = array_merge(array(PSI_ADD_PATHS), $arrPath); // In this order so $addpaths is before $arrPath when looking for a program
                }
            }
        } else { //directory defined
            array_push($arrPath, $path_parts['dirname']);
            $strProgram = $path_parts['basename'];
        }

        //add some default paths if we still have no paths here
        if (empty($arrPath) && (PSI_OS != 'WINNT')) {
            if (PSI_OS == 'Android') {
                array_push($arrPath, '/system/bin');
            } else {
                array_push($arrPath, '/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
            }
        }

        $exceptPath = "";
        if ((PSI_OS == 'WINNT') && self::readenv('WinDir', $windir)) {
            foreach ($arrPath as $strPath) {
                if ((strtolower($strPath) == strtolower($windir)."\\system32") && is_dir($windir."\\SysWOW64")) {
                    if (is_dir($windir."\\sysnative\\drivers")) { // or strlen(decbin(~0)) == 32; is_dir($windir."\\sysnative") sometimes does not work
                        $exceptPath = $windir."\\sysnative"; //32-bit PHP on 64-bit Windows
                    } else {
                        $exceptPath = $windir."\\SysWOW64"; //64-bit PHP on 64-bit Windows
                    }
                    array_push($arrPath, $exceptPath);
                    break;
                }
            }
        } elseif (PSI_OS == 'Android') {
            $exceptPath = '/system/bin';
        }

        foreach ($arrPath as $strPath) {
            // Path with and without trailing slash
            if (PSI_OS == 'WINNT') {
                $strPath = rtrim($strPath, "\\");
                $strPathS = $strPath."\\";
            } else {
                $strPath = rtrim($strPath, "/");
                $strPathS = $strPath."/";
            }
            if (($strPath !== $exceptPath) && !is_dir($strPath)) {
                continue;
            }
            $strProgrammpath = $strPathS.$strProgram;
            if (is_executable($strProgrammpath) || ((PSI_OS == 'WINNT') && (strtolower($path_parts['extension']) == 'py'))) {
                return $strProgrammpath;
            }
        }

        return null;
    }

    /**
     * Execute a system program. return a trim()'d result.
     * does very crude pipe and multiple commands (on WinNT) checking.  you need ' | ' or ' & ' for it to work
     * ie $program = CommonFunctions::executeProgram('netstat', '-anp | grep LIST');
     * NOT $program = CommonFunctions::executeProgram('netstat', '-anp|grep LIST');
     *
     * @param string  $strProgramname name of the program
     * @param string  $strArgs        arguments to the program
     * @param string  &$strBuffer     output of the command
     * @param boolean $booErrorRep    en- or disables the reporting of errors which should be logged
     * @param integer $timeout        timeout value in seconds (default value is PSI_EXEC_TIMEOUT_INT)
     *
     * @return boolean command successfull or not
     */
    public static function executeProgram($strProgramname, $strArgs, &$strBuffer, $booErrorRep = true, $timeout = PSI_EXEC_TIMEOUT_INT)
    {
        if (defined('PSI_LOG') && is_string(PSI_LOG) && (strlen(PSI_LOG)>0) && ((substr(PSI_LOG, 0, 1)=="-") || (substr(PSI_LOG, 0, 1)=="+"))) {
            $out = self::_parse_log_file("Executing: ".trim($strProgramname.' '.$strArgs));
            if ($out == false) {
                if (substr(PSI_LOG, 0, 1)=="-") {
                    $strBuffer = '';

                    return false;
                }
            } else {
                $strBuffer = $out;

                return true;
            }
        }

        if ((PSI_OS != 'WINNT') && preg_match('/^([^=]+=[^ \t]+)[ \t]+(.*)$/', $strProgramname, $strmatch)) {
            $strSet = $strmatch[1].' ';
            $strProgramname = $strmatch[2];
        } else {
            $strSet = '';
        }
        $strProgram = self::_findProgram($strProgramname);
        $error = PSI_Error::singleton();
        if (!$strProgram) {
            if ($booErrorRep) {
                $error->addError('find_program("'.$strProgramname.'")', 'program not found on the machine');
            }

            return false;
        } else {
            if (preg_match('/\s/', $strProgram)) {
                $strProgram = '"'.$strProgram.'"';
            }
        }

        if ((PSI_OS != 'WINNT') && defined('PSI_SUDO_COMMANDS') && is_string(PSI_SUDO_COMMANDS)) {
            if (preg_match(ARRAY_EXP, PSI_SUDO_COMMANDS)) {
                $sudocommands = eval(PSI_SUDO_COMMANDS);
            } else {
                $sudocommands = array(PSI_SUDO_COMMANDS);
            }
            if (in_array($strProgramname, $sudocommands)) {
                $sudoProgram = self::_findProgram("sudo");
                if (!$sudoProgram) {
                    if ($booErrorRep) {
                        $error->addError('find_program("sudo")', 'program not found on the machine');
                    }

                    return false;
                } else {
                    if (preg_match('/\s/', $sudoProgram)) {
                        $strProgram = '"'.$sudoProgram.'" '.$strProgram;
                    } else {
                        $strProgram = $sudoProgram.' '.$strProgram;
                    }
                }
            }
        }

        // see if we've gotten a | or &, if we have we need to do path checking on the cmd
        if ($strArgs) {
            $arrArgs = preg_split('/ /', $strArgs, -1, PREG_SPLIT_NO_EMPTY);
            for ($i = 0, $cnt_args = count($arrArgs); $i < $cnt_args; $i++) {
                if (($arrArgs[$i] == '|') || ($arrArgs[$i] == '&')) {
                    $strCmd = $arrArgs[$i + 1];
                    $strNewcmd = self::_findProgram($strCmd);
                    if ($arrArgs[$i] == '|') {
                        $strArgs = preg_replace('/\| '.$strCmd.'/', '| "'.$strNewcmd.'"', $strArgs);
                    } else {
                        $strArgs = preg_replace('/& '.$strCmd.'/', '& "'.$strNewcmd.'"', $strArgs);
                    }
                }
            }
            $strArgs = ' '.$strArgs;
        }

        $strBuffer = '';
        $strError = '';
        $pipes = array();
        $descriptorspec = array(0=>array("pipe", "r"), 1=>array("pipe", "w"), 2=>array("pipe", "w"));
        if (defined("PSI_MODE_POPEN") && PSI_MODE_POPEN === true) {
            if (PSI_OS == 'WINNT') {
                $process = $pipes[1] = popen($strSet.$strProgram.$strArgs." 2>nul", "r");
            } else {
                $process = $pipes[1] = popen($strSet.$strProgram.$strArgs." 2>/dev/null", "r");
            }
        } else {
            $process = proc_open($strSet.$strProgram.$strArgs, $descriptorspec, $pipes);
        }
        if (is_resource($process)) {
            $te = self::_timeoutfgets($pipes, $strBuffer, $strError, $timeout);
            if (defined("PSI_MODE_POPEN") && PSI_MODE_POPEN === true) {
                $return_value = pclose($pipes[1]);
            } else {
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                // It is important that you close any pipes before calling
                // proc_close in order to avoid a deadlock
                if ($te) {
                    proc_terminate($process); // proc_close tends to hang if the process is timing out
                    $return_value = 0;
                } else {
                    $return_value = proc_close($process);
                }
            }
        } else {
            if ($booErrorRep) {
                $error->addError($strProgram, "\nOpen process error");
            }

            return false;
        }
        $strError = trim($strError);
        $strBuffer = trim($strBuffer);
        if (defined('PSI_LOG') && is_string(PSI_LOG) && (strlen(PSI_LOG)>0) && (substr(PSI_LOG, 0, 1)!="-") && (substr(PSI_LOG, 0, 1)!="+")) {
            error_log("---".gmdate('r T')."--- Executing: ".trim($strProgramname.$strArgs)."\n".$strBuffer."\n", 3, PSI_LOG);
        }
        if (! empty($strError)) {
            if ($booErrorRep) {
                $error->addError($strProgram, $strError."\nReturn value: ".$return_value);
            }

            return $return_value == 0;
        }

        return true;
    }

    /**
     * read a one-line value from a file with a similar name
     *
     * @return value if successfull or null if not
     */
    public static function rolv($similarFileName, $match = "//", $replace = "")
    {
        $filename = preg_replace($match, $replace, $similarFileName);
        if (self::fileexists($filename) && self::rfts($filename, $buf, 1, 4096, false) && (($buf=trim($buf)) != "")) {
            return $buf;
        } else {
            return null;
        }
    }

    /**
     * read data from array $_SERVER
     *
     * @param string $strElem    element of array
     * @param string &$strBuffer output of the command
     *
     * @return string
     */
    public static function readenv($strElem, &$strBuffer)
    {
        $strBuffer = '';
        if (PSI_OS == 'WINNT') { //case insensitive
            if (isset($_SERVER)) {
                foreach ($_SERVER as $index=>$value) {
                    if (is_string($value) && (trim($value) !== '') && (strtolower($index) === strtolower($strElem))) {
                        $strBuffer = $value;

                        return true;
                    }
                }
            }
        } else {
            if (isset($_SERVER[$strElem]) && is_string($value = $_SERVER[$strElem]) && (trim($value) !== '')) {
                $strBuffer = $value;

                return true;
            }
        }

        return false;
    }

    /**
     * read a file and return the content as a string
     *
     * @param string  $strFileName name of the file which should be read
     * @param string  &$strRet     content of the file (reference)
     * @param integer $intLines    control how many lines should be read
     * @param integer $intBytes    control how many bytes of each line should be read
     * @param boolean $booErrorRep en- or disables the reporting of errors which should be logged
     *
     * @return boolean command successfull or not
     */
    public static function rfts($strFileName, &$strRet, $intLines = 0, $intBytes = 4096, $booErrorRep = true)
    {
        if (defined('PSI_LOG') && is_string(PSI_LOG) && (strlen(PSI_LOG)>0) && ((substr(PSI_LOG, 0, 1)=="-") || (substr(PSI_LOG, 0, 1)=="+"))) {
            $out = self::_parse_log_file("Reading: ".$strFileName);
            if ($out == false) {
                if (substr(PSI_LOG, 0, 1)=="-") {
                    $strRet = '';

                    return false;
                }
            } else {
                $strRet = $out;

                return true;
            }
        }

        $strFile = "";
        $intCurLine = 1;
        $error = PSI_Error::singleton();
        if (file_exists($strFileName)) {
            if (is_readable($strFileName)) {
                if ($fd = fopen($strFileName, 'r')) {
                    while (!feof($fd)) {
                        $strFile .= fgets($fd, $intBytes);
                        if ($intLines <= $intCurLine && $intLines != 0) {
                            break;
                        } else {
                            $intCurLine++;
                        }
                    }
                    fclose($fd);
                    $strRet = $strFile;
                    if (defined('PSI_LOG') && is_string(PSI_LOG) && (strlen(PSI_LOG)>0) && (substr(PSI_LOG, 0, 1)!="-") && (substr(PSI_LOG, 0, 1)!="+")) {
                        if ((strlen($strRet)>0)&&(substr($strRet, -1)!="\n")) {
                            error_log("---".gmdate('r T')."--- Reading: ".$strFileName."\n".$strRet."\n", 3, PSI_LOG);
                        } else {
                            error_log("---".gmdate('r T')."--- Reading: ".$strFileName."\n".$strRet, 3, PSI_LOG);
                        }
                    }
                } else {
                    if ($booErrorRep) {
                        $error->addError('fopen('.$strFileName.')', 'file can not read by phpsysinfo');
                    }

                    return false;
                }
            } else {
                if ($booErrorRep) {
                    $error->addError('fopen('.$strFileName.')', 'file permission error');
                }

                return false;
            }
        } else {
            if ($booErrorRep) {
                $error->addError('file_exists('.$strFileName.')', 'the file does not exist on your machine');
            }

            return false;
        }

        return true;
    }

    /**
     * file exists
     *
     * @param string $strFileName name of the file which should be check
     *
     * @return boolean command successfull or not
     */
    public static function fileexists($strFileName)
    {
        if (defined('PSI_LOG') && is_string(PSI_LOG) && (strlen(PSI_LOG)>0) && ((substr(PSI_LOG, 0, 1)=="-") || (substr(PSI_LOG, 0, 1)=="+"))) {
            $log_file = substr(PSI_LOG, 1);
            if (file_exists($log_file)
                && ($contents = @file_get_contents($log_file))
                && preg_match("/^\-\-\-[^-\n]+\-\-\- ".preg_quote("Reading: ".$strFileName, '/')."\n/m", $contents)) {
                return true;
            } else {
                if (substr(PSI_LOG, 0, 1)=="-") {
                    return false;
                }
            }
        }

        $exists =  file_exists($strFileName);
        if (defined('PSI_LOG') && is_string(PSI_LOG) && (strlen(PSI_LOG)>0) && (substr(PSI_LOG, 0, 1)!="-") && (substr(PSI_LOG, 0, 1)!="+")) {
            if ((substr($strFileName, 0, 5) === "/dev/") && $exists) {
                error_log("---".gmdate('r T')."--- Reading: ".$strFileName."\ndevice exists\n", 3, PSI_LOG);
            }
        }

        return $exists;
    }

    /**
     * reads a directory and return the name of the files and directorys in it
     *
     * @param string  $strPath     path of the directory which should be read
     * @param boolean $booErrorRep en- or disables the reporting of errors which should be logged
     *
     * @return array content of the directory excluding . and ..
     */
    public static function gdc($strPath, $booErrorRep = true)
    {
        $arrDirectoryContent = array();
        $error = PSI_Error::singleton();
        if (is_dir($strPath)) {
            if ($handle = opendir($strPath)) {
                while (($strFile = readdir($handle)) !== false) {
                    if ($strFile != "." && $strFile != "..") {
                        $arrDirectoryContent[] = $strFile;
                    }
                }
                closedir($handle);
            } else {
                if ($booErrorRep) {
                    $error->addError('opendir('.$strPath.')', 'directory can not be read by phpsysinfo');
                }
            }
        } else {
            if ($booErrorRep) {
                $error->addError('is_dir('.$strPath.')', 'directory does not exist on your machine');
            }
        }

        return $arrDirectoryContent;
    }

    /**
     * Check for needed php extensions
     *
     * We need that extensions for almost everything
     * This function will return a hard coded
     * XML string (with headers) if the SimpleXML extension isn't loaded.
     * Then it will terminate the script.
     * See bug #1787137
     *
     * @param array $arrExt additional extensions for which a check should run
     *
     * @return void
     */
    public static function checkForExtensions($arrExt = array())
    {
        if (defined('PSI_SYSTEM_CODEPAGE') && ((strcasecmp(PSI_SYSTEM_CODEPAGE, "UTF-8") == 0) || (strcasecmp(PSI_SYSTEM_CODEPAGE, "CP437") == 0)))
            $arrReq = array('simplexml', 'pcre', 'xml', 'dom');
        elseif (PSI_OS == 'WINNT')
            $arrReq = array('simplexml', 'pcre', 'xml', 'dom', 'mbstring', 'com_dotnet');
        else
            $arrReq = array('simplexml', 'pcre', 'xml', 'dom', 'mbstring');
        $extensions = array_merge($arrExt, $arrReq);
        $text = "";
        $error = false;
        $text .= "<?xml version='1.0'?>\n";
        $text .= "<phpsysinfo>\n";
        $text .= "  <Error>\n";
        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $text .= "    <Function>checkForExtensions</Function>\n";
                $text .= "    <Message>phpSysInfo requires the ".$extension." extension to php in order to work properly.</Message>\n";
                $error = true;
            }
        }
        $text .= "  </Error>\n";
        $text .= "</phpsysinfo>";
        if ($error) {
            header("Content-Type: text/xml\n\n");
            echo $text;
            die();
        }
    }

    /**
     * get the content of stdout/stderr with the option to set a timeout for reading
     *
     * @param array   $pipes   array of file pointers for stdin, stdout, stderr (proc_open())
     * @param string  &$out    target string for the output message (reference)
     * @param string  &$err    target string for the error message (reference)
     * @param integer $timeout timeout value in seconds
     *
     * @return boolean timeout expired or not
     */
    private static function _timeoutfgets($pipes, &$out, &$err, $timeout)
    {
        $w = null;
        $e = null;
        $te = false;

        if (defined("PSI_MODE_POPEN") && PSI_MODE_POPEN === true) {
            $pipe2 = false;
        } else {
            $pipe2 = true;
        }
        while (!(feof($pipes[1]) && (!$pipe2 || feof($pipes[2])))) {
            if ($pipe2) {
                $read = array($pipes[1], $pipes[2]);
            } else {
                $read = array($pipes[1]);
            }

            $n = stream_select($read, $w, $e, $timeout);

            if ($n === false) {
                error_log('stream_select: failed !');
                break;
            } elseif ($n === 0) {
                error_log('stream_select: timeout expired !');
                $te = true;
                break;
            }

            foreach ($read as $r) {
                if ($r == $pipes[1]) {
                    $out .= fread($r, 4096);
                } elseif (feof($pipes[1]) && $pipe2 && ($r == $pipes[2])) {//read STDERR after STDOUT
                    $err .= fread($r, 4096);
                }
            }
        }

        return $te;
    }

    /**
     * function for getting a list of values in the specified context
     * optionally filter this list, based on the list from third parameter
     *
     * @param $wmi object holds the COM object that we pull the WMI data from
     * @param string $strClass name of the class where the values are stored
     * @param array  $strValue filter out only needed values, if not set all values of the class are returned
     *
     * @return array content of the class stored in an array
     */
    public static function getWMI($wmi, $strClass, $strValue = array())
    {
        $arrData = array();
        if (gettype($wmi) === "object") {
            $value = "";
            try {
                $objWEBM = $wmi->Get($strClass);
                $arrProp = $objWEBM->Properties_;
                $arrWEBMCol = $objWEBM->Instances_();
                foreach ($arrWEBMCol as $objItem) {
                    if (is_array($arrProp)) {
                        reset($arrProp);
                    }
                    $arrInstance = array();
                    foreach ($arrProp as $propItem) {
                        $value = $objItem->{$propItem->Name}; //instead exploitable eval("\$value = \$objItem->".$propItem->Name.";");
                        if (empty($strValue)) {
                            if (is_string($value)) $arrInstance[$propItem->Name] = trim($value);
                            else $arrInstance[$propItem->Name] = $value;
                        } else {
                            if (in_array($propItem->Name, $strValue)) {
                                if (is_string($value)) $arrInstance[$propItem->Name] = trim($value);
                                else $arrInstance[$propItem->Name] = $value;
                            }
                        }
                    }
                    $arrData[] = $arrInstance;
                }
            } catch (Exception $e) {
                if (PSI_DEBUG) {
                    $error = PSI_Error::singleton();
                    $error->addError("getWMI()", preg_replace('/<br\/>/', "\n", preg_replace('/<b>|<\/b>/', '', $e->getMessage())));
                }
            }
        } elseif ((gettype($wmi) === "string") && (PSI_OS == 'Linux')) {
            $delimeter = '@@@DELIM@@@';
            if (self::executeProgram('wmic', '--delimiter="'.$delimeter.'" '.$wmi.' '.$strClass.'" 2>/dev/null', $strBuf, true) && preg_match("/^CLASS:\s/", $strBuf)) {
                if (self::$_cp) {
                    if (self::$_cp == 932) {
                        $codename = ' (SJIS)';
                    } elseif (self::$_cp == 949) {
                        $codename = ' (EUC-KR)';
                    } elseif (self::$_cp == 950) {
                        $codename = ' (BIG-5)';
                    } else {
                        $codename = '';
                    }
                    self::convertCP($strBuf, 'windows-'.self::$_cp.$codename);
                }
                $lines = preg_split('/\n/', $strBuf, -1, PREG_SPLIT_NO_EMPTY);
                if (count($lines) >=3) {
                    unset($lines[0]);
                    $names = preg_split('/'.$delimeter.'/', $lines[1], -1, PREG_SPLIT_NO_EMPTY);
                    $namesc = count($names);
                    unset($lines[1]);
                    foreach ($lines as $line) {
                        $arrInstance = array();
                        $values = preg_split('/'.$delimeter.'/', $line, -1);
                        if (count($values) == $namesc) {
                            foreach ($values as $id=>$value) {
                                if (empty($strValue)) {
                                    if ($value !== "(null)") $arrInstance[$names[$id]] = trim($value);
                                    else $arrInstance[$names[$id]] = null;
                                } else {
                                    if (in_array($names[$id], $strValue)) {
                                        if ($value !== "(null)") $arrInstance[$names[$id]] = trim($value);
                                        else $arrInstance[$names[$id]] = null;
                                    }
                                }
                            }
                            $arrData[] = $arrInstance;
                        }
                    }
                }
            }
        }

        return $arrData;
    }

    /**
     * get all configured plugins from phpsysinfo.ini (file must be included and processed before calling this function)
     *
     * @return array
     */
    public static function getPlugins()
    {
        if (defined('PSI_PLUGINS') && is_string(PSI_PLUGINS)) {
            if (preg_match(ARRAY_EXP, PSI_PLUGINS)) {
                return eval(strtolower(PSI_PLUGINS));
            } else {
                return array(strtolower(PSI_PLUGINS));
            }
        } else {
            return array();
        }
    }

    /**
     * name natural compare function
     *
     * @return comprasion result
     */
    public static function name_natural_compare($a, $b)
    {
        return strnatcmp($a->getName(), $b->getName());
    }

    /**
     * readReg function
     *
     * @return boolean command successfull or not
     */
    public static function readReg($reg, $strName, &$strBuffer, $booErrorRep = true, $bits64 = false)
    {
        $arrBuffer = array();
        $_hkey = array('HKEY_CLASSES_ROOT'=>0x80000000, 'HKEY_CURRENT_USER'=>0x80000001, 'HKEY_LOCAL_MACHINE'=>0x80000002, 'HKEY_USERS'=>0x80000003, 'HKEY_PERFORMANCE_DATA'=>0x80000004, 'HKEY_PERFORMANCE_TEXT'=>0x80000050, 'HKEY_PERFORMANCE_NLSTEXT'=>0x80000060, 'HKEY_CURRENT_CONFIG'=>0x80000005, 'HKEY_DYN_DATA'=>0x80000006);

        if ($reg === false) {
            if (defined('PSI_EMU_HOSTNAME')) {
                return false;
            }
            $last = strrpos($strName, "\\");
            $keyname = substr($strName, $last + 1);
            if ($bits64) {
                $param = ' /reg:64';
            } else {
                $param = '';
            }
            if (self::$_cp) {
                if (self::executeProgram('cmd', '/c chcp '.self::$_cp.' >nul & reg query "'.substr($strName, 0, $last).'" /v '.$keyname.$param.' 2>&1', $strBuf, $booErrorRep) && (strlen($strBuf) > 0) && preg_match("/^\s*".$keyname."\s+REG_\S+\s+(.+)\s*$/mi", $strBuf, $buffer2)) {
                    $strBuffer = $buffer2[1];
                } else {
                    return false;
                }
            } else {
                if (self::executeProgram('reg', 'query "'.substr($strName, 0, $last).'" /v '.$keyname.$param.' 2>&1', $strBuf, $booErrorRep) && (strlen($strBuf) > 0) && preg_match("/^\s*".$keyname."\s+REG_\S+\s+(.+)\s*$/mi", $strBuf, $buffer2)) {
                    $strBuffer = $buffer2[1];
                } else {
                    return false;
                }
            }
        } elseif (gettype($reg) === "object") {
            $first = strpos($strName, "\\");
            $last = strrpos($strName, "\\");
            $hkey = substr($strName, 0, $first);
            if (isset($_hkey[$hkey])) {
                $sub_keys = new VARIANT();
                try {
                    $reg->Get("StdRegProv")->GetStringValue(strval($_hkey[$hkey]), substr($strName, $first+1, $last-$first-1), substr($strName, $last+1), $sub_keys);
                } catch (Exception $e) {
                    if ($booErrorRep) {
                        $error = PSI_Error::singleton();
                        $error->addError("GetStringValue()", preg_replace('/<br\/>/', "\n", preg_replace('/<b>|<\/b>/', '', $e->getMessage())));
                    }

                    return false;
                }
                if (variant_get_type($sub_keys) !== VT_NULL) {
                    $strBuffer = strval($sub_keys);
                } else {
                    return false;
                }
            } else {
               return false;
            }
        }

        return true;
    }

    /**
     * enumKey function
     *
     * @return boolean command successfull or not
     */
    public static function enumKey($reg, $strName, &$arrBuffer, $booErrorRep = true)
    {
        $arrBuffer = array();
        $_hkey = array('HKEY_CLASSES_ROOT'=>0x80000000, 'HKEY_CURRENT_USER'=>0x80000001, 'HKEY_LOCAL_MACHINE'=>0x80000002, 'HKEY_USERS'=>0x80000003, 'HKEY_PERFORMANCE_DATA'=>0x80000004, 'HKEY_PERFORMANCE_TEXT'=>0x80000050, 'HKEY_PERFORMANCE_NLSTEXT'=>0x80000060, 'HKEY_CURRENT_CONFIG'=>0x80000005, 'HKEY_DYN_DATA'=>0x80000006);

        if ($reg === false) {
            if (defined('PSI_EMU_HOSTNAME')) {
                return false;
            }
            if (self::$_cp) {
                if (self::executeProgram('cmd', '/c chcp '.self::$_cp.' >nul & reg query "'.$strName.'" 2>&1', $strBuf, $booErrorRep) && (strlen($strBuf) > 0) && preg_match_all("/^".preg_replace("/\\\\/", "\\\\\\\\", $strName)."\\\\(.*)/mi", $strBuf, $buffer2)) {
                    foreach ($buffer2[1] as $sub_key) {
                        $arrBuffer[] = trim($sub_key);
                    }
                } else {
                    return false;
                }
            } else {
                if (self::executeProgram('reg', 'query "'.$strName.'" 2>&1', $strBuf, $booErrorRep) && (strlen($strBuf) > 0) && preg_match_all("/^".preg_replace("/\\\\/", "\\\\\\\\", $strName)."\\\\(.*)/mi", $strBuf, $buffer2)) {
                    foreach ($buffer2[1] as $sub_key) {
                        $arrBuffer[] = trim($sub_key);
                    }
                } else {
                    return false;
                }
            }
        } elseif (gettype($reg) === "object") {
            $first = strpos($strName, "\\");
            $hkey = substr($strName, 0, $first);
            if (isset($_hkey[$hkey])) {
                $sub_keys = new VARIANT();
                try {
                   $reg->Get("StdRegProv")->EnumKey(strval($_hkey[$hkey]), substr($strName, $first+1), $sub_keys);
                } catch (Exception $e) {
                    if ($booErrorRep) {
                        $error = PSI_Error::singleton();
                        $error->addError("enumKey()", preg_replace('/<br\/>/', "\n", preg_replace('/<b>|<\/b>/', '', $e->getMessage())));;
                    }

                    return false;
                }
                if (variant_get_type($sub_keys) !== VT_NULL) foreach ($sub_keys as $sub_key) {
                    $arrBuffer[] = $sub_key;
                } else {
                    return false;
                }
            } else {
               return false;
            }
        }

        return true;
    }


    /**
     * initWMI function
     *
     * @return string, object or false
     */
    public static function initWMI($namespace, $booErrorRep = false)
    {
        $wmi = false;
        try {
            if (PSI_OS == 'Linux') {
                if (defined('PSI_EMU_HOSTNAME'))
                    $wmi = '--namespace="'.$namespace.'" -U '.PSI_EMU_USER.'%'.PSI_EMU_PASSWORD.' //'.PSI_EMU_HOSTNAME.' "select * from';
            } elseif (PSI_OS == 'WINNT') {
                $objLocator = new COM('WbemScripting.SWbemLocator');
                if (defined('PSI_EMU_HOSTNAME'))
                    $wmi = $objLocator->ConnectServer(PSI_EMU_HOSTNAME, $namespace, PSI_EMU_USER, PSI_EMU_PASSWORD);
                else
                    $wmi = $objLocator->ConnectServer('', $namespace);
            }
        } catch (Exception $e) {
            if ($booErrorRep) {
                $error = PSI_Error::singleton();
                $error->addError("WMI connect ".$namespace." error", "PhpSysInfo can not connect to the WMI interface for security reasons.\nCheck an authentication mechanism for the directory where phpSysInfo is installed or credentials.");
            }
        }

        return $wmi;
    }

    /**
     * convertCP function
     *
     * @return void
     */
    public static function convertCP(&$strBuf, $encoding)
    {
        if (defined('PSI_SYSTEM_CODEPAGE') && ($encoding != null) && ($encoding != PSI_SYSTEM_CODEPAGE)) {
            $systemcp = PSI_SYSTEM_CODEPAGE;
            if (preg_match("/^windows-\d+ \((.+)\)$/", $systemcp, $buf)) {
                $systemcp = $buf[1];
            }
            if (preg_match("/^windows-\d+ \((.+)\)$/", $encoding, $buf)) {
                $encoding = $buf[1];
            }
            $enclist = mb_list_encodings();
            if (in_array($encoding, $enclist) && in_array($systemcp, $enclist)) {
                $strBuf = mb_convert_encoding($strBuf, $encoding, $systemcp);
            } elseif (function_exists("iconv")) {
                if (($iconvout=iconv($systemcp, $encoding.'//IGNORE', $strBuf))!==false) {
                    $strBuf = $iconvout;
                }
            } elseif (function_exists("libiconv") && (($iconvout=libiconv($systemcp, $encoding, $strBuf))!==false)) {
                $strBuf = $iconvout;
            }
        }
    }
}
