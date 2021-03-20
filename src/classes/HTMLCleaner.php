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
		* Gets the node's attributes as a string
		*/
		public function getAttributeString(DOMElement $element): string{
			$attributes = "";
			if ($element->hasAttributes()){
				
				$attributes = " ";
				$counter = 0;

				foreach($element->attributes as $domAttr){
					$name = $domAttr->name;
					$value = $domAttr->value;
					$attributes .= sprintf(
						"%s=\"%s\"",
						$name,
						$value
					);

					++$counter;

					// Add a trailing space if there are more attributes to append
					if ($counter < $element->attributes->length){
						$attributes .= " ";
					}
				}
			}

			return $attributes;
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

			// Get the attribute string
			$attributes = $this->getAttributeString($node);

			// Append the tabs, opening element, and a newline
			$this->cleanHTML .= sprintf(
				"%s<%s%s>\n",
				$this->getTabs($tabDepth),
				$nodeName,
				$attributes,
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
