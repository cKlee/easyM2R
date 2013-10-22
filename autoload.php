<?php
/*
* (c) Carsten Klee <kleetmp-copyright@yahoo.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
$path = ini_get('include_path');
ini_set('include_path', dirname(__FILE__).'/m2r_lib'.PATH_SEPARATOR.$path);
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
			require_once 'File/MARC.php';
			return true;
		}
		elseif($class == 'File_MARCXML')
		{
			require_once 'File/MARCXML.php';
			return true;
		}
		elseif($class == 'EasyRdf_Graph' || $class == 'EasyRdf_Namespace')
		{
			require_once "easyrdf/lib/EasyRdf.php";
			return true;
		}
		elseif($class == 'ForceUTF8\Encoding')
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
		elseif($class == 'Structures_LinkedList_Double')
		{
			require_once "Structures_LinkedList/Structures/LinkedList/Double.php";
			return true;
		}
		elseif($class == 'Structures_LinkedList_Single')
		{
			require_once "Structures_LinkedList/Structures/LinkedList/Single.php";
			return true;
		}
	}
);
include 'callback/callback.php';