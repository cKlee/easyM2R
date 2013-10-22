<?php
/*
* (c) Carsten Klee <kleetmp-copyright@yahoo.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
$path = ini_get('include_path');
$otherPaths = array('/m2r_lib','/m2r_lib/PEAR_Exception','/m2r_lib/File_MARC');
foreach($otherPaths as $otherPath)
{
	ini_set('include_path', dirname(__FILE__).$otherPath.PATH_SEPARATOR.$path);
	$path = ini_get('include_path');
}
spl_autoload_register(
	function ($class) {
		if (0 === strpos($class, 'ML\\JsonLD\\')) {
			$path = implode('/', array_slice(explode('\\', $class), 2)) . '.php';
			require_once 'JsonLD/' . $path;
			return true;
		} elseif (0 === strpos($class, 'ML\\IRI\\')) {
			$path = implode('/', array_slice(explode('\\', $class), 2)) . '.php';
			require_once 'IRI/' . $path;
			return true;
		} elseif (0 === strpos($class, 'CK\\MARC2RDF\\')) {
			$path = implode('/', array_slice(explode('\\', $class), 2)) . '.php';
			require_once $path;
			return true;
		}
		elseif($class == 'File_MARC') 
		{
			require_once 'File_MARC/File/MARC.php';
			return true;
		}
		elseif($class == 'File_MARCXML')
		{
			require_once 'File_MARC/File/MARCXML.php';
			return true;
		}
		elseif($class == 'EasyRdf_Graph' || $class == 'EasyRdf_Namespace')
		{
			require_once "easyrdf/lib/EasyRdf.php";
			return true;
		}
		elseif($class == 'ForceUTF8\\Encoding')
		{
			require_once "forceutf8/src/ForceUTF8/Encoding.php";
			return true;
		}
		elseif($class == 'Validate_ISPN')
		{
			require_once "Validate_ISPN/Validate/ISPN.php";
			return true;
		}
		elseif($class == 'Validate')
		{
			require_once "Validate/Validate.php";
			return true;
		}
		elseif($class == 'PEAR_Exception')
		{
			require_once "PEAR_Exception/PEAR/Exception.php";
			return true;
		}
	}
);
include 'callback/callback.php';