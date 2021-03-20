<?php

	/*
		NOTE: The inner DOM parser will have issues if the HTML document
		is a fragment. For example, a fragment without the outer <html> will not
		properly parse a <style></style> block for some reason. This class
		must turn fragments into HTML documents.
	*/

	require_once __DIR__ . "/HTMLParser.php";
	require_once __DIR__ . "/HTMLNodeSettings.php";

	class HTMLCleaner{

		private string $tabCharacter = "\t";
		private bool $isFragment;

		public string $cleanHTML;
		public HTMLParser $parser;
		public HTMLNodeSettings $nodeSettings;

		/** @property DOMElement $currentElementContext The current DOMElement the cleaner is inside */
		public ?DOMElement $currentElementContext;

		public function __construct(string $html, HTMLNodeSettings $nodeSettings){
			$parser = new HTMLParser($html);
			$this->nodeSettings = $nodeSettings;
			$this->parser = $parser;

			if ($this->parser->document->getElementsByTagName("html")->length === 0){
				$this->isFragment = true;
				$html = "<!DOCTYPE html><html><body>$html</body></html>";
				$parser = new HTMLParser($html);
				$this->parser = $parser;
			}else{
				$this->isFragment = false;
			}

			$this->cleanHTML = "<!DOCTYPE html>\n";
		}

		/**
		* Begins the HTML cleaner on the document
		*/
		public function cleanDocument(){
			$this->iterateChildren($this->parser->document);

			// If this document was originally a fragment, turn it back into a fragment
			// now that parsing is done.
			if ($this->isFragment){
				// Iterate over every line and remove two tab characters
				$newCleanHTML = "";
				$lines = explode("\n", $this->cleanHTML);
				foreach($lines as $lineNumber=>$line){
					// Dont count the fragment's doctype, <html>, <body>, </body>, or </html>
					if ($lineNumber > 2 && $lineNumber < count($lines) - 3){
						$newCleanHTML .= substr($line, 2) . "\n";
					}
				}
				$this->cleanHTML = $newCleanHTML;
			}
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
				}else{
					$this->processCharacterData($domNode, $tabDepth);
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
		* Check if a node has only text children
		*/
		public function hasOnlyTextChildren(DOMElement $element): bool{
			foreach ($element->childNodes as $node){
				if ($node->nodeType !== XML_TEXT_NODE){
					return false;
				}
			}

			return true;
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
				if (
					$this->nodeSettings->isInlineElement($prevSibling->nodeName)
					||
					$this->nodeSettings->isInlineSelfClosingElement($prevSibling->nodeName)
				){
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
				if (
					$this->nodeSettings->isInlineElement($nextSibling->nodeName)
					||
					$this->nodeSettings->isInlineSelfClosingElement($nextSibling->nodeName)
				){
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
			$textContentTrimmed = trim($textContent);

			if ($textContentTrimmed !== ""){
				if ($this->hasPrevTextOrInlineSibling($textNode) || $this->hasNextTextOrInlineSibling($textNode)){
					if ($this->hasPrevTextOrInlineSibling($textNode) && !$this->hasNextTextOrInlineSibling($textNode)){
						// The previous sibling is a text node or an inline element
						// but there is no next sibling. The ending content needs to be rtrim'd

						// Further, check for cases where the previous sibling is an inline element and not a text node
						if ($textNode->previousSibling->nodeType === XML_TEXT_NODE){
							// CAN TWO TEXT NODES EVEN BE NEXT TO EACH OTHER?
							// Probably not, so this should NEVER happen and is a useless if statement.
							$this->cleanHTML .= sprintf(
								"%s\n",
								rtrim($textContent),
							);
						}else{
							$this->cleanHTML .= sprintf(
								"\n%s%s\n",
								$this->getTabs($tabDepth),
								$textContentTrimmed,
							);
						}
					}elseif(!$this->hasPrevTextOrInlineSibling($textNode) && $this->hasNextTextOrInlineSibling($textNode)){
						// There is a next text or inline sibling, but not a previous
						if ($this->nodeSettings->isInlineElement($textNode->parentNode->nodeName)){
							// The parent node is an inline element
							$this->cleanHTML .= sprintf(
								"%s",
								$textContent,
							);
						}else{
							// The parent node is not an inline element
							// and this is the first element (no previous sibling) in the parent.
							// It should be left-trimmed and right-trimmed of new lines
							$this->cleanHTML .= sprintf(
								"%s%s",
								$this->getTabs($tabDepth),
								rtrim(ltrim($textContent), "\n\t"),
							);
						}
					}else{
						// Both siblings are inline or text nodes
						// Use left trimmed text content without tabbing
						$this->cleanHTML .= sprintf(
							"%s",
							ltrim($textContent),
						);
					}
				}else{
					// There are no siblings
					if ($this->nodeSettings->isInlineElement($textNode->parentNode->nodeName)){
						// The parent node is an inline element
						if ($this->hasNonEmptyTextSiblings($textNode->parentNode)){
							// The parent node has text node siblings, so this could be an
							// inline element with textual siblings (such as an anchor in
							// text or a strong, i, em, element)
							$this->cleanHTML .= sprintf(
								"%s",
								$textContentTrimmed,
							);
						}else{
							// The inline element has no textual siblings
							// it needs tabbing
							$this->cleanHTML .= sprintf(
								"%s%s\n",
								$this->getTabs($tabDepth),
								$textContentTrimmed,
							);
						}
					}else{
						// No siblings and the parent node is not an inline element
						$this->cleanHTML .= sprintf(
							"%s%s\n",
							$this->getTabs($tabDepth),
							$textContentTrimmed,
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
				if ($node->childNodes->length === 0){
					$this->cleanHTML .= sprintf(
						"%s<%s%s></%s>\n",
						$this->getTabs($tabDepth),
						$nodeName,
						$attributes,
						$nodeName,
					);
				}else{
					if ($this->hasOnlyTextChildren($node)){
						$innerHTML = trim($this->parser->getInnerHTML($node));
						if (strlen($innerHTML) > $this->nodeSettings->maxTextualBlockElementCharacters){
							// Too many characters. Split it into a tabbed section inside the block
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
						}else{
							$this->cleanHTML .= sprintf(
								"%s<%s%s>%s</%s>\n",
								$this->getTabs($tabDepth),
								$nodeName,
								$attributes,
								$innerHTML,
								$nodeName,
							);
						}
					}else{
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
			}elseif ($nodeSettings->isIsolatedSelfClosingElement($nodeName)){
				$this->cleanHTML .= sprintf(
					"%s<%s%s>\n",
					$this->getTabs($tabDepth),
					$nodeName,
					$attributes,
				);
			}elseif ($nodeSettings->isInlineElement($nodeName)){
				if ($this->hasPrevTextOrInlineSibling($node) || $this->hasNextTextOrInlineSibling($node)){
					// True inline element with other text
					if ($this->hasPrevTextOrInlineSibling($node) && !$this->hasNextTextOrInlineSibling($node)){
						$this->cleanHTML .= sprintf(
							"<%s%s>",
							$nodeName,
							$attributes,
						);
					}elseif (!$this->hasPrevTextOrInlineSibling($node) && $this->hasNextTextOrInlineSibling($node)){
						$this->cleanHTML .= sprintf(
							"%s<%s%s>",
							$this->getTabs($tabDepth),
							$nodeName,
							$attributes,
						);
					}else{
						// Has both textual or inline siblings around it
						// This is an odd case and will never look good. For example:
						/*
							Hello <span> <img src=""> </span> there!
						*/
						// That is when this case happens
						$this->cleanHTML .= sprintf(
							"<%s%s>",
							$nodeName,
							$attributes,
						);
					}

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
				if ($this->hasPrevTextOrInlineSibling($node) || $this->hasNextTextOrInlineSibling($node)){
					if ($this->hasPrevTextOrInlineSibling($node) && !$this->hasNextTextOrInlineSibling($node)){
						$this->cleanHTML .= sprintf(
							"<%s%s>\n",
							$nodeName,
							$attributes,
						);
					}elseif (!$this->hasPrevTextOrInlineSibling($node) && $this->hasNextTextOrInlineSibling($node)){
						$this->cleanHTML .= sprintf(
							"%s<%s%s>",
							$this->getTabs($tabDepth),
							$nodeName,
							$attributes,
						);
					}else{
						$this->cleanHTML .= sprintf(
							"<%s%s>",
							$nodeName,
							$attributes,
						);
					}
				}else{
					$this->cleanHTML .= sprintf(
						"%s<%s%s>\n",
						$this->getTabs($tabDepth),
						$nodeName,
						$attributes,
					);
				}
			}
		}

		/**
		* Processes document character data. This is usually JavaScript source code
		* or CSS in either <script> or <style> elements.
		*/
		public function processCharacterData(DOMCharacterData $node, $tabDepth){
			// Break into lines and just make sure they are minimally tabbed
			$lines = explode("\n", $node->textContent);
			$numBaseTabsFromFirstSourceCodeLine = null;
			foreach($lines as $line){
				if (trim($line) !== ""){
					$currentTabsForDepth = $this->getTabs($tabDepth);
					$additionalTabs = "";
					preg_match("/^(\t+).*/", $line, $matches);

					if (isset($matches[1])){
						$existingTabs = $matches[1];
						if ($numBaseTabsFromFirstSourceCodeLine === null){
							$numBaseTabsFromFirstSourceCodeLine = strlen($existingTabs);
						}else{
							$additionalTabsNeeded = strlen($existingTabs) - $numBaseTabsFromFirstSourceCodeLine;
							if ($additionalTabsNeeded > 0){
								$additionalTabs = str_repeat($this->tabCharacter, $additionalTabsNeeded);
							}
						}
					}

					$this->cleanHTML .= sprintf(
						"%s%s%s\n",
						$currentTabsForDepth,
						$additionalTabs,
						trim($line),
					);
				}
			}
		}
	}
