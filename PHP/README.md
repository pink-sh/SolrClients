PHP Solr Client
===============

Class Methods:
==============

  SolrClient:
  -----------
  Constructor:
  
    new SolrClient($host, $port)
---------------------------------------------------------------------------------
    setUriPath($uri);
      Path where Solr is located (ex. /solr/)
      Default: none
    
    setRequestHandler($handler);
      Solr Request Handler
      Default: select
    
    setUpdateHandler($handler);
      Solr Update Handler
      Default: update
    
    setOutputType($type);
      Set the Query output types {json, xml, csv, python, ruby, php}
      Default: json
    
    setQueryParser($qp);
      Set the query parser {dismax, edismax}
      Default: none
      
    setRows($rows);
      Set the returning documents rows
      Default: 10
      
    setStart($start);
      Set the query start
      Default: 0
      
    addParameter($param, $value);
      Add a query parameter
      ex:
        addParameter("q", "title:test");
        addParameter("q", "title:test");
        addParameter("fq", "features:Documentary");
        addParameter("fl", "category keywords");
        addParameter("sort", "category desc, keywords asc");
        
    addCustomParameter($param, $value)
      Add a custom parameter, mainly used for compatibility with different Solr versions
      
    getRawResponse();
      returns the Raw Solr server response
      
    getSolrCallsLog();
      returns a list of all the calls made to solr so far
      
    setHighlight(true|false);
      Set the highlight
      
    setSpatial(true|false);
      Set the spatial
      
    setSpellCheck(true|false);
      Set the spellcheck
      
    setFacets(object SolrFacetsHandler);
      Set the query facets
      
    doQuery();
      Performs the query to Solr
      
    doUpdate($data);
      Performs the update.
      $data can be a json in the native solr update format or can be an object
      the object must be a list of records, multiple values fields must be defined as lists too.
        ex:
          $dataToUpdate = array(
          	array(
          		  "id" => "1",
              	"name" => "John Smith",
              	"comments" => array("comment1", "comment2"),
              ),
          	array(
          		  "id" => "2",
              	"name" => "Mary White",
              	"comments" => array("comment1", "comment2"),
          	 )
          );
          
      
    getResults();
      gets the list of results
        
    getNumberOfResults();
      gets the number of results found
      
