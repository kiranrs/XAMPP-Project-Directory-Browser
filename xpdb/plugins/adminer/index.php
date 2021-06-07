<?php
/*******************************************************
** Adminer, since version 4.7.0 does not accept        *
** connections without a password.                     *
** For version 4.7.x to accept an empty password,      *
** in the adminer-4.7.x.php file, replace :            *
** login($Ae,$F){if($F=="") by login($Ae,$F){if(1===2) *
** This can be done automatically by replacing false   *
** with true in the line below.                        *
*******************************************************/
$AcceptEmptyPassword = false;

$files = glob('adminer-*.php');
if(!empty($files)) {
  $version = str_replace(array('adminer-','.php'),'',$files[0]);
  $file = 'adminer-'.$version.'.php';
  if(version_compare($version, '4.7.0', '>=')) {
    if(file_exists($file)) {
      /* original strings to be replaced are:
          4.7.0 login($_e,$F){if($F=="")
          4.7.1 login($ze,$F){if($F=="")
          4.7.2 login($ze,$F){if($F=="")
          4.7.3 login($Ae,$F){if($F=="")
          4.7.4 login($_e,$F){if($F=="")
          4.7.5 login($Ae,$E){if($E=="")
          4.7.6 login($Ce,$E){if($E=="")
          4.7.7 login($Ce,$E){if($E=="")
          4.7.8 login($Be,$F){if($F=="")
          4.7.9 login($Fe,$F){if($F=="")
          4.8.0 login($ze,$F){if($F=="")
         must be replaced by
          4.7.0 login($_e,$F){if(1===2)
          4.7.1 login($ze,$F){if(1===2)
          4.7.2 login($ze,$F){if(1===2)
          4.7.3 login($Ae,$F){if(1===2)
          4.7.4 login($_e,$F){if(1===2)
          4.7.5 login($Ae,$E){if(1===2)
          4.7.6 login($Ce,$E){if(1===2)
          4.7.7 login($Ce,$E){if(1===2)
          4.7.8 login($Be,$F){if(1===2)
          4.7.9 login($Fe,$F){if(1===2)
          4.8.0 login($ze,$F){if(1===2)
      */
      $AdminerContents = file_get_contents($file);
      if($AcceptEmptyPassword) {
        $searchpreg = '~(login\(\$[_|z|A|B|C|F]e,\$[F|E]\)\{if\()(\$[F|E]=="")(\))~';
        $replacepreg = '${1}'."1===2".'${3}';
      }
      else {
        $searchpreg = '~(login\(\$[_|z|A|B|C|F]e,\$([F|E])\)\{if\()(1===2)(\))~';
        $replacepreg = '${1}'.'$'.'${2}'.'==""'.'${4}';
      }
      if(preg_match($searchpreg,$AdminerContents,$matches) > 0 ) {
        $AdminerContents = preg_replace($searchpreg,$replacepreg,$AdminerContents,1,$count);
        if($count > 0){
          $fp = fopen($file,'wb');
          fwrite($fp,$AdminerContents);
          fclose($fp);
        }
      }
      unset($adminerContents);
    }
  }
  // include Adminer
  include $file;
}

?>
