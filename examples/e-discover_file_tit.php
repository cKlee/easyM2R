<?php
/*
* (c) Carsten Klee <kleetmp-copyright@yahoo.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
use CK\MARC2RDF as m2r;
include('../autoload.php');
foreach (glob('my_callback/callback_*.php') as $filename) include $filename;
$test = new m2r\MARCFILE2RDF("../template/default.jsonld","marc/e-discover.mrc");
print '<html><head><meta charset="UTF-8" /></head><body><pre>'.htmlspecialchars($test->output('turtle')).'</pre></body></html>';