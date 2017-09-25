<?php

namespace Samwilson\WaBmdScraper;

use DOMDocument;
use DOMXPath;
use Exception;
use Requests_Response;
use Requests_Session;

/**
 * An HTML scraper for the Western Australian Registry of Births, Deaths, and
 * Marriages (also known as the Pioneers Index).
 */
class PioneersIndexScraper {

    /** @var Requests_Session */
    protected $session;

    /** @var string */
    protected $url = 'http://www.bdm.dotag.wa.gov.au/_apps/pioneersindex/default.aspx';

    /** @var string */
    protected $fieldNamePrefix = 'ctl00$ctl00$MasterContent$pageContent$';

    /** @var Requests_Response */
    protected $currentRequest;

    /** @var string */
    protected $searchType;

    const BIRTHS = 1;
    const DEATHS = 2;
    const MARRIAGES = 3;

    public function __construct() {
        $headers = [
            'Referer' => $this->url,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:33.0) Gecko/20100101 Firefox/33.0',
        ];
        $options = [
            'timeout' => 60,
        ];
        $this->searchType = self::BIRTHS;
        $this->session = new Requests_Session();
        $this->session->headers = $headers;
        $this->session->options = $options;
    }

    /**
     * Get a list of the BMD record types.
     * @return string[] Type IDs, keyed by their title.
     */
    public function getAllowedTypes() {
        return [
            'births' => self::BIRTHS,
            'deaths' => self::DEATHS,
            'marriages' => self::MARRIAGES,
        ];
    }

    /**
     * Initialise the search type and session.
     * @param int $type One of the 'birth', 'death', or 'marriage' constants of this class.
     * @throws Exception
     */
    public function init($type) {
        if (!in_array($type, $this->getAllowedTypes())) {
            throw new Exception("'$type' is not an allowed search type.");
        }
        $this->searchType = $type;
        $this->currentRequest = $this->session->get($this->url);
    }

    /**
     * Get the first page of results. Call init() before this.
     * Including column headers.
     * @return array
     * @throws Exception
     */
    public function getPage1() {
        $postData = $this->getSearchFormParams($this->currentRequest->body);
        $postData[$this->fieldNamePrefix.'txtFromYear'] = '0';
        
        $data = $this->getPageData($postData);
        //file_put_contents('data/p1.html', $this->currentRequest->body);
        if (!isset($data[0])) {
            throw new Exception("Unable to find column headers in page 1.");
        }
        $colNames = array_keys($data[1]);
        return array_merge(array($colNames), $data);
    }

    /**
     * Get the next available page.
     * @return string[]
     */
    public function getPageN() {
        $postData = $this->getSearchFormParams($this->currentRequest->body);
        $postData['__EVENTTARGET'] = $this->fieldNamePrefix . 'grvSearchResults$ctl01$lnkNextPage';
        unset($postData[$this->fieldNamePrefix . 'btnSubmit']);
        return $this->getPageData($postData);
    }

    /**
     * Submit the POST request to the remote site, using $this->searchTerms as
     * the query parameters.
     * @return array The return value of $this->extractData().
     */
    protected function getPageData($postData) {
        $this->currentRequest = $this->session->post($this->url, array(), $postData);
        return $this->extractData($this->currentRequest->body);
    }

    /**
     * Get a multi-dimensional array of the data in the 'divResults' table.
     * @param string $source The source HTML.
     * @return array Multi-dimensional data from the results table.
     */
    protected function extractData($source) {
        // Extra types of whitespace, for trim()
        $whitespace = " \t\n\r\0\x0B\xC2\xA0";
        $rows = array();
        $dom = new DOMDocument();
        @$dom->loadHTML($source);
        $xpath = new DOMXPath($dom);

        // Get the table headers into their own array.
        $headers = array();
        $colNum = 0;
        $headerThs = $xpath->query("//table[@id='MasterContent_pageContent_grvSearchResults'][1]/tr[@class='listHeader']/th");
        foreach ($headerThs as $th) {
            $val = trim($th->nodeValue, $whitespace);
            if (empty($val)) {
                continue;
            }
            $headers[$colNum] = $th->nodeValue;
            $colNum++;
        }

        // Get the table data.
        $trs = $xpath->query("//table[@id='MasterContent_pageContent_grvSearchResults'][1]/tr");
        foreach ($trs as $tr) {
            if ($tr->getAttribute('class') == 'listLink') {
                continue;
            }
            $row = array();
            $colNum = 0;
            foreach ($tr->getElementsByTagName('td') as $td) {
                $val = trim($td->nodeValue, $whitespace);
                if (isset($headers[$colNum])) {
                    $row[$headers[$colNum]] = $val;
                }
                $colNum++;
            }
            if (count($row) > 0) {
                $rows[] = $row;
            }
        }
        // Tell the user that something's going on.
        if (isset($rows[0])) {
            echo join(', ', $rows[0]) . "\n";
        } else {
            echo "No rows found.\n";
        }
        return $rows;
    }

    /**
     * Get the keys and values of all search form elements.
     * @param string $html
     * @return string[]
     */
    protected function getSearchFormParams($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $input = $xpath->query("//input");
        $out = array();
        foreach ($input as $i) {
            $name = $i->getAttribute('name');
            if ($name) {
                $out[$name] = $i->getAttribute('value');
            }
        }
        // Reset missing ones and remove extraneous ones.
        $out[$this->fieldNamePrefix.'rblSearchType'] = $this->searchType;
        unset($out['query']);
        unset($out['']);
        unset($out['search']);
        return $out;
    }

}
