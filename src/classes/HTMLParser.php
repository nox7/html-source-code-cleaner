<?php
	class HTMLParser{

		public string $html;
		public DOMDocument $document;

		public function __construct(string $html){
			libxml_use_internal_errors(true);
			$document = new DOMDocument();
			$document->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			$this->html = $html;
			$this->document = $document;
		}

		/**
		* Returns the inner HTML of a provided node
		*/
		public function getInnerHTML(DOMNode $node): string{
			$innerHTML = "";
			$children = $node->childNodes;
			foreach($children as $childNode){
				$innerHTML .= $this->document->saveHTML($childNode);
			}

			return $innerHTML;
		}
	}
