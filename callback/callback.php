<?php
/*
* (c) Carsten Klee <kleetmp-copyright@yahoo.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


function callback_leader(File_MARC_Record $record, array $_params){
	$leader = $record->getLeader();
	$length = $_params['nonspecs'][1] - $_params['nonspecs'][0] + 1;
	return $dataSub = substr($leader,$_params['nonspecs'][0],$length);
}

function callback_with_indicators(File_MARC_Record $record, array $_params)
{
	$_subfields = null;
	foreach($_params['specs'] as $_spec){
		if($fields = $record->getFields($_spec['field']))
		{
			foreach($fields as $field){
				if($field->getIndicator(1) == (string)$_params['nonspecs'][0] && $field->getIndicator(2) == (string)$_params['nonspecs'][1])
				{
					if($subfields = $field->getSubfields($_spec['subfield']))
					{
						foreach($subfields as $subfield)
						{
							if(!$subfield->isEmpty()) $_subfields[] = $subfield->getData();
						}
					}
				}
			}
		}
	}
	return $_subfields;
}

function callback_with_indicator2(File_MARC_Record $record, array $_params){
	// array to collect subfields
	$_subfields = null;
	
	// for each spec
	foreach($_params['specs'] as $_spec){
		// get a field
		if($fields = $record->getFields($_spec['field']))
		{
			// get all subfields
			foreach($fields as $field)
			{
				// check for indicator 2
				if($field->getIndicator(2) == (string)$_params['nonspecs'][0])
				{ 
					if($subfields = $field->getSubfields($_spec['subfield']))
					{
						foreach($subfields as $subfield)
						{
							if(!$subfield->isEmpty()) $_subfields[] = $subfield->getData();
						}
					}
				}
			}
		}
	}
	return $_subfields;
}


function callback_with_indicator1(File_MARC_Record $record, array $_params){
	$_subfields = null;
	foreach($_params['specs'] as $_spec){
		// get a field
		if($fields = $record->getFields($_spec['field'])){
			// get all subfields
			foreach($fields as $field)
			{
				if($field->getIndicator(1) == (string)$_params['nonspecs'][0])
				{
					if($subfields = $field->getSubfields($_spec['subfield']))
					{
						foreach($subfields as $subfield)
						{
							if(!$subfield->isEmpty()) $_subfields[] = $subfield->getData();
						}
					}
				}
			}
		}
	}
	return $_subfields;
}

function callback_template(File_MARC_Record $record, array $_params){
	$_subfields = null;
	
	// for each spec
	foreach($_params['specs'] as $_spec)
	{
		// get a field
		if($field = $record->getField($_spec['field'])){
			// get a subfield
				if($subfields = $field->getSubfields($_spec['subfield']))
				{
					foreach($subfields as $subfield)
					{
						if(!$subfield->isEmpty()) $_subfields[] = $subfield->getData();
					}
				}
		}
	}
	if(0 === count($_subfields)) return null;
	$_split = preg_split('/\$/', $_params['nonspecs'][0], -1, PREG_SPLIT_NO_EMPTY);
	foreach($_split as $part){
		$_newSplit[$part[0]] = substr($part, 1);
	}

	foreach($_subfields as $key => $sub){
		if(array_key_exists($key,$_newSplit)) {
			if(array_key_exists($key+1,$_newSplit)){
				if(empty($_newSplit[$key+1])) {
					$_newSplit[$key] = $sub;
				} else {
					$_newSplit[$key] = $sub.$_newSplit[$key];
				} 
			} else {
				$_newSplit[$key] = $sub.$_newSplit[$key];
			} 
		}
	}
	#$data = urldecode(implode('',$_newSplit));
	$data = implode('',$_newSplit);
	return $data;
}

function callback_substring_after(File_MARC_Record $record, array $_params){
	$_data = null;
	if($fields = $record->getFields($_params['specs'][0]['field']))
	{
		foreach($fields as $field){
			// get all subfields
			if($subfields = $field->getSubfields($_params['specs'][0]['subfield']))
			{
				foreach($subfields as $subfield)
				{
					$data = false;
					if(!$subfield->isEmpty()) $data = $subfield->getData();
					if($data)
					{
						if(strstr($data,$_params['nonspecs'][0]))
						{
							$_dat = explode($_params['nonspecs'][0],$data);
							$_data[] = end($_dat);
						}
					}
				}
			}
		}
	}
	return $_data;
}

function callback_subfield_context(File_MARC_Record $record, array $_params)
{
	if($fields = $record->getFields($_params['specs'][0]['field']))
	{
		foreach($fields as $field){
			if($checks = $field->getSubfields($_params['specs'][1]['subfield']))
			{
				foreach($checks as $check){
					if($_params['nonspecs'][0] == $check->getData()){
						if($subfield = $field->getSubfield($_params['specs'][0]['subfield'])){
							if(!$subfield->isEmpty()) return $subfield->getData();
						}
					}
				}
			}
		}
	}
	return null;
}

function callback_string_contains(File_MARC_Record $record, array $_params)
{
	$_data = null;
	if($fields = $record->getFields($_params['specs'][0]['field']))
	{
		foreach($fields as $field)
		{
			// get all subfields
			if($subfields = $field->getSubfields($_params['specs'][0]['subfield']))
			{
				foreach($subfields as $subfield)
				{
					$data = $subfield->getData();
					if(strstr($_params['nonspecs'][0],$data)) $_data[] = $data;
				}
			}
		}
		return $_data;
	}
	return null;
}

function callback_prefix_in_parentheses(File_MARC_Record $record, array $_params)
{
	$_data = null;
	if($fields = $record->getFields($_params['specs'][0]['field']))
	{
		foreach($fields as $field){
			// get all subfields
			if($subfields = $field->getSubfields($_params['specs'][0]['subfield']))
			{
				foreach($subfields as $subfield)
				{
					$data = $subfield->getData();
					if(strstr($data,$_params['nonspecs'][0]))
					{
						$_dat = explode('('.$_params['nonspecs'][0].')',$data);
						$_data[] = end($_dat);
					}
				}
			}
		}
	}
	return $_data;
}

function callback_multi_subfields(File_MARC_Record $record, array $_params)
{
	$_subfields = null;
	foreach($_params['specs'] as $_spec){
		// get a field
		if($field = $record->getField($_spec['field'])){
			// get a subfield
			if($subfields = $field->getSubfields($_spec['subfield'])){
				foreach($subfields as $subfield){
					if(!$subfield->isEmpty()) $_subfields[] = $subfield->getData();
				}
			}
		}
	}
	return $_subfields;
}

function callback_make_iri_with_indicator2(File_MARC_Record $record, array $_params)
{
	$_iris = null;
	#print_r($_params);
	if($fields = $record->getFields($_params['specs'][0]['field'])){
		foreach($fields as $field){
			if(callback_check_indicator2($field,$_params['nonspecs'][1]))
			{
				if($subfields = $field->getSubfields($_params['specs'][0]['subfield']))
				{
					foreach($subfields as $subfield)
					{
						if(!$subfield->isEmpty())
						{
							$data = $subfield->getData();
							$_data = explode(')',$data);
							#$_iris[] = urldecode($_params['nonspecs'][0]).end($_data);
							$_iris[] = $_params['nonspecs'][0].end($_data);
						}
					}
				}
			}
		}
	}
	return $_iris;
}

function callback_make_iri_with_indicator1(File_MARC_Record $record, array $_params)
{
	$_iris = null;
	#print_r($_params);
	if($fields = $record->getFields($_params['specs'][0]['field'])){
		foreach($fields as $field){
			if(callback_check_indicator1($field,$_params['nonspecs'][1]))
			{
				if($subfields = $field->getSubfields($_params['specs'][0]['subfield']))
				{
					foreach($subfields as $subfield)
					{
						if(!$subfield->isEmpty())
						{
							$data = $subfield->getData();
							$_data = explode(')',$data);
							#$_iris[] = urldecode($_params['nonspecs'][0]).end($_data);
							$_iris[] = $_params['nonspecs'][0].end($_data);
						}
					}
				}
			}
		}
	}
	return $_iris;
}

function callback_make_iri(File_MARC_Record $record, array $_params)
{
	$_iris = null;
	#print_r($_params);
	if($fields = $record->getFields($_params['specs'][0]['field']))
	{
		foreach($fields as $field){
			// get a subfield
			if($subfields = $field->getSubfields($_params['specs'][0]['subfield']))
			{
				foreach($subfields as $subfield)
				{
					if(!$subfield->isEmpty())
					{
						$data = $subfield->getData();
						if(array_key_exists(1,$_params['nonspecs']))
						{
							if(strstr($data,$_params['nonspecs'][1]))
							{
								$_data = explode(')',$data);
								#$_iris[] = urldecode($_params['nonspecs'][0]).end($_data);
								$_iris[] = $_params['nonspecs'][0].end($_data);
							}
						}
						else
						{
							#$_iris[] = urldecode($_params['nonspecs'][0]).$data;
							$_iris[] = $_params['nonspecs'][0].$data;
						}
					}
				}
			}
		}
	}
	return $_iris;
}

function callback_join(File_MARC_Record $record, array $_params)
{
	$_data = null;
	$_subfields = array();
	// for each spec
	foreach($_params['specs'] as $_spec)
	{
		// get a field
		if($fields = $record->getFields($_spec['field']))
		{
			// get all subfields
			foreach($fields as $field){
				if($subfields = $field->getSubfields($_spec['subfield']))
				{
					foreach($subfields as $subfield){
						if(!$subfield->isEmpty()) $_subfields[] = $subfield->getData();
					}
				}
			}
		}
	}
	
	if(count($_subfields) == 0) return null;
	#$char = urldecode($_params['nonspecs'][0]);
	$char = $_params['nonspecs'][0];
	$_data[] = implode($char,$_subfields);
	return $_data;
}

function callback_control_field_substring(File_MARC_Record $record, array $_params)
{
	if($field = $record->getField($_params['specs'][0]['field']))
	{
		if(!$field->isControlField()) return null;
		$data = $field->getData();
		$length = $_params['nonspecs'][1] - $_params['nonspecs'][0] + 1;
		$dataSub = substr($data,$_params['nonspecs'][0],$length);
		return (strstr($dataSub,'|')) ? null : $dataSub;
	}
	return null;
}

function callback_check_indicator2(File_MARC_Data_Field $field, $indicator2)
{
	if(get_class($field) != 'File_MARC_Data_Field'){
		throw new Exception('callback_check_indicator1: \$field is not of type File_MARC_Data_Field');
		die;
	}
	return ($field->getIndicator(2) == $indicator2) ? true : false;
}

function callback_check_indicator1(File_MARC_Data_Field $field,$indicator1)
{
	if(get_class($field) != 'File_MARC_Data_Field'){
		throw new Exception('callback_check_indicator1: \$field is not of type File_MARC_Data_Field');
		die;
	}
	return ($field->getIndicator(1) == $indicator1) ? true : false;
}