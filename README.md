SolrClients
===========

Solr Clients written in different, web-oriented, languages

PHP:
===========

The PHP Version (PHP/SolrClient.php) has been written using PHP Version 5.5.9

This client handles many Solr features for queries and updates.
It is completely object oriented and lets the user fine graining the searches by its methods.

Very simple usage:
------------------
Query

    require_once("SolrClient.php");
      
    $client = new SolrClient("localhost", "8983");
    $client->setUriPath("/solr/collection1/");
    $client->addParameter("q", "*:*");
    $client->addParameter("start", 0);
    $client->addParameter("rows", 10);
  
    $facets = new SolrFacetHandler();
    $facets->addFacetField("category");
  
    $client->setFacets($facets);
    try {
        $client->doQuery();
    } catch (Exception $e) {
        echo "Caught Exception: " . $e->getMessage();
    }
  
    echo $client->getNumberOfResults();
    $docs = $client->getResults();
    foreach ($docs as $doc) {
        print_r($doc);
    }
  
  


