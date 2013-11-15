<?php
/*
* (c) Carsten Klee <kleetmp-copyright@yahoo.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace CK\MARC2RDF;

class MARCSTRING2RDF extends MARC2RDF {
	
	/**
	* constructor for the class
	*
	* @param string $jsonld_file The local path or URL of the jsonld template file
	* @param string $marc_string MARC data as string
	* @param null|string $marc_format The MARC format
	* @param null|string $base The base IRI for each MARC record in RDF 
	*/
	public function __construct($jsonld_file,$marc_string,$marc_format = null,$base = null,$perRecord = false)
	{
		if(!isset($jsonld_file)) throw new \Exception('Please provide a valid json-ld file.');
		if(!isset($marc_string)) throw new \Exception('Please provide a MARC21 source string.');
		// set jsonld file
		$this->jsonld_file = $jsonld_file;
		if(!is_null($base)) $this->base = $base;
		try
		{
			parent::__construct();
			
			// set marc source file
			$this->_setMarcFile();
			
			// load marc records
			$this->_loadMarcFile($marc_string,$marc_format);
			
			if(!$perRecord) parent::recordLoop();
		}
		catch(Exception $e)
		{
			print $e->getMessage().' in file '.$e->getFile().' on line '.$e->getLine().'<br/>'.$e->getTraceAsString();
		}
	}
	
	/**
	* Set next MARC record
	* @access public
	* @return bool True if next record is set | false if no record left
	*/
	public function next()
	{
		if($this->marcRecord = $this->marcRecords->next())
		{
			parent::marc2rdf();
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	* set the marc file from context
	*/
	private function _setMarcFile()
	{
		if(substr($this->context->{'marc2rdf'}, -1) == ('#' || '/'))
		{
			$this->marc_source = (substr($this->context->{'marc2rdf'}, 0, -1));
		}
		else
		{
			throw new \Exception('marc2rdf IRI must end with \'#\' or \'/\'');
		}
	}
	
	/**
	* Loads the marc string into an File_MARC_Record object
	*
	* Depending on $marc_format the FILE_MARC XML reader or standard reader will be used
	*
	* @param string $marc_string MARC data as string
	* @param null|string $marc_format The MARC format 
	*/
	private function _loadMarcFile($marc_string,$marc_format)
	{
		if($marc_format == 'xml')
		{
			$this->marcRecords = new \File_MARCXML($marc_string, \File_MARC::SOURCE_STRING);
		}
		else
		{
			$this->marcRecords = new \File_MARC($marc_string, \File_MARC::SOURCE_STRING);
		}
	}

}