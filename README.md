# MARC2RDF BETA

**This is a beta version and still being tested.**

MARC2RDF is a php-based attempt to easily convert MARC data to RDF.

It's easy because you only need

  * your MARC data as a file or a string
  * a valid JSON-LD file which shows how your data should look like in RDF
  * PHP installed in version 5.3.x or higher

# Credits

MARC2RDF is build upon the following software

* File_MARC by Dan Scott, Copyright (c) 1991, 1999 Free Software Foundation, Inc.
* JSON-LD processor for PHP and IRI Copyright (c) by Markus Lanthaler
* EasyRdf Copyright (c) by Nicholas J Humfrey
* forceutf8 by Sebastián Grignoli

# Overview

``` {.ditaa}
    +--------------+
    | MARC data    +----+
    | in a file    |    |
    +--------------+    |   +--------------+
                        +-->| MARCFILE2RDF +-----+
    +--------------+    |   +--------------+     |
    | MARCXML data +----+                        |
    | in a file    |                             |
    +--------------+                             |   +-------------+
                                                 +-->| data as RDF |
    +--------------+                             |   +-------------+
    | MARC data    +----+                        |
    | as a string  |    |                        |
    +--------------+    |   +----------------+   |
                        +-->| MARCSTRING2RDF +---+
    +--------------+    |   +----------------+
    | MARCXML data +----+
    | as a string  |
    +--------------+
```

# Installation

Just pull or clone the repository recursively.

    git clone --recursive http://github.com/cklee/MARC2RDF.git

# Quickstart using the command line

Navigate to the MARC2RDF base directory where you'll find the file 'tordf.php'. At the command line type

    php tordf.php \[-s <PATH_TO_YOUR_MARC_SOURCE>] \[-i <MARC_INPUT_FORMAT>] \[-o <RDF_OUTPUT_SERIALIZATION>]

This will output your MARC data in RDF with the desired output serialization. See [Using the command line interface] for further explanation of the command line options.

# Using the command line interface

With the marc3rdf command line interface you can only use MARC data from a file. If you fetch your MARC data from a stream you can only do this by using a custom php script.

The command line interface is called via the script **tordf.php** with the command **php tordf.php**. At the command line interface you have these options

* -t Path to the jsonld template
* -o The output format must be one of 'jsonld', 'json', 'php', 'ntriples', 'turtle', 'rdfxml', 'dot', 'n3', 'png', 'gif', 'svg' 
* -s The Path to your MARC source file
* -i The MARC input format. 'xml' for MARCXML source
* -c The path to your custom callback functions directory
* -b The base IRI for each MARC record in RDF

All options are optional. But if you want to convert your own MARC data, you have to set the -s option at least.

# Using MARC2RDF in a custom PHP script

Using MARC2RDF within a custom PHP script is necessary if you fetch the MARC data from a stream as a string and pass on to MARC2RDF. This sample code gives a short insight how a custom script could look like:

    <?php
    // always include the autoload.php
    include('path/to/marc2rdf/autoload.php');
    
    // include your custom callback scripts
    foreach(glob('my_callback/callback_*.php') as $filename) include $filename;
    
    // fetch your MARC data here
    $xml_string = do something...

    // initiate MARC2RDF
    $marc2rdf = new MARCSTRING2RDF('../template/default.jsonld',$xml_string,'xml');
    
    // print pretty RDF for browser
    print '<pre>'.htmlspecialchars($marc2rdf->output('turtle')).'</pre>';

## Choosing the right class

MARC2RDF provides to main classes **MARCFILE2RDF** and **MARCSTRING2RDF**. Use the MARCFILE2RDF class if your data resides in a file and use MARCSTRING2RDF if you want to pass your MARC data as a string to MARC2RDF.

### Class MARCFILE2RDF

The MARCFILE2RDF class accepts 4 parameters:

    * @param string The local path or URL of the jsonld template file
    * @param string Path to MARC data as a file
    * @param null|string The MARC format
    * @param null|string The base IRI for each MARC record in RDF

### Class MARCFSTRING2RDF

The MARCSTRING2RDF class accepts 4 parameters:

    * @param string The local path or URL of the jsonld template file
    * @param string MARC data as string
    * @param null|string The MARC format
    * @param null|string The base IRI for each MARC record in RDF

# Configuration

The configuration is done via a JSON-LD document called template. The template is the blue print for every graph resulting from a MARC record. The template has to follow some ground rules, but whatever you do in the template, remember that it must be a valid JSON-LD file. You can test the validity of your template via the [JSON-LD Playground](http://json-ld.org/playground/).

As an example a default template is provided [**`default.jsonld`**](template/default.jsonld).

## MARC spec

In the template you want to access the MARC fields and subfields. This is done via a **MARC spec**. A MARC spec has a simple syntax:

    field_subfield

That is, if you want to access subfield 'a' in field '210', the MARC spec is '210_a'. For MARC control fields the subfield MARC spec part is always '0' (i.e. MARC spec for control field 001 is 001_0).

The MARC spec is only recognized as one, if it is prefixed with the MARC2RDF namespace (see [@context]).

If there are multiple subfields with the same name in one field, there also will be created multiple nodes.

There is also a more powerful way to access MARC fields via [callbacks].

## @context

In the template you must create a **@context** node. In the @context node the only mandatory entry is the MARC2RDF namespace declaration. 

    {
        "@context": {
            "marc2rdf": "http://my.arbitratynamespace.com#"
        }
    }

The prefix of the MARC2RDF namespace must be 'marc2rdf'. The namespaces identifier is also a prefix to your RDF resource IRI. Choose a custom identifier, which must end with '/' or '#'

## @graph

The **@graph** is the template for each MARC record.

Within the @graph you must define the resources **@id**. The value of the @id consists of the MARC2RDF namespace prefix and a MARC spec. I.e.

    {
        "@id": "marc2rdf:001_0"
    }

In this example, if the data in the control field '001' is '123245', then your resources IRI will be 'http://my.arbitratynamespace.com#12345'.

Now define your properties and objects. Regardless of the node type you create (resource, typed value or untyped value) if you want to access a MARC field/subfield always use the MARC2RDF namespace as a prefix. Otherwise the MARC spec will not be recognized as one.

## callbacks

MARC data is not always that easy to access. Sometimes you have to check the indicator first, or look up substrings. Or if you want to join data from subfields or shape data in a different way, there is a powerful way to do this via **callbacks**.

Callbacks are functions that are called if you specify them in the template. There are some default callbacks (see [default callbacks]) but you can write your own callbacks (see [create custom callbacks]).

In the template if you want to call a callback, prefix the callback name with the MARC2RDF namespace prefix 'marc2rdf'. This could look like this example

    "oclcnum":{"@value": "marc2rdf:callback_prefix_in_parentheses(035_a,OCoLC)"}

See [default callbacks] for specific usage.

If you want to use a callback function to return a value for the rdf:type property, then you can't use the JSON-LD syntax token '@type'. The solution is to define the property 'type' within the @context node and use that instead of '@type'.

### default callbacks

There are a bunch of predefined default callback functions that are listed here. Each default callback function takes one to n parameters (often the number is fixed), which are either MARC specs or nonspecs. Nonspecs must always be urlencoded.

#### callback\_with_indicators

* param 1: MARC spec
* param 2: indicator 1
* param 3: indicator 2

Return data, if subfield has indicator 1 and indicator 2.

#### callback\_with_indicator2

* param 1: MARC spec
* param 2: indicator 2

Return data, if subfield has indicator 2.

#### callback\_with_indicator1

* param 1: MARC spec
* param 2: indicator 1

Return data, if subfield has indicator 1.

#### callback_template

* param 1-n: MARC spec
* param m: regex replace pattern

Return data in the shape of the param m. Data of first param is filled in '$0', second in '$1' and so on...

Example

    marc2rdf:callback_template(260_a,260_b,260_c,$0%20%3A%20$1%2C%20$2)

leads to

    Detmold : Kreis Lippe, Der Landrat

#### callback\_substring_after

* param 1: MARC spec
* param 2: substring

Return data comes after substring.

#### callback\_subfield_context

* param 1: MARC spec
* param 2: MARC spec
* param 3: context

Returns data from param 1, if context is substring of data from param 2. 

#### callback\_string_contains

* param 1: MARC spec
* param 2: containing string

Return data if data from param 1 contains string in param 2.

#### callback\_prefix_in_parentheses

* param 1: MARC spec
* param 2: prefixed string

Return data without prefix from param 1 if it is prefixed with param 2 in parentheses.

#### callback\_multi_subfields

* param 1-n: MARC spec

Return data from all MARC specs.

#### callback\_make\_iri\_with_indicator2

* param 1: MARC spec
* param 2: IRI prefix
* param 3: indicator 2

Return IRI consisting of value of param 2 and data from param 1, if indicator 2 equals param 3. 

#### callback\_make\_iri\_with_indicator1

* param 1: MARC spec
* param 2: IRI prefix
* param 3: indicator 1

Return IRI consisting of value of param 2 and data from param 1, if indicator 1 equals param 3. 

#### callback\_make_iri

* param 1: MARC spec
* param 2: IRI prefix

Return IRI consisting of value of param 2 and data from param 1. 

#### callback_join

* param 1-n: MARC spec
* param m: join character

Return data from param 1-n joined with character in param m. 

#### callback\_control\_field_substring

* param 1: MARC spec
* param 2: start position
* param 3: end position

Return data from param 1 with start position in param 2 and end position in param 3

### create custom callbacks

Custom callback functions names must start with 'callback', otherwise they cannot be called.

A callback functions takes two parameters. The first is the MARC record and the second is an array containing MARC specs and nonspes.

The first line of your custom callback function might look like

    function callback_mycustom(File_MARC_Record $record, array $_params)

The var $record is a File_MARC_Record object. This you can access MARC data via its methods (see http://pear.php.net/package/File_MARC/docs for documentation).

The var $_params is an associative array that might look like:

    [specs] => Array
            (
                [0] => Array
                    (
                        [field] => 016
                        [subfield] => a
                    )

                [1] => Array
                    (
                        [field] => 016
                        [subfield] => 2
                    )
            )

    [nonspecs] => Array
        (
            [0] => DE-600
        )

    [rootId] => _:b0

See usage of key 'rootId' under [dealing with dynamic blank nodes].

Return the data at the end of the function. Then include your custom callbacks in your script like

    foreach(glob('my_callback/path/callback_*.php') as $filename) include $filename;

or use the -c option for the command line interface.

### dealing with dynamic blank nodes

For example you specified a blank node in your template

    "@id": "marc2rdf:001_0",
    "property1":
    {
        "@id": "_:bnode_1",
        "@type": "Sometype",
        "property2": {"@value": "marc2rdf:866_z"}
        "property3": {"@value": "marc2rdf:866_y"}
    }

and you want for each data of subfield 'z' and 'y' in field '866' to create a blank node, in your callback function the returning array might look like this

    // subfield z
    [_:b0] => value 1
    [_:b1] => value 2
    [_:b2] => value 3
    
    // subfield y
    [_:b0] => value 4
    [_:b1] => value 5
    [_:b2] => value 6

This would result in something like

    <http://my.arbitratynamespace.com#12345>
        someprefix:property1 [
            a Sometype ;
            someprefix:property2 "value 1" ;
            someprefix:property2 "value 4"
        ], [
            a Sometype ;
            someprefix:property2 "value 2" ;
            someprefix:property2 "value 5"
        ], [
            a Sometype ;
            someprefix:property2 "value 3" ;
            someprefix:property2 "value 6"
        ].

But how do you know what blank node identifiers to use? This is the point where you'll need the value of the key 'rootId' in the var $_params. This value is the id of the currently created node. Just make sure that the first key in your returning array is this id and that all other keys are with a higher count.


