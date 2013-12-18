<?php
/*
* (c) Carsten Klee <kleetmp-copyright@yahoo.de>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


function callback_template(File_MARC_Record $record, array $_params)
{
	if(0 === count($_params['data'])) return null;
	$_split = preg_split('/\$/', $_params['nonspecs'][0], -1, PREG_SPLIT_NO_EMPTY);
	foreach($_split as $part)
	{
		$_newSplit[$part[0]] = substr($part, 1);
	}
	foreach($_params['data'] as $key => $sub)
	{
		if(array_key_exists($key,$_newSplit))
		{
			if(array_key_exists($key+1,$_newSplit))
			{
				if(empty($_newSplit[$key+1]))
				{
					$_newSplit[$key] = $sub;
				}
				else
				{
					$_newSplit[$key] = $sub.$_newSplit[$key];
				} 
			}
			else
			{
				$_newSplit[$key] = $sub.$_newSplit[$key];
			} 
		}
	}
	$data = implode('',$_newSplit);
	return array($data);
}

function callback_substring_after(File_MARC_Record $record, array $_params)
{
	foreach($_params['data'] as $sub)
	{
		if(strstr($sub,$_params['nonspecs'][0]))
		{
			$_dat = explode($_params['nonspecs'][0],$sub);
			$_data[] = end($_dat);
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
				foreach($checks as $check)
				{
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
	for($x = 0; $x < count($_params['data']); $x++)
	{
		if(strstr($_params['nonspecs'][0],$_params['data'][$x])) $data[] = $_params['data'][$x];
	}
	return $_data;
}

function callback_prefix_in_parentheses(File_MARC_Record $record, array $_params)
{
	$_data = null;
	foreach($_params['data'] as $subfield)
	{
		if(strstr($subfield,$_params['nonspecs'][0]))
		{
			$_dat = explode('('.$_params['nonspecs'][0].')',$subfield);
			$_data[] = end($_dat);
		}
	}
	return $_data;
}

function callback_make_iri(File_MARC_Record $record, array $_params)
{
	$_iris = null;
	foreach($_params['data'] as $subfield)
	{
			if(array_key_exists(1,$_params['nonspecs']))
			{
				if(strstr($subfield,$_params['nonspecs'][1]))
				{
					$_data = explode(')',$subfield);
					$_iris[] = $_params['nonspecs'][0].end($_data);
				}
			}
			else
			{
				$_iris[] = $_params['nonspecs'][0].$subfield;
			}
	}
	return $_iris;
}

function callback_join(File_MARC_Record $record, array $_params)
{
	$_data[] = implode($_params['nonspecs'][0],$_params['data']);
	return $_data;
}

function callback_make_bn(File_MARC_Record $record, array $_params)
{
	$bnCounter = str_replace('_:b','',$_params['rootId']);
	
	// array to collect subfields
	$_subfields = array();
	
	foreach($_params['data'] as $i => $subfield)
	{
		$key = $bnCounter + $i;
		$_subfields['_:b'.$key] = $subfield;
	}
	return $_subfields;
}

function check_indicators(File_MARC_Data_Field $field, array $_spec)
{
	$indContext = true;
	if(array_key_exists('indicator1',$_spec))
	{
		$indContext = ($field->getIndicator(1) == $_spec['indicator1']) ? true : false;
	}
	if(array_key_exists('indicator2',$_spec))
	{
		$indContext = ($field->getIndicator(2) == $_spec['indicator2']) ? true : false;
	}
	return $indContext;
}