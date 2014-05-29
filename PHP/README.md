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
        
        
      
