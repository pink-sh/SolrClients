PHP Solr Client
===============

Class Methods:
==============

  SolrClient:
  -----------
  Constructor:
  new SolrClient($host, $port)
  
  setUriPath($uri);
    Path where Solr is located (ex. /solr/)
    Default: none
    
  setRequestHandler($handler)
    Solr Request Handler
    Default: select
    
  setUpdateHandler($handler)
    Solr Update Handler
    Default: update
    
  setOutputType($type)
    Set the Query output types {json, xml, csv, python, ruby, php}
    Default: json
    
  addParameter($param, $value)
    
