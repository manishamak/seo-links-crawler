<?php

namespace Slc\SeoLinksCrawler\Html_Parser;
use DomDocument;

class DomDocumentParser extends DOMDocument
{
    // public function __construct($version = '1.0', $encoding = 'UTF-8')
    // {
    //     parent::__construct($version, $encoding);
    //     // Additional initialization or configuration if needed
    // }

    public function loadHTMLDocument($html)
    {
        // libxml_use_internal_errors(true); // Disable libxml errors

        $this->loadHTML($html,  LIBXML_NOERROR | LIBXML_NOWARNING);

        // libxml_clear_errors(); // Clear libxml errors

        // Additional processing or validation if needed
    }

    public function gather_links(){
        $total_links = [];
        $get_tags = $this->getElementsByTagName('a');
        if ($get_tags->length){
            foreach($get_tags as $tag){
                array_push($total_links, $tag->getAttribute('href'));
            }
            $total_links = array_unique($total_links);
        }
        return $total_links;
    }
}