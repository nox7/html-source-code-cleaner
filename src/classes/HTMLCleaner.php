<?php
	require_once __DIR__ . "/HTMLParser.php";

	class HTMLCleaner{

		private string $tabCharacter = "\t";
		public string $cleanHTML;
		public HTMLParser $parser;

		/** @property DOMElement $currentElementContext The current DOMElement the cleaner is inside */
		public ?DOMElement $currentElementContext;

		public function __construct(string $html){
			$parser = new HTMLParser($html);
			$this->cleanHTML = "<!doctype html>\n";
			$this->parser = $parser;
		}

		/**
		* Begins the HTML cleaner on the document
		*/
		public function cleanDocument(){
			$this->iterateChildren($this->parser->document);
		}

		/**
		* Iterate over the children of a dom node
		*/
		public function iterateChildren(DOMNode $parentNode, int $tabDepth = 0){
			foreach($parentNode->childNodes as $domNode){

				$nodeType = $domNode->nodeType;
				if ($nodeType === XML_DOCUMENT_TYPE_NODE){
					// Ignore
				}elseif ($nodeType === XML_TEXT_NODE){
					$this->processText($domNode, $tabDepth);
				}elseif ($nodeType === XML_ELEMENT_NODE){
					$this->processNode($domNode, $tabDepth);
				}
			}
		}

		/**
		* Gets the proper amount of tabs
		*/
		public function getTabs(int $tabDepth): string{
			if ($tabDepth > 0){
				return str_repeat($this->tabCharacter, $tabDepth);
			}else{
				return "";
			}
		}

		/**
		* Process a text node
		*/
		public function processText(DOMText $textNode, int $tabDepth){
			$textContent = $textNode->textContent;

			// Trim whitespace from the textContent
			// TODO Don't do this for some elements? Check $currentElementContext
			$textContent = trim($textContent);

			if ($textContent !== ""){
				$this->cleanHTML .= sprintf(
					"%s%s\n",
					$this->getTabs($tabDepth),
					$textContent,
				);
			}
		}

		/**
		* Process an element node
		*/
		public function processNode(DOMElement $node, int $tabDepth){
			// Set the cleaner's current element context
			$this->currentElementContext = $node;
			$nodeName = $node->nodeName;

			// Append the tabs, opening element, and a newline
			$this->cleanHTML .= sprintf(
				"%s<%s>\n",
				$this->getTabs($tabDepth),
				$nodeName,
			);

			// Check for children
			if ($node->childNodes->length > 0){
				$this->iterateChildren($node, $tabDepth + 1);
			}

			// Append the tabs, closing element, and a newline
			$this->cleanHTML .= sprintf(
				"%s</%s>\n",
				$this->getTabs($tabDepth),
				$nodeName,
			);
		}
	}
