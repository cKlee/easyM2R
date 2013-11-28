<?php
/*
* (c) Carsten Klee <kleetmp-copyright@yahoo.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace CK\MARC2RDF;
use ML\JsonLD as jld;
use ML\JsonLD\TypedValue;
use ML\IRI as iri;
use ForceUTF8 as enc;

class MARC2RDF {

	/**
	* @var string The local path or URL of the jsonld template file 
	*/
	public $jsonld_file;
	
	/**
	* @var string The local path or URL of the MARC source
	*/
	public $marc_source;
	
	/**
	* @var string the base IRI for the resource
	*/
	public $base;
	
	/**
	* @var GraphInterface The result graph with MARC data
	*/
	public $newGraph;
	
	/**
	* @var GraphInterface A temporary graph, will be merged into newGraph
	*/
	public $recordGraph;
	
	/**
	* @var string|null The name of the template graph, which should be used
	*/
	public $graph_name = null;
	
	/**
	* @var string The desired output format
	*/
	public $format = 'jsonld';
	
	/**
	* @var array The available formats for output
	*/
	protected $formats = array('jsonld', 'json', 'php', 'ntriples', 'turtle', 'rdfxml', 'dot', 'n3', 'png', 'gif', 'svg');
	
	/**
	* @var File_MARCBASE The FILE_MARC object with data access methods
	*/
	protected $marcRecords;
	
	/**
	* @var NodeInterface The @context node of the jsonld template 
	*/
	protected $context;

	/**
	* @var File_MARC_Record The current MARC record object
	*/
	protected $marcRecord;
	
	/**
	* @var GraphInterface The template graph
	*/
	private $graph;
	
	/**
	* @var array All template nodes
	*/
	private $nodes;
	
	/**
	* @var GraphInterface The current template node
	*/
	private $node;
	
	/**
	* @var string The current template node id
	*/
	private $nodeId;
	
	/**
	* @var NodeInterface The node currently created in the recordGraph 
	*/
	private $currentNode;
	
	/**
	* @var array Mapping of template ids to recordGraph ids
	*/
	private $map;
	
	/**
	* @var array Mapping of template ids to recordGraph ids
	*/
	private $callback;
	/**
	* @var array MARC field spec
	*/
	private $spec;
	/**
	* @var array Extra param for callback functions
	*/
	private $nonSpec;


	/**
	* Constructor
	* check jsonld file, set context and base,
	* create newGraph
	*
	* @access protected
	*/
	protected function __construct()
	{
		$this->_checkFile($this->jsonld_file);
		
		$this->_setContext();

		if(is_null($this->base) && property_exists($this->context,'marc2rdf'))
		{
			$this->base = $this->context->{'marc2rdf'};
		}
		
		$this->newGraph = new jld\Graph;
		
		// load jsonld file into doc
		$doc = jld\JsonLD::getDocument($this->jsonld_file);
		
		// make graph from doc
		$this->graph = $doc->getGraph($this->graph_name);

		// get all nodes of graph
		$this->nodes = $this->graph->getNodes();
	}
	
	/**
	* marc2rdf the main method
	*
	* @access protected
	*/
	protected function marc2rdf()
	{
		$this->recordGraph = new jld\Graph;
		$this->map = array();
		
		// iterate through all nodes
		// create nodes
		$i = 0;
		foreach($this->nodes as $this->node)
		{

			$this->nodeId = $this->node->getId();
			if(array_key_exists($this->nodeId,$this->map)) continue;
			
			$data = false;
			
			if( 0 === $i ) // this is the root node
			{
				$wholeSpec = str_replace($this->base,'',$this->nodeId);
				if($data = $this->getMarcData($wholeSpec))
				{
					$data[0] = $this->base.$data[0];
				}
				else
				{
					throw new \Exception('No data for resource IRI found');
				}
			}
			else
			{
				if(strstr($this->nodeId,$this->base))
				{
					$wholeSpec = str_replace($this->base,'',$this->nodeId);
					$data = $this->getMarcData($wholeSpec);
				}
				else
				{
					$data[0] = $this->nodeId;
				}
			}
			
			if($data)
			{
				foreach($data as $key => $id)
				{
					if('http' !== substr($id, 0, 4) && '_:' !== substr($id, 0, 2))
					{
						$data[$key] = $this->base.$id;
					}
				}
				
				foreach($data as $key => $id)
				{
					if('_:' == substr($key, 0, 2))
					{
						$this->dynamicBlankNode($data);
						continue 2;
					}
					
					if(!$this->recordGraph->getNode($id))
					{
						$this->currentNode = $this->recordGraph->createNode($id);
						$this->map[$this->nodeId][] = $id;
					}
					$this->typeForNode();
				}
			}
			$this->iterateProperties();
			$i++;
		} // node loop
		
		// link nodes
		$this->linkNodes();
		
		// clean up recordGraph
		$this->cleanUp();
	}
	
	
	/**
	* Loop through MARC records and merge graphs
	* @access protected
	*/
	protected function recordLoop()
	{
		while($this->marcRecord = $this->marcRecords->next()) 
		{
			$this->marc2rdf();
			// merge the record graph into the new resulting graph
			$this->newGraph->merge($this->recordGraph);
		}
	}
	/**
	* Reverse properties maped to reverse nodes of recordGraph
	*
	* @access private
	* @param array $reverseProperties The reverse properties with the template reverse nodes
	* @return array $rev The reverse properties with the recordGraph reverse nodes
	*/
	private function mapProperties(array $reverseProperties)
	{
		
		foreach($reverseProperties as $revName => $revNodes) // rev template nodes
		{

			foreach($revNodes as $revNode) // rev template node
			{
				$revNodeId = $revNode->getId();
				foreach($this->map[$revNodeId] as $newRevNodeId)
				{
					$newRevNode = $this->recordGraph->getNode($newRevNodeId);
					$rev[$revName][] = $newRevNode;
				}
			}
		}
		return $rev;
	}
	
	/**
	* Iterates through the nodes properties
	* @access private
	*/
	private function iterateProperties()
	{
		$properties = $this->node->getProperties();
		foreach($properties as $propertyName => $propertyValue)
		{
			if(is_array($propertyValue))
			{
				foreach($propertyValue as $setPropertyValue)
				{
					if( !is_a($setPropertyValue,'ML\JsonLD\Node') )
					{
						$this->addProperties($propertyName,$setPropertyValue);
					}
				}
			}
			else
			{
				if( !is_a($propertyValue,'ML\JsonLD\Node') )
				{
					$this->addProperties($propertyName,$propertyValue);
				}
			}
		}
	}
	
	/**
	* If data for property, create propertyValue
	* @access private
	* @param string $propertyName
	* @param JsonLdSerializable $propertyValue
	*/
	private function addProperties($propertyName,jld\JsonLdSerializable $propertyValue)
	{
		$data = false;
		if(method_exists($propertyValue,'getValue'))
		{
			$value = $propertyValue->getValue();
			if('marc2rdf:' === substr($value, 0, 9) )
			{
				$wholeSpec = str_replace('marc2rdf:','',$value);
				$data = $this->getMarcData($wholeSpec);
			}
			else
			{
				$data = array($value);
			}
		}
		
		if($data)
		{
			$type = $propertyValue->getType();
			$key = key($data);
			if('_:' == substr($key, 0, 2))
			{
				$this->dynamicBlankNode($data,$propertyName,$type);
				return;
			}
			if(is_a($propertyValue,'ML\JsonLD\TypedValue'))
			{
				foreach($data as $val)
				{
					$this->currentNode->addPropertyValue($propertyName,new TypedValue($val,$type));
				}
			}
			else
			{
				foreach($data as $val)
				{
					$this->currentNode->addPropertyValue($propertyName,$val);
				}
			}
		}
	}
	
	/**
	* Link currentNode and its referencing nodes
	* @access private
	*/
	private function linkNodes()
	{
		$i = 0;
		foreach($this->map as $templateId => $newIds)
		{
			if(0 !== $i) // root node does not have reverse properties
			{
				$this->node = $this->graph->getNode($templateId);
				$reverseProperties = $this->node->getReverseProperties();
				if( '@type' === key($reverseProperties) ) continue;
				if($reverse = $this->mapProperties($reverseProperties))
				{
					foreach($newIds as $newNodeId)
					{
						$this->currentNode = $this->recordGraph->getNode($newNodeId);
						foreach($reverse as $revProperty => $revNodes)
						{
							foreach($revNodes as $revNode)
							{
								$revNode->addPropertyValue($revProperty,$this->currentNode);
							}
						}
					}
				}
			}
			$i++;
		}
	}
	
	/**
	* Clean up new graph from empty blank nodes
	*/
	private function cleanUp()
	{
		$recordNodes = array_reverse($this->recordGraph->getNodes()); // clean inner nodes first
		foreach($recordNodes as $recordNode)
		{
			if($recordNode->isBlankNode())
			{
				$recordProperties = $recordNode->getProperties();
				$rCnt = count($recordProperties);
				if(0 === $rCnt)
				{
					$this->recordGraph->removeNode($recordNode);
				}
				elseif(1 === $rCnt)
				{
					$rProp = key($recordProperties);
					if('@type' === $rProp || 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' === $rProp)
					{
						$this->recordGraph->removeNode($recordNode);
					}
				}
				#else
				#{
				#	foreach($recordProperties as $propName => $node)
				#	{
				#		if(is_a($node,'ML\JsonLD\Node'))
				#		{
				#			if( !$this->recordGraph->getNode($node->getId()) )
				#			{
				#				$this->recordGraph->removeNode($recordNode);
				#			}
				#		}
				#	}
				#}
			}
		}
	}
	
	/**
	* create blank node for each data element
	* or add property value if blank node already exists
	*
	* @access private
	*
	* @param array $data
	* @param null|string $propertyName
	* @param null|string $type
	*/
	private function dynamicBlankNode(array $data,$propertyName = null,$type = null)
	{
		foreach($data as $key => $val)
		{
			if(array_key_exists($key,$this->map))
			{
				$this->currentNode = $this->recordGraph->getNode($this->map[$key][0]);
			}
			elseif(array_key_exists($key,$this->map[$this->nodeId]))
			{
				$this->currentNode = $this->recordGraph->getNode($this->map[$this->nodeId][$key]);
			}
			else
			{
				// create a new blank node
				$this->currentNode = $this->recordGraph->createNode();
				$currentNodId = $this->currentNode->getId();
				$this->map[$this->nodeId][$key] = $this->currentNode->getId();
				$this->typeForNode();
			}

			if( 'http' === substr($val, 0, 4) )
			{
				$this->map[$this->nodeId][$key] = $val;
				// create new named node
				$this->currentNode = $this->recordGraph->createNode($val);
				$this->typeForNode();
			}
			else
			{
				if(!is_null($type))
				{
					$this->currentNode->addPropertyValue($propertyName,new TypedValue($val,$type));
				}
				else
				{
					$this->currentNode->addPropertyValue($propertyName,$val);
				}
			}
		}
		$this->currentNode = $this->recordGraph->getNode($this->map[$this->nodeId][0]);
	}
	
	/**
	* Add type to the currentNode
	* @access private
	*/
	private function typeForNode()
	{
		$type = $this->node->getType();
		
		$types = null;
		if(is_array($type))
		{
			foreach($type as $tkey => $typeNode)
			{
				if (method_exists($typeNode, 'getId'))
				{
					$types[] = $typeNode->getId();
				}
				elseif('http' !== substr($tkey, 0, 4))
				{
					$types[] = $tkey;
				}
			}
		}
		elseif (method_exists($type, 'getId'))
		{
			$types = array($type->getId());
		} 
		elseif(is_string($type))
		{
			$types = array($type);
		}
		
		if(!is_null($types))
		{
			foreach($types as $type)
			{
				$typeNode = $this->recordGraph->getNode($type);
				if(is_null($typeNode))
				{
					$typeNode = $this->recordGraph->createNode($type);
				}
				$this->currentNode->addType($typeNode);
			}
		}
	}

	/**
	* gets marc data directly or via callback
	*
	* @access private
	* @param string $wholeSpec
	*/
	private function getMarcData($wholeSpec)
	{

		$data = false;

		$this->analyzeSpec($wholeSpec);

		if( !is_null( $this->callback ) )
		{
			$_params = array();
			$_params['specs'] = $this->spec;
			$_params['nonspecs'] = $this->nonSpec;
			if(isset($this->currentNode)) $_params['rootId'] = $this->currentNode->getId();
			
			$data = $this->_call_callback($this->callback, $_params);
		}
		else
		{
			if($fields = $this->marcRecord->getFields($this->spec['field']))
			{
				$data = array();
				foreach($fields as $field)
				{
					if(!$field->isControlField())
					{
						if($subfields = $field->getSubfields($this->spec['subfield']))
						{
							foreach($subfields as $subfield)
							{
								if(!$subfield->isEmpty()) $data[] = $subfield->getData();
							}
						}
					} 
					else
					{
						if(!$field->isEmpty()) $data[] = $field->getData();
					}
				}
			}
		}
		if(is_null($data)) return false;
		if($data)
		{
			if(is_array($data)) 
			{
				foreach($data as $kdat => $dat)
				{
					$_data[$kdat] = trim(enc\Encoding::toUTF8($dat));
				}
			}
			else
			{
				$_data[0] = trim(enc\Encoding::toUTF8($data));
			}
			return $_data;
		}
		return $data;
	}
	
	/**
	* call a callback function
	*
	* @access private
	*
	* @param string $callback: the callback dunction name
	* @param array $_callback_param: the callback params
	* @return array of marc data strings
	*/
	private function _call_callback($callback, array $_callback_param)
	{
		if ( is_callable($callback) )
		{
			return call_user_func_array($callback, array($this->marcRecord, $_callback_param));
		}
		else
		{
			throw new \Exception('callback function '. $callback .' is not callable');
		}
	
	}
	
	
	/**
	* set the \@context object
	*
	* @access private
	*/
	private function _setContext()
	{
		$parsed = jld\JsonLD::parse($this->jsonld_file);
		$this->context = $parsed->{'@context'};
		unset($parsed);
	}
	
	/**
	* Check if the file exits and is readable
	*
	* @access protected
	*/
	protected function _checkFile($file)
	{
		if(!is_file($file)){
			throw new \Exception($file.' is not a file.');
		}
		if(!is_readable($file)) {
			throw new \Exception($file.' is not readable.');
		}
	}
	
	/**
	* analyzes the marc spec
	*
	* @access private
	*
	* @param string $spec
	*/
	private function analyzeSpec($wholeSpec)
	{
		$this->callback = null;
		$this->spec = null;
		$this->nonSpec = null;
		if( strstr($wholeSpec,'callback') )
		{
			preg_match('/^(callback.*)\((.*)\)/',$wholeSpec,$matches);
			$this->callback = $matches[1];
			$_parameter = explode(',',$matches[2]);
			$_callback_param = null;
			$_callback_nonspec_param = null;
			foreach($_parameter as $param)
			{
				// parse spec
				$_parsed = $this->_parseSpec(trim($param));
				
				// if false param is not a marc spec
				if(!$_parsed)
				{
					$_callback_nonspec_param[] = urldecode($param);
				}
				else
				{
					$_callback_param[] = $_parsed;
				}
			}
			if(isset($_callback_param)) $this->spec = $_callback_param;
			if(isset($_callback_nonspec_param)) $this->nonSpec = $_callback_nonspec_param;
		}
		else
		{
			$this->spec = $this->_parseSpec( trim($wholeSpec) );
		}
	}
	
	/**
	* parses the marc spec into an array
	* 
	* @access private
	*
	* @param string $marcSpec: the marc spec
	*
	* @return array $_pasedSpec: array of field spec
	*/
	private static function _parseSpec($marcSpec)
	{
		$marcSpec = str_replace('__','_',urldecode($marcSpec));
		$_marcSpec = explode('_',$marcSpec);
		if(count($_marcSpec) < 2) return false;
		$_pasedSpec['field'] = $_marcSpec[0];
		$_pasedSpec['subfield'] = $_marcSpec[1];
		return $_pasedSpec;
	}
	
	/**
	* output the new graph in a desired format
	*
	* @access public
	*
	* @param string $format The desired format
	* @param null|GraphInterface $graph
	* @return string $output
	*/
	public function output($format = 'jsonld',jld\GraphInterface $graph = null)
	{
		if(!is_null($graph))
		{
			$outputGraph = $graph;
		}
		else
		{
			$outputGraph = $this->newGraph;
		}
		if(!$outputGraph)
		{
			throw new \Exception('marc2rdf must be initialized first.');
		}
		
		if( !in_array($format,$this->formats) )
		{
			throw new \Exception('Unknown format '.$format);
		}
		
		$nquads = new jld\NQuads();
		$serialized = $outputGraph->toJsonLd(true);
		$quads = jld\JsonLD::toRdf($serialized);

		if($format == 'jsonld')
		{
			$output = jld\JsonLD::toString($serialized);
		}
		elseif($format == 'ntriples')
		{
			$output = $nquads->serialize($quads);
		}
		else
		{
			foreach( get_object_vars($this->context) as $prefix => $namespace )
			{
				if(gettype($namespace) == 'string' && $prefix[0] != '@')
				{
					$oldprefix = \EasyRdf_Namespace::prefixOfUri($namespace);
					if(isset($oldprefix)) \EasyRdf_Namespace::delete($oldprefix);
					if('marc2rdf' != $prefix) \EasyRdf_Namespace::set($prefix,$namespace);
				}
			}
			
			$graph = new \EasyRdf_Graph();
			$ntripleParser = new \EasyRdf_Parser_Ntriples();
			$ntripleParser->parse($graph,$nquads->serialize($quads),'ntriples',$this->base);
			#$graph->parse($nquads->serialize($quads), 'ntriples');

			$output = $graph->serialise($format);
		}
		return $output;
	}
}