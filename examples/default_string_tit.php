<?php
error_reporting(E_ALL);
use CK\MARC2RDF as m2r;
include('../autoload.php');

$xml_source = 'marc/test_tit.xml';

$XML = simplexml_load_file($xml_source);
$xml_string = $XML->asXML();

$test = new m2r\MARCSTRING2RDF('../template/default.jsonld',$xml_string,'xml',null,true);
print '<html><head><meta charset="UTF-8" /></head><body>';
while($test->next())
{
	print '<pre>'.htmlspecialchars($test->output('rdfxml',$test->recordGraph)).'</pre>';
}

print '</body></html>';