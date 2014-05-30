PHP Solr Client
===============

Please have a look at the Solr documentation for further functuonalities: <br/>
http://lucene.apache.org/solr/ <br/>
http://wiki.apache.org/solr/CommonQueryParameters <br/>
http://wiki.apache.org/solr/SimpleFacetParameters <br/>
 

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
      
      
  SolrFacetHandler:
  -----------
  Constructor:
  
    new SolrFacetHandler()
---------------------------------------------------------------------------------
      
      addFacetField($field);
        Adds a facet field, the parameter can be the field name or an object that identifies the field with its faceting options
        ex:
          addFacetField("category");
          addFacetField( array("field" => "category", "sort" => "index", "mincount" => "4") );
        
      addFacetField($fields);
        Adds all the facet fields at once, the parameter can be the field name or an object that identifies the field with its faceting options
        ex:
          addFacetFields(array("category", "name"));
          addFacetFields( array ( array("field" => "category", "sort" => "index", "mincount" => "4"), array("field" => "name", "sort" => "index", "mincount" => "4") ) );

      addFacetQuery($query);
        Adds a facet query:
        ex:
          addFacetQuery('name:"john"');
          
      addFacetQueries($queries);
        Add all the facet queries at once:
        ex:
          addFacetQueries(array('author:"john"', 'author:"mary"'));
          
      setFacetPrefix($prefix);
        Sets the facet prefix
        
      setFacetSort($sort);
        Sets the facet sort mode {count, index}
        
      setFacetLimit($limit);
        Sets the return facet limits
        
      setFacetOffset($offset);
        Sets the facet offset
        
      setFacetMincount($mincount);
        Sets the facet mincount
        
      setFacetMissing($missing)
        Sets the facet missing
        
      setFacetMethod($method);
        Sets the facet methods
        
      addFacetPivot($pivot);
        Adds the facet pivots
        ex:
          addFacetPivot("cat,popularity,inStock");
      
      addFacetDate($date);
        Adds a facet date field, or an object with the fields and its options
        ex:
          addFacetDate("last_update");
          addFacetDate(array("field" => "last_modified", "start" => "NOW/DAY-20DAYS", "end" => "NOW", "gap" => "+1DAY"));
            possible array keys:
            - field
            - start
            - end
            - gap
            - hardend
            - include
            - other
          
      addFacetDates($dates);
        Adds all the facet dates at once, the parameter must be a list of date fields or a list of objects which identifies the fields and their properties
        ex:
          addFacetDates(array("last_update", "first_update"));
          addFacetDates(array(array("field" => "last_modified", "start" => "NOW/DAY-20DAYS", "end" => "NOW", "gap" => "+1DAY"));
          , array("field" => "first_update", "start" => "NOW/DAY-20DAYS", "end" => "NOW", "gap" => "+1DAY"));
          ));
            possible array keys:
            - field
            - start
            - end
            - gap
            - hardend
            - include
            - other
          
      setFacetDateStart($start);
        Sets the facet date start
        
      setFacetDateEnd($end);
        Sets the facet date end
        
      setFacetDateGap($gap);
        Sets the facet date gap
        
      setFacetDateHardend($hardend);
        Sets the facet date hardend
        
      setFacetDateOther($other);
        Sets the date other
        
      setFacetDateInclude($include);
        Sets the facet date include
        
      addFacetRange($range);
        Adds a facet Range field, or an object with the fields and its options
        ex:
          addFacetRange($range);
          addFacetRange(array("field" => "price", "start" => "1", "end" => "100", "gap" => "2"));
            possible array keys:
            - field
            - start
            - end
            - gap
            - hardend
            - include
            - other
          
      addFacetRanges($ranges);
        Adds all the facet Ranges at once, the parameter must be a list of Range fields or a list of objects which identifies the fields and their properties
        ex:
          addFacetRanges(array("price", "metacritic"));
          addFacetRanges(array(array("field" => "price", "start" => "1", "end" => "100", "gap" => "2"));
          , array("field" => "metacritic", "start" => "1", "end" => "5", "gap" => "0.5"));
          ));
            possible array keys:
            - field
            - start
            - end
            - gap
            - hardend
            - include
            - other
          
      setFacetRangeStart($start);
        Sets the facet Range start
        
      setFacetRangeEnd($end);
        Sets the facet Range end
        
      setFacetRangeGap($gap);
        Sets the facet Range gap
        
      setFacetRangeHardend($hardend);
        Sets the facet Range hardend
        
      setFacetRangeOther($other);
        Sets the Range other
        
      setFacetRangeInclude($include);
        Sets the facet Range include  
      
      getFacetResponse();
        Gets all the facet response
      
      getFacetResponseQueries();
        Gets the facet response query
        
      getFacetResponseFields();
        Gets the facet response fields
        
      getFacetResponseRanges();
        Gets the facet response ranges
        
      getFacetResponseDates();
        Gets the facet response dates
        
      getFacetResponsePivots();
        Gets the facets response pivots
        
      getRawFacetResponse();
        Gets the facet raw response
      
        
        
        
        
      
        
        
          
