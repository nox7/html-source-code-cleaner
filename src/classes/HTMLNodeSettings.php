<?php
	class HTMLNodeSettings{

		/**
		* By default, everything not listed in below arrays
		* is treated as a block. So this doesn't need explicit
		* definitions.
		*/
		public $blockElements = [

		];

		/**
		* Inline elements that are not self-closing
		*/
		public $inlineElements = [
			"a","em","b","i","strong",
		];

		/**
		* Self closing elements that you'd prefer be on their own
		* line (such as <img>, or <link>)
		*/
		public $isolatedSelfClosingElements = [
			"link","img","meta","input",
		];

		/**
		* Self closing elements that you don't mind being on the same lines
		* as textual values. However, if there are no textual values,
		* they will be considered isolated. (Think <a> elements when they are note
		* inlined with text, such as a navigation bar.)
		*/
		public $inlineSelfClosingElements = [
			"br",
		];

		/**
		* The maximum amount of characters a block element (with only text node children)
		* can have in it before it becomes a tabbed newline of text
		*/
		public $maxTextualBlockElementCharacters = 60;

		/**
		* TODO: Allow this to be merged with provided arguments
		*/
		public function __construct(){

		}

		public function isBlockElement(string $nodeName): bool{
			if (in_array($nodeName, $this->isolatedSelfClosingElements)){
				return false;
			}

			if (in_array($nodeName, $this->inlineElements)){
				return false;
			}

			if (in_array($nodeName, $this->inlineSelfClosingElements)){
				return false;
			}

			return true;
		}

		public function isInlineElement(string $nodeName): bool{
			if (in_array($nodeName, $this->inlineElements)){
				return true;
			}

			return false;
		}

		public function isIsolatedSelfClosingElement(string $nodeName): bool{
			if (in_array($nodeName, $this->isolatedSelfClosingElements)){
				return true;
			}

			return false;
		}

		public function isInlineSelfClosingElement(string $nodeName): bool{
			if (in_array($nodeName, $this->inlineSelfClosingElements)){
				return true;
			}

			return false;
		}
	}
