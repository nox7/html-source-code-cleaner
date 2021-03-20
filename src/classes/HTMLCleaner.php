<?php
	require_once __DIR__ . "/HTMLParser.php";
	require_once __DIR__ . "/HTMLNodeSettings.php";

	class HTMLCleaner{

		private string $tabCharacter = "\t";
		public string $cleanHTML;
		public HTMLParser $parser;
		public HTMLNodeSettings $nodeSettings;

		/** @property DOMElement $currentElementContext The current DOMElement the cleaner is inside */
		public ?DOMElement $currentElementContext;

		public function __construct(string $html, HTMLNodeSettings $nodeSettings){
			$parser = new HTMLParser($html);
			$this->nodeSettings = $nodeSettings;
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
		* Checks if the siblings of an element are non-empty text nodes
		*/
		public function hasNonEmptyTextSiblings(DOMElement $element): bool{
			if (property_exists($element, "previousSibling")){
				$prevSibling = $element->previousSibling;
			}

			if (property_exists($element, "nextSibling")){
				$nextSibling = $element->nextSibling;
			}

			if (isset($prevSibling) && $prevSibling->nodeType === XML_TEXT_NODE){
				if (trim($prevSibling->textContent) !== ""){
					return true;
				}
			}

			if (isset($nextSibling) && $nextSibling->nodeType === XML_TEXT_NODE){
				if (trim($nextSibling->textContent) !== ""){
					return true;
				}
			}

			return false;
		}

		/**
		* Checks if the siblings of an element are non-empty text nodes or inline elements
		*/
		public function hasPrevTextOrInlineSibling(DOMNode $node): bool{
			if (property_exists($node, "previousSibling")){
				$prevSibling = $node->previousSibling;
			}

			if (isset($prevSibling)){
				if ($prevSibling->nodeType === XML_TEXT_NODE){
					if (trim($prevSibling->textContent) !== ""){
						return true;
					}
				}
				if ($this->nodeSettings->isInlineElement($prevSibling->nodeName)){
					return true;
				}
			}

			return false;
		}

		/**
		* Checks if the siblings of an element are non-empty text nodes or inline elements
		*/
		public function hasNextTextOrInlineSibling(DOMNode $node): bool{
			if (property_exists($node, "nextSibling")){
				$nextSibling = $node->nextSibling;
			}

			if (isset($nextSibling)){
				if ($nextSibling->nodeType === XML_TEXT_NODE){
					if (trim($nextSibling->textContent) !== ""){
						return true;
					}
				}
				if ($this->nodeSettings->isInlineElement($nextSibling->nodeName)){
					return true;
				}
			}

			return false;
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
				if ($this->hasPrevTextOrInlineSibling($textNode) || $this->hasNextTextOrInlineSibling($textNode)){
					if ($this->hasPrevTextOrInlineSibling($textNode) && !$this->hasNextTextOrInlineSibling($textNode)){
						$this->cleanHTML .= sprintf(
							" %s\n",
							$textContent,
						);
					}elseif(!$this->hasPrevTextOrInlineSibling($textNode) && $this->hasNextTextOrInlineSibling($textNode)){
						if ($this->nodeSettings->isInlineElement($textNode->parentNode->nodeName)){
							$this->cleanHTML .= sprintf(
								"%s",
								$textContent,
							);
						}else{
							$this->cleanHTML .= sprintf(
								"%s%s ",
								$this->getTabs($tabDepth),
								$textContent,
							);
						}
					}else{
						// Both siblings are inline or text nodes
					}
				}else{
					if ($this->nodeSettings->isInlineElement($textNode->parentNode->nodeName)){
						if ($this->hasNonEmptyTextSiblings($textNode->parentNode)){
							$this->cleanHTML .= sprintf(
								"%s",
								$textContent,
							);
						}else{
							$this->cleanHTML .= sprintf(
								"%s%s\n",
								$this->getTabs($tabDepth),
								$textContent,
							);
						}
					}else{
						$this->cleanHTML .= sprintf(
							"%s%s\n",
							$this->getTabs($tabDepth),
							$textContent,
						);
					}
				}
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
			$nodeSettings = $this->nodeSettings;

			// Check the element type to determine the tabbing and spacing
			// as well as the need to check for inner children at all
			if ($nodeSettings->isBlockElement($nodeName)){

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
			}elseif ($nodeSettings->isIsolatedSelfClosingElement($nodeName)){
				$this->cleanHTML .= sprintf(
					"%s<%s%s>\n",
					$this->getTabs($tabDepth),
					$nodeName,
					$attributes,
				);
			}elseif ($nodeSettings->isInlineElement($nodeName)){
				if ($this->hasNonEmptyTextSiblings($node)){
					// True inline element with other text
					$this->cleanHTML .= sprintf(
						"<%s%s>",
						$nodeName,
						$attributes,
					);

					// Check for children
					if ($node->childNodes->length > 0){
						$this->iterateChildren($node, $tabDepth + 1);
					}

					$this->cleanHTML .= sprintf(
						"</%s>",
						$nodeName,
					);
				}else{
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
			}elseif ($nodeSettings->isInlineSelfClosingElement($nodeName)){
				$this->cleanHTML .= sprintf(
					"<%s%s>",
					$nodeName,
					$attributes,
				);
			}
		}
	}
