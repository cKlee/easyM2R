<?php
/**
* MARC2RDF command line interface
* @usage php tordf.php [args..]
* @arg -t Path to the jsonld template
* @arg -o The output format must be one of 'jsonld', 'json', 'php', 'ntriples', 'turtle', 'rdfxml', 'dot', 'n3', 'png', 'gif', 'svg' 
* @arg -s The Path to your MARC source file
* @arg -i The MARC input format. 'xml' for MARCXML source
* @arg -c The path to your custom callback functions directory
* @arg -b The base IRI for each MARC record in RDF
*/
error_reporting(E_ALL);
include('autoload.php');
use CK\MARC2RDF as m2r;
// default values
$template          = 'template/default.jsonld';
$outputFormat      = 'turtle';
$marcSource        = 'example/marc/test_tit.mrc'; 
$inputFormat       = 'marc'; 
$pathToMyCallbacks = false;
$base              = 'http://example.org/';
$formats = array('turtle','rdfxml','ntriples','jsonld','json','dot', 'n3', 'png', 'gif', 'svg');
for($i = 1; $i<count($argv); $i = $i+2)
{
	switch($argv[$i])
	{
		case '-t': $template          = $argv[$i+1];
		break;
		case '-o': $outputFormat      = $argv[$i+1];
		break;
		case '-s': $marcSource        = $argv[$i+1];
		break;
		case '-i': $inputFormat       = strtolower($argv[$i+1]);
		break;
		case '-c': $pathToMyCallbacks = rtrim($argv[$i+1],'/');
		break;
		case '-b': $base              = $argv[$i+1];
		break;
		
	}
}

$template          = realpath($template);
$marcSource        = 'file://'.realpath($marcSource); 
$pathToMyCallbacks = realpath($pathToMyCallbacks);

if($pathToMyCallbacks)
{
	foreach (glob($pathToMyCallbacks.'/callback_*.php') as $filename) include $filename;
}

if('xml' === $inputFormat)
{
	$XML        = simplexml_load_file($xml_source);
	$xml_string = $XML->asXML();
	$toRdf      = new m2r\MARCSTRING2RDF($template,$xml_string,'xml',$base);
}
else
{
	$toRdf      = new m2r\MARCFILE2RDF($template,$marcSource,null,$base);
}
if(in_array($outputFormat,$formats))
{
	print $toRdf->output($outputFormat);
}
elseif('php' === $outputFormat)
{
	print_r($toRdf->output($outputFormat));
}
else
{
	throw new \Exception('Unknown output format '.$outputFormat);
}