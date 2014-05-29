<?php

class SolrClient extends SolrFacetHandler{
	private $host;
	private $port;
	private $extUri;
	private $fq;
	private $queryParameters;
	private $customQueryParameters;

	private $queryParser = null;

	private $rows = 10;
	private $start = 0;
	private $outputType = "json";

	private $numResults = null;
	private $docs = null;

	private $highlight = false;
	private $facet = false;
	private $spatial = false;
	private $spellcheck = false;

	private $rawResponse = null;

	private $solrCallLogger = array();

	private $facets = null;

	private $requestHandler = "select";
	private $updateHandler = "update";

	private $allowedParameters = array("q","indent","fl","df","sort","q.alt","qf","mm","pf","ps","qs","tie","bq","bf","uf","pf2","pf3","ps2","ps3","ps2","boost","stopwords","lowercaseOperator","hl.fl","hl.simple.pre","hl.simple.post","hl.requireFieldMatch","hl.usePhraseHighlighter","hl.highlightMultiTerm","facet.query","facet.field","facet.prefix","pt","sfield","d","spellcheck.build","spellcheck.reload","spellcheck.q","spellcheck.dictionary","spellcheck.count","spellcheck.onlyMorePopular","spellcheck.extendedResults","spellcheck.collate","spellcheck.maxCollations","spellcheck.maxCollationTries","spellcheck.accuracy");

	function __construct($host, $port) {
		if ($this->startsWith(strtolower($host), "http://")) {
			$this->host = $host;
		} else {
			$this->host = "http://" . $host;
		}
		$this->port = $port;
		$this->fq = array();
		$this->queryParameters = array();
		$this->customQueryParameters = array();
	}

	public function doQuery() {
		$defaultWT = "wt=json";
		$wt = $defaultWT;
		if ($this->outputType != "json") {
			$wt = "wt=" . $this->outputType;
		} 

		$url = 	$this->host . ":" . $this->port . $this->extUri . $this->requestHandler . "?";
		$count = 0;

		if ($this->queryParser != null) {
			$url .= "defType=" . $this->queryParser;
			$count++;
		}

		if ($this->highlight) {
			if ($count > 0) { $url .= "&"; }
			$url .= "hl=true";
			$count++;
		}

		foreach ($this->queryParameters as $param) {
			if ($count > 0) { $url .= "&"; }
			$url .= $param;
			$count++;
		}

		foreach ($this->fq as $fq) {
			if ($count > 0) {
				$url .= "&";
			}
			$url .= "fq=" . urlencode($fq);
			$count++;
		}

		if ($this->start != 0) {
			$url .= "&start=" . $this->start;
		}
		if ($this->rows != 10) {
			$url .= "&rows=" . $this->rows;
		}

		if ($this->facet) {
			if ($count > 0) { $url .= "&"; }
			$url .= "facet=true";
			$count++;
		}
		if ($this->spatial) {
			if ($count > 0) { $url .= "&"; }
			$url .= "spatial=true";
			$count++;
		}
		if ($this->spellcheck) {
			if ($count > 0) { $url .= "&"; }
			$url .= "spellcheck=true";
			$count++;
		}

		$url .= $this->getFacetsQueryString();
		
		foreach ($this->customQueryParameters as $param) {
			if ($count > 0) { $url .= "&"; }
			$url .= $param;
			$count++;
		}

		if ($this->outputType != "json") {
			$wt = "wt=" . $this->outputType;
			$defwt = "wt=json";
			if ($count > 0) { $wt = "&" . $wt; $defwt = "&" . $defwt; }
			$this->setRawResponse($this->callSolr($url.$wt));
			$this->handleResponse($this->callSolr($url.$defwt));
		} else {
			$wt = "wt=json";
			if ($count > 0) { $wt = "&" . $wt; }
			$this->handleResponse($this->getRawResponse( $this->setRawResponse( $this->callSolr($url.$wt) ) ) );
		}
	}

	public function doUpdate($data) {
		if (is_array($data)) {
			$json = json_encode($data);
		} else if (is_object(json_decode($data))) {
			$json = $data;
		} else {
			throw new Exception("Error doing Update: Bad data received, expected Json or array");
		}

		$url = 	$this->host . ":" . $this->port . $this->extUri . $this->updateHandler . "?commit=true";
		$params = array(
	    	'http' => array
	      	(
	        	'method' => 'POST',
	          	'header'=>"Content-type:application/json",
	          	'content' => $json
	      	)
	   	);
	   	$ctx = stream_context_create($params);
	   	array_push($this->solrCallLogger, $url);
	   	$fp = fopen($url, 'rb', false, $ctx);
	   	if (!$fp) {
	   		throw new Exception ("Error Processing Update, Host is not reachable: {" . $url . "}");
	   	}
	   	$response = stream_get_contents($fp);
	   	if ($response == null || $response == "") {
	   		throw new Exception ("Error Processing Update [1] : {" . $url . "}");
	   	}
	   	$responseDecoded = json_decode($response);
	   	if ($responseDecoded->responseHeader->status != 0) {
	   		throw new Exception ("Error Processing Update [1] : {" . $url . "} -> Status: " . $responseDecoded->responseHeader->status . " QTime:" . $responseDecoded->responseHeader->QTime);	
	   	}
	   	return true;
	}

	public function addParameter($param, $value) {
		$param = strtolower($param);
		if (in_array($param, $this->allowedParameters)) {
			array_push($this->queryParameters, $param . "=" . urlencode($value));
		}
		else if ($param == "start") {
			$this->start = $value;
		}
		else if ($param == "rows") {
			$this->rows = $value;
		}
		else if ($param == "fq") {
			array_push($this->fq, $value);
		}
	}

	public function addCustomParameter($param, $value) {
		$param = strtolower($param);
		array_push($this->customQueryParameters, $param . "=" . urlencode($value));
	}

	
	private function handleResponse($json) {
		$decoded = json_decode($json);
		$this->numResults = $decoded->response->numFound;
		if ($this->numResults > 0) {
			$this->docs = array();
			foreach ($decoded->response->docs as $doc) {
				$tDoc = array();
				foreach ($doc as $key => $value) {
					$tDoc[$key]=$value;
				}
				array_push($this->docs, $tDoc);
			}
		}
	}


	private function callSolr($url) {
		array_push($this->solrCallLogger, $url);
		$err = $this->checkConnectionErrors($url);
		if ($err != false) {
			throw new Exception("Error Processing Request, Host returned Errors: " . $err . " {" . $url . "}", 1);
		} else {
			if(($callback = file_get_contents($url))) {
				if ($this->facets != null) {
					$this->facets->setFacetResponse($callback);
				}
				return $callback;
			} else {
				throw new Exception("Error Processing Request, Host is not reachable: {" . $url . "}", 1);
			}
		}
	}
	private function checkConnectionErrors($url) {
		$headers = get_headers($url);
		if ($headers[0] == "HTTP/1.1 200 OK") {
			return false;
		} else {
			return $headers[0];
		}
	}

	public function setRequestHandler($handler) {
		$this->requestHandler = $handler;
	}
	public function setUpdateHandler($handler) {
		$this->updateHandler = $handler;
	}

	private function setRawResponse($response) {
		$this->rawResponse = $response;
	}
	public function getRawResponse() {
		return $this->rawResponse;
	}
	public function getSolrCallsLog() {
		return $this->solrCallLogger;
	}

	public function setRows($rows) { $this->rows = $rows; }
	public function setStart($start) { $this->start = $start; }
	public function setOutputType($type) {
		$type = strtolower($type);
		if ($type == "json" || $type == "xml" || $type == "python" || $type == "ruby" || $type == "php" || $type == "csv") {
			$this->outputType = $type;
		}
	}
	public function setQueryParser($qp) {
		$qp = strtolower($qp); 
		if ($qp == "edismax" || $qp == "dismax") {
			$this->queryParser = $qp;
		}
	}
	public function setHighlight($hl) {
		$hl = strtolower($hl); 
		if ($hl == "true" || $hl == true) {
			$this->highlight = true;
		}
	}
	public function setSpatial($spatial) {
		$spatial = strtolower($spatial); 
		if ($spatial == "true" || $spatial == true) {
			$this->spatial = true;
		}
	}
	public function setSpellCheck($spellcheck) {
		$spellcheck = strtolower($spellcheck); 
		if ($spellcheck == "true" || $spellcheck == true) {
			$this->spellcheck = true;
		}
	}
	public function setUriPath($uri) {
		if (!$this->startsWith($uri, "/")) {
			$uri = "/" . $uri;
		}
		if (!$this->endsWith($uri, "/")) {
			$uri = $uri . "/";
		}
		$this->extUri = $uri;
	}
	public function getNumberOfResults() {
		return $this->numResults;
	}
	public function getResults() {
		return $this->docs;
	}

	public function setFacets ($facets) {
		$this->facets = $facets;
	}
	private function getFacetsQueryString() {
		$qs = "";
		if ($this->facets == null) {
			return $qs;
		}
		$qs .= "&facet=true";
		//set sort
		if ($this->facets->getFacetSort() != null) {
			if ($this->facets->getFacetSort() == "count" || $this->facets->getFacetSort() == "index") {
				$qs .= "&facet.sort=" . $this->facets->getFacetSort();
			}
		}

		if ($this->facets->getFacetPrefix() != null) {
			$qs .= "&facet.prefix=" . urlencode($this->facets->getFacetPrefix());
		}
		if ($this->facets->getFacetSort() != null) {
			$qs .= "&facet.sort=" . urlencode($this->facets->getFacetSort());
		}
		if ($this->facets->getFacetLimit() != null) {
			$qs .= "&facet.limit=" . urlencode($this->facets->getFacetLimit());
		}
		if ($this->facets->getFacetOffset() != null) {
			$qs .= "&facet.offset=" . urlencode($this->facets->getFacetOffset());
		}
		if ($this->facets->getFacetMincount() != null) {
			$qs .= "&facet.mincount=" . urlencode($this->facets->getFacetMincount());
		}
		if ($this->facets->getFacetMissing() != null) {
			$qs .= "&facet.missing=" . urlencode($this->facets->getFacetMissing());
		}
		if ($this->facets->getFacetMethod() != null) {
			$qs .= "&facet.method=" . urlencode($this->facets->getFacetMethod());
		}
		//set fields
		foreach ($this->facets->getFacetFields() as $field) {
			if (is_array($field)) {
				$fr = null;
				$prefix = null;
				$sort = null;
				$limit = null;
				$offset = null;
				$mincount = null;
				$missing = null;
				$method = null;
				foreach ($field as $key=>$val) {
					$key = strtolower($key);
					switch ($key) {
						case 'field':
							$fr = $val;
							break;
						case 'prefix':
							$prefix = $val;
							break;
						case 'sort':
							$sort = $val;
							break;
						case 'limit':
							$limit = $val;
							break;
						case 'offset':
							$offset = $val;
							break;
						case 'mincount':
							$mincount = $val;
							break;
						case 'missing':
							$missing = $val;
							break;
						case 'method':
							$method = $val;
							break;
					}
				}
				if ($fr != null) {
					$qs .= "&facet.field=" . urlencode($fr);
					if ($prefix != null) {
						$qs .= "&f." . $fr . ".facet.prefix=" . urlencode($prefix);
					}
					if ($sort != null) {
						$qs .= "&f." . $fr . ".facet.sort=" . urlencode($sort);
					}
					if ($limit != null) {
						$qs .= "&f." . $fr . ".facet.limit=" . urlencode($limit);
					}
					if ($offset != null) {
						$qs .= "&f." . $fr . ".facet.offset=" . urlencode($offset);
					}
					if ($mincount != null) {
						$qs .= "&f." . $fr . ".facet.mincount=" . urlencode($mincount);
					}
					if ($missing != null) {
						$qs .= "&f." . $fr . ".facet.missing=" . urlencode($missing);
					}
					if ($method != null) {
						$qs .= "&f." . $fr . ".facet.method=" . urlencode($method);
					}
				}
			} else if (is_string($field)) {
				$qs .= "&facet.field=" . urlencode($field);
			}
		}

		//set queries
		foreach ($this->facets->getFacetQueries() as $query) {
			$qs .= "&facet.query=" . urlencode($query);
		}

		//set pivots
		foreach ($this->facets->getFacetPivots() as $pivot) {
			$qs .= "&facet.pivot=" . urlencode($pivot);
		}

		//set ranges
		if ($this->facets->getFacetRangeStart() != null) {
			$qs .= "&facet.range.start=" . urlencode($this->facets->getFacetRangeStart());
		}
		if ($this->facets->getFacetRangeEnd() != null) {
			$qs .= "&facet.range.end=" . urlencode($this->facets->getFacetRangeEnd());
		}
		if ($this->facets->getFacetRangeGap() != null) {
			$qs .= "&facet.range.gap=" . urlencode($this->facets->getFacetRangeGap());
		}
		if ($this->facets->getFacetRangeHardend() != null) {
			$qs .= "&facet.range.hardend=" . urlencode($this->facets->getFacetRangeHardend());
		}
		if ($this->facets->getFacetRangeOther() != null) {
			$qs .= "&facet.range.other=" . urlencode($this->facets->getFacetRangeOther());
		}
		if ($this->facets->getFacetRangeInclude() != null) {
			$qs .= "&facet.range.include=" . urlencode($this->facets->getFacetRangeInclude());
		}

		foreach ($this->facets->getFacetRanges() as $range) {
			if (is_array($range)) {
				$fr = null;
				$start = null;
				$end = null;
				$gap = null;
				$hardend = null;
				$other = null;
				$include = null;
				foreach ($range as $key=>$val) {
					$key = strtolower($key);
					switch ($key) {
						case 'field':
							$fr = $val;
							break;
						case 'start':
							$start = $val;
							break;
						case 'end':
							$end = $val;
							break;
						case 'gap':
							$gap = $val;
							break;
						case 'hardend':
							$hardend = $val;
							break;
						case 'other':
							$other = $val;
							break;
						case 'include':
							$include = $val;
							break;
					}
				}
				if ($fr != null) {
					$qs .= "&facet.range=" . urlencode($fr);
					if ($start != null) {
						$qs .= "&f." . $fr . ".facet.range.start=" . urlencode($start);
					}
					if ($end != null) {
						$qs .= "&f." . $fr . ".facet.range.end=" . urlencode($end);
					}
					if ($gap != null) {
						$qs .= "&f." . $fr . ".facet.range.gap=" . urlencode($gap);
					}
					if ($hardend != null) {
						$qs .= "&f." . $fr . ".facet.range.hardend=" . urlencode($hardend);
					}
					if ($other != null) {
						$qs .= "&f." . $fr . ".facet.range.other=" . urlencode($other);
					}
					if ($include != null) {
						$qs .= "&f." . $fr . ".facet.range.include=" . urlencode($include);
					}
				}
			} else if (is_string($range)) {
				$qs .= "&facet.range=" . urlencode($range);
			}
		}

		//set dates
		if ($this->facets->getFacetDateStart() != null) {
			$qs .= "&facet.date.start=" . urlencode($this->facets->getFacetDateStart());
		}
		if ($this->facets->getFacetDateEnd() != null) {
			$qs .= "&facet.date.end=" . urlencode($this->facets->getFacetDateEnd());
		}
		if ($this->facets->getFacetDateGap() != null) {
			$qs .= "&facet.date.gap=" . urlencode($this->facets->getFacetDateGap());
		}
		if ($this->facets->getFacetDateHardend() != null) {
			$qs .= "&facet.date.hardend=" . urlencode($this->facets->getFacetDateHardend());
		}
		if ($this->facets->getFacetDateOther() != null) {
			$qs .= "&facet.date.other=" . urlencode($this->facets->getFacetDateOther());
		}
		if ($this->facets->getFacetDateInclude() != null) {
			$qs .= "&facet.date.include=" . urlencode($this->facets->getFacetDateInclude());
		}

		foreach ($this->facets->getFacetDates() as $date) {
			if (is_array($date)) {
				$fr = null;
				$start = null;
				$end = null;
				$gap = null;
				$hardend = null;
				$other = null;
				$include = null;
				foreach ($date as $key=>$val) {
					$key = strtolower($key);
					switch ($key) {
						case 'field':
							$fr = $val;
							break;
						case 'start':
							$start = $val;
							break;
						case 'end':
							$end = $val;
							break;
						case 'gap':
							$gap = $val;
							break;
						case 'hardend':
							$hardend = $val;
							break;
						case 'other':
							$other = $val;
							break;
						case 'include':
							$include = $val;
							break;
					}
				}
				if ($fr != null) {
					$qs .= "&facet.date=" . $fr;
					if ($start != null) {
						$qs .= "&f." . $fr . ".facet.date.start=" . urlencode($start);
					}
					if ($end != null) {
						$qs .= "&f." . $fr . ".facet.date.end=" . urlencode($end);
					}
					if ($gap != null) {
						$qs .= "&f." . $fr . ".facet.date.gap=" . urlencode($gap);
					}
					if ($hardend != null) {
						$qs .= "&f." . $fr . ".facet.date.hardend=" . urlencode($hardend);
					}
					if ($other != null) {
						$qs .= "&f." . $fr . ".facet.date.other=" . urlencode($other);
					}
					if ($include != null) {
						$qs .= "&f." . $fr . ".facet.date.include=" . urlencode($include);
					}
				}
			} else if (is_string($date)) {
				$qs .= "&facet.date=" . urlencode($date);
			}
		}

		return $qs;
	}

	private function startsWith($haystack, $needle)
	{
    	$length = strlen($needle);
    	return (substr($haystack, 0, $length) === $needle);
	}
	private function endsWith($haystack, $needle)
	{
    	$length = strlen($needle);
    	if ($length == 0) {
        	return true;
    	}
    	return (substr($haystack, -$length) === $needle);
	}
}

class SolrFacetHandler {
	private $facetFields = null;
	private $facetQueries = null;
	private $facetPivots = null;
	private $facetRanges = null;
	private $facetDates = null;

	private $facetPrefix = null;
	private $facetSort = null;
	private $facetLimit = null;
	private $facetOffset = null;
	private $facetMincount = null;
	private $facetMissing = null;
	private $facetMethod = null;

	private $rangeStart = null;
	private $rangeEnd = null;
	private $rangeGap = null;
	private $rangeHardend = null;
	private $rangeOther = null;
	private $rangeInclude = null;
	private $dateStart = null;
	private $dateEnd = null;
	private $dateGap = null;
	private $dateHardend = null;
	private $dateOther = null;
	private $dateInclude = null;

	private $solrResponse = null;

	private $results = null;

	private $facetFieldsResults = null;
	private $facetQueriesResults = null;
	private $facetPivotsResults = null;
	private $facetRangesResults = null;
	private $facetDatesResults = null;

	function __construct() {
		$this->facetFields = array();
		$this->facetQueries = array();
		$this->facetPivots = array();
		$this->facetRanges = array();
		$this->facetDates = array();
	}

	public function addFacetField($field) {
		array_push($this->facetFields, $field);
	}
	public function addFacetFields($fields) {
		$this->facetFields = $fields;
	}
	public function getFacetFields() {
		return $this->facetFields;
	}

	public function addFacetQuery($query) {
		array_push($this->facetQueries, $query);
	}
	public function addFacetQueries($queries) {
		$this->facetQueries = $queries;
	}
	public function getFacetQueries() {
		return $this->facetQueries;
	}


	public function setFacetPrefix($prefix) {
		$this->facetPrefix = $prefix;
	}
	public function getFacetPrefix() {
		return $this->facetPrefix;
	}
	public function setFacetSort($sort) {
		$this->facetSort = $sort;
	}
	public function getFacetSort() {
		return $this->facetSort;
	}
	public function setFacetLimit($limit) {
		$this->facetLimit = $limit;
	}
	public function getFacetLimit() {
		return $this->facetLimit;
	}
	public function setFacetOffset($offset) {
		$this->facetSort = $offset;
	}
	public function getFacetOffset() {
		return $this->facetOffset;
	}
	public function setFacetMincount($mincount) {
		$this->facetMincount = $mincount;
	}
	public function getFacetMincount() {
		return $this->facetMincount;
	}
	public function setFacetMissing($missing) {
		$this->facetMissing = $missing;
	}
	public function getFacetMissing() {
		return $this->facetMissing;
	}
	public function setFacetMethod($method) {
		$this->facetMethod = $method;
	}
	public function getFacetMethod() {
		return $this->facetMethod;
	}

	public function addFacetPivot($pivot) {
		array_push($this->facetPivots, $pivot);
	}
	public function addFacetPivots($pivots) {
		$this->facetPivots = $pivots;
	}
	public function getFacetPivots() {
		return $this->facetPivots;
	}

	public function addFacetRange($range) {
		array_push($this->facetRanges, $range);
	}
	public function addFacetRanges($ranges) {
		$this->facetRanges = $ranges;
	}
	public function getFacetRanges() {
		return $this->facetRanges;
	}
	public function setFacetRangeStart($start) {
		$this->rangeStart = $start;
	}
	public function getFacetRangeStart() {
		return $this->rangeStart;
	}
	public function setFacetRangeEnd($end) {
		$this->rangeEnd = $end;
	}
	public function getFacetRangeEnd() {
		return $this->rangeEnd;
	}
	public function setFacetRangeGap($gap) {
		$this->rangeGap = $gap;
	}
	public function getFacetRangeGap() {
		return $this->rangeGap;
	}
	public function setFacetRangeHardend($hardend) {
		$this->rangeHardend = $hardend;
	}
	public function getFacetRangeHardend() {
		return $this->rangeHardend;
	}
	public function setFacetRangeOther($other) {
		$this->rangeOther = $other;
	}
	public function getFacetRangeOther() {
		return $this->rangeOther;
	}
	public function setFacetRangeInclude($include) {
		$this->rangeInclude = $include;
	}
	public function getFacetRangeInclude() {
		return $this->rangeInclude;
	}


	public function addFacetDate($date) {
		array_push($this->facetDates, $date);
	}
	public function addFacetDates($dates) {
		$this->facetDates = $dates;
	}
	public function getFacetDates() {
		return $this->facetDates;
	}
	public function setFacetDateStart($start) {
		$this->dateStart = $start;
	}
	public function getFacetDateStart() {
		return $this->dateStart;
	}
	public function setFacetDateEnd($end) {
		$this->dateEnd = $end;
	}
	public function getFacetDateEnd() {
		return $this->dateEnd;
	}
	public function setFacetDateGap($gap) {
		$this->dateGap = $gap;
	}
	public function getFacetDateGap() {
		return $this->dateGap;
	}
	public function setFacetDateHardend($hardend) {
		$this->dateHardend = $hardend;
	}
	public function getFacetDateHardend() {
		return $this->dateHardend;
	}
	public function setFacetDateOther($other) {
		$this->dateOther = $other;
	}
	public function getFacetDateOther() {
		return $this->dateOther;
	}
	public function setFacetDateInclude($include) {
		$this->dateInclude = $include;
	}
	public function getFacetDateInclude() {
		return $this->dateInclude;
	}

	protected function setFacetResponse($response) {
		$this->solrResponse = json_decode($response);
		
		if (isset($this->solrResponse->facet_counts)) {
			if (isset($this->solrResponse->facet_counts->facet_queries)) {
				$this->facetQueriesResults = array();
				foreach ($this->solrResponse->facet_counts->facet_queries as $k=>$v) {
					array_push($this->facetQueriesResults, array($k=>$v));
				}
				$this->results['facet_queries'] = $this->facetQueriesResults;
			}
			if (isset($this->solrResponse->facet_counts->facet_fields)) {
				$this->facetFieldsResults = array();
				foreach ($this->solrResponse->facet_counts->facet_fields as $k=>$v) {
					$t_ = array();
					$n = 1;
					foreach ($v as $v_) {
						if ($n % 2 != 0) {
  							$f_ = $v_;
						} else {
							$t_[$k][$f_] = $v_;
						}
						$n++;
					}
					array_push($this->facetFieldsResults, array($k => $t_));
				}
				$this->results['facet_fields'] = $this->facetFieldsResults;
			}
			if (isset($this->solrResponse->facet_counts->facet_dates)) {
				$this->facetDatesResults = array();
				foreach ($this->solrResponse->facet_counts->facet_dates as $k=>$v) {
					$t_ = array();
					foreach ($v as $k_=>$v_) {
						$t_[$k][$k_] = $v_;
					}
					array_push($this->facetDatesResults, $t_);
				}
				$this->results['facet_dates'] = $this->facetDatesResults;
			}

			if (isset($this->solrResponse->facet_counts->facet_pivot)) {
				$this->facetPivotsResults = array();
				foreach ($this->solrResponse->facet_counts->facet_pivot as $k=>$v) {
					array_push($this->facetPivotsResults, array($k => $v));
				}
				$this->results['facet_pivots'] = $this->facetPivotsResults;
			}

			if (isset($this->solrResponse->facet_counts->facet_ranges)) {
				$this->facetRangesResults = array();
				foreach ($this->solrResponse->facet_counts->facet_ranges as $k=>$v) {
					$t_ = array();
					foreach($v as $k_=>$v_) {
						if ($k_ == "counts") {
							$n = 1;
							foreach ($v_ as $v__) {
								if ($n % 2 != 0) {
									$f_ = $v__;
								} else {
									$t_[$k_][$f_] = $v__;
								}
								$n++;
							}
						} else {
							$t_[$k_] = $v_;
						}
					}
					array_push ($this->facetRangesResults, array($k => $t_));
				}
				$this->results['facet_ranges'] = $this->facetRangesResults;
			}
		}
	}

	public function getFacetResponse() {
		return $this->results;
	}
	public function getFacetResponseQueries() {
		return $this->results['facet_queries'];
	}
	public function getFacetResponseFields() {
		return $this->results['facet_fields'];
	}
	public function getFacetResponseRanges() {
		return $this->results['facet_ranges'];
	}
	public function getFacetResponseDates() {
		return $this->results['facet_dates'];
	}
	public function getFacetResponsePivots() {
		return $this->results['facet_pivots'];
	}
	public function getRawFacetResponse() {
		return $this->solrResponse->facet_counts;
	}
}

?>
