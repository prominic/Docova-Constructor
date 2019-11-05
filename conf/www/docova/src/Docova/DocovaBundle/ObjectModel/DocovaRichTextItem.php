<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Back end class and methods represents an item of type rich text.
 * @author javad_rahimi
 */
class DocovaRichTextItem extends DocovaField
{
	private $_docova;
	private $domObject = null;
	private $currentStyle = null;
	private $insertAtElem = null;
	private $lastPragraph = null;
	private $insertAtEnd = false;
	private $prepend = false;
	private $insertArray = array();
	private $currentParagraphStyle = null;
	public $navigator;
	
	public function __construct(DocovaDocument $parentDoc, $fieldname, DocovaField $objField = null, Docova $docova_obj)
	{
		if (!empty($docova_obj)) {
			$this->_docova = $docova_obj;
		}
		else {
			global $docova;
			if (!empty($docova) && $docova instanceof Docova) {
				$this->_docova = $docova;
			}
			else {
				throw new \Exception('Oops! DocovaRichTextItem construction failed. Docova service not available.');
			}
		}
		
		if (empty($objField)) {
			parent::__construct($parentDoc, $fieldname, '', null, 0, $this->_docova);
		}
		else {
			parent::__construct($parentDoc, $fieldname, $objField->value, null, 0, $this->_docova);
		}
		
		$this->domObject = new \DOMDocument();
		if (!empty($this->value)) {
			$tmp = new \DOMDocument();
			@$tmp->loadHTML($this->value);
			if (!$tmp->getElementById('rtcontainer')) {
				@$this->domObject->loadHTML('<div id="rtcontainer">'.$this->value.'</div>');
			}
			else {
				@$this->domObject->loadHTML($this->value);
			}
		}
		else {
			@$this->domObject->loadHTML('<div id="rtcontainer"></div>');
		}
		
		$p = $this->domObject->getElementById('dparagraph');
		if (!empty($p)) {
			$this->lastPragraph = $p;
		}
	}
	
	/**
	 * Inserts one or more new lines (carriage returns) in a rich text item.
	 * 
	 * @param integer $count
	 */
	public function addNewLine($count)
	{
		if (empty($count))
			$count = 1;

		$html = '';
		$start = 1;
		
		if (!empty($this->insertAtElem) && strtolower($this->insertAtElem->nodeName) == 'td') {
			$start = 0;
		}
		for ($x = $start; $x < $count; $x++)
			$html .= '<br />';
		
		if (!empty($this->insertAtElem)) {
			$this->insertArray[] = $html;
		}
		else {
			$tmp_dom = new \DOMDocument();
			@$tmp_dom->loadHTML("<div>$html</div>");
			$content = $tmp_dom->getElementsByTagName('div')->item(0);
			$newnode = $this->domObject->importNode($content, true);
			$node = $this->domObject->getElementById('rtcontainer');
			$node->appendChild($newnode);
			$tmp_dom = null;
		}
		$p = $this->domObject->getElementById('dparagraph');
		if (!empty($p))
		{
			$p->removeAttribute('id');
		}
		$this->lastPragraph = null;
		$this->value = $this->domObject->saveHTML();
	}
	
	/**
	 * Inserts text in a rich text item.
	 * 
	 * @param string $intext
	 */
	public function appendText($intext)
	{
		$text = $this->styleText('span', $intext);
		
		if (!empty($this->insertAtElem))
		{
			$this->insertArray[] = $text;
		}
		else {
			if (empty($this->lastPragraph))
				$text = '<p id="dparagraph" style="margin:0">'.$text.'</p>';

			$tmp_dom = new \DOMDocument();
			@$tmp_dom->loadHTML($text);
			if (!empty($this->lastPragraph)) {
				$content = $tmp_dom->getElementsByTagName('span')->item(0);
				$target = $this->domObject->getElementById($this->lastPragraph->getAttribute('id'));
			}
			else {
				$content = $tmp_dom->getElementById('dparagraph');
				$target = $this->domObject->getElementById('rtcontainer');
				$this->lastPragraph = $content;
			}
			$newnode = $this->domObject->importNode($content, true);
			$target->appendChild($newnode);
			$tmp_dom = null;
		}
		$this->value = $this->domObject->saveHTML();
	}
	
	/**
	 * Appends a DocovaRichTextStyle to the rich text item
	 * 
	 * @param DocovaRichTextStyle $style
	 */
	public function appendStyle(DocovaRichTextStyle $style)
	{
		if (!empty($style))
		{
			$this->currentStyle = $style;
		}
	}
	
	/**
	 * Appends a DocovaRichTextParagraphStyle object to rich text item
	 * 
	 * @param DocovaRichTextParagraphStyle $style
	 */
	public function appendParagraphStyle(DocovaRichTextParagraphStyle $style)
	{
		if(!empty($style))
		{
			$this->currentParagraphStyle = $style;
		}
	}
	
	/**
	 * Changes the insertion position from the end of the rich text item to the beginning or end of a specified element.
	 * 
	 * @param DocovaRichTextNavigator $element
	 * @param string $after
	 */
	public function beginInsert($element, $after = false)
	{
		if (empty($element))
			return;
		
		if ($element instanceof DocovaRichTextNavigator) 
		{
			$this->insertAtElem = $element->getElement();
			if ($after === true)
			{
				$this->insertAtEnd = true;
			}
		}
	}
	
	/**
	 * Resets the insertion position to the end of the rich text item. Must be paired with BeginInsert.
	 */
	public function endInsert()
	{
		if (count($this->insertArray))
		{
			if (!empty($this->insertAtElem))
			{
				$html = implode('', $this->insertArray);
				$tmp_dom = new \DOMDocument();
				@$tmp_dom->loadHTML("<div>$html</div>");
				$content = $tmp_dom->getElementsByTagName('div')->item(0);
				$newnode = $this->domObject->importNode($content, true);
				$node = $this->domObject->getElementById($this->insertAtElem);
				if ($this->insertAtEnd !== false) {
					$node->appendChild($newnode);
				}
				else {
					$node->parentNode->insertBefore($newnode, $node);
				}
			}
		}

		$this->insertArray = array();
		$this->insertAtElem = null;
		$this->insertAtEnd = false;
	}
	
	/**
	 * Appends the HTML to the rich text item
	 * 
	 * @param string $inhtml
	 * @param string $selector
	 */
	public function appendHTML($inhtml, $selector = '')
	{
		if (empty($inhtml))
			return;
		
		if (!empty($this->insertAtElem))
		{
			$this->insertArray[] = $inhtml; 
		}
		else {
			$root = $this->domObject->getElementById('rtcontainer');
			$dom = new \DOMDocument();
			if (empty($selector)) {
				@$dom->loadHTML('<div>'.$inhtml.'</div>');
				$node = $dom->getElementsByTagName('div')->item(0);
			}
			else {
				@$dom->loadHTML($inhtml);
				$node = $dom->getElementsByTagName($selector)->item(0);
			}
			$newnode = $this->domObject->importNode($node, true);
			$root->appendChild($newnode);
		}
		$this->value = $this->domObject->saveHTML();
	}
	
	/**
	 * Appends a richtext items contents to another rich text item
	 * 
	 * @param DocovaRichTextItem $rtitem
	 */
	public function appendRTItem(DocovaRichTextItem $rtitem)
	{
		$html = $rtitem->value;
		if (empty($html))
			return;
		
		$this->appendHTML($html);
	}
	
	/**
	 * Appends a table to the rich text item
	 * 
	 * @param integer $rows
	 * @param integer $columns
	 * @param string $labels
	 * @param integer $leftmargin
	 * @param DocovaRichTextParagraphStyle[] $rtpsStyleArray
	 */
	public function appendTable($rows, $columns, $labels, $leftmargin, $rtpsStyleArray = array())
	{
		if (!empty($rtpsStyleArray) && $columns != count($rtpsStyleArray))
			return;
		
		$tablestr = '<table style="margin-left:'.$leftmargin.'; border-spacing: 0px; border-collapse: collapse;">';
		for ($r =0; $r < $rows; $r++) {
			$tablestr .= '<tr>';
			for ($c = 0; $c < $columns; $c++) {
				if (!empty($rtpsStyleArray[$c])) {
					$pstyle = $rtpsStyleArray[$c];
					$tablestr .= $this->styleText('TD', '', $this->currentStyle, $pstyle);
				}else{
					$tablestr .= $this->styleText('TD', '');
				}
				
			}
			$tablestr .= '</tr>';
		}
		$tablestr .= '</table>';
		
		$this->appendHTML($tablestr, 'table');
	}
	
	/**
	 * Appends a doclink to a richtext item
	 * 
	 * @param DocovaDocument $linkto
	 * @param string $comment
	 * @param string $hotspottext
	 */
	public function appendDocLink($linkto, $comment = '', $hotspottext = '')
	{
		if ($linkto instanceof DocovaDocument)
		{
			$linktext = '<a target = "_self" href="';
			$linktext .= $linkto->getURL(['fullurl'=>true]);
			$linktext .= '" title="' . ($comment ? htmlentities($comment) : '') . '">'. ($hotspottext ? htmlentities($hotspottext) : 'link') .'</a>';
			
			if (!empty($this->insertAtElem)) {
				$html = $linktext;
				$this->appendHTML($html);
			}
			else {
				if (!empty($this->lastPragraph))
				{
					$html = $linktext;
					$tmp_dom = new \DOMDocument();
					@$tmp_dom->loadHTML($html);
					$pgraph = $tmp_dom->getElementsByTagName('a')->item(0);
					$newnode = $this->domObject->importNode($pgraph, true);
					$target = $this->domObject->getElementById($this->lastPragraph->getAttribute('id'));
					$target->appendChild($newnode);
					
					$this->value = $this->domObject->saveHTML();
				}
				else {
					$html = '<p id="" style="margin:0;">'.$linktext.'</p>';
					$tmp_dom = new \DOMDocument();
					@$tmp_dom->loadHTML($html);
					$this->lastPragraph = $tmp_dom->getElementById('dparagraph');
					$tmp_dom = null;
					$this->appendHTML($html, 'p');
				}
			}
		}
	}
	
	/**
	 * Creates a NotesRichTextNavigator object.
	 * 
	 * @return DocovaRichTextNavigator
	 */
	public function createNavigator()
	{
		$navigator = new DocovaRichTextNavigator($this->domObject);

		return $navigator;
	}
	
	/**
	 * Creates a DocovaRichTextRange object.
	 * 
	 * @return \Docova\DocovaBundle\ObjectModel\DocovaRichTextRange
	 */
	public function createRange()
	{
		$range = new DocovaRichTextRange($this->domObject);
		
		return $range;
	}
	
	/**
	 * Returns text from a rich text item.
	 * 
	 * @return string
	 */
	public function getUnformattedText()
	{
		return $this->domObject->saveHTML();
	}
	
	/**
	 * Styles the incoming data based on the style and paragraph styles
	 * 
	 * @param string $elem
	 * @param string $inputtext
	 * @param DocovaRichTextStyle $style
	 * @param DocovaRichTextParagraphStyle $paraStyle
	 * @return string
	 */
	private function styleText($elem, $inputtext, DocovaRichTextStyle $style = null, DocovaRichTextParagraphStyle $paraStyle = null)
	{
		$fstyle = !empty($style) ? $style : $this->currentStyle;
		$pstyle = !empty($paraStyle) ? $paraStyle : $this->currentParagraphStyle;
		$html = "<$elem style='";
		if (!empty($fstyle))
		{
			$html .= "font:{$fstyle->font}; ";
			$html .= "color:{$fstyle->color}; ";
			$html .= 'font-weight:'.($fstyle->bold === true ? 'bold' : 'normal').'; ';
			$html .= 'text-decoration:'.($fstyle->underline === true ? 'underline' : 'none').'; ';
			$html .= "font-size:{$fstyle->fontSize}; ";
		}
		
		if (!empty($pstyle))
		{
			$html .= "text-align:{$pstyle->alignment}; ";
			$html .= "text-indent:{$pstyle->firstLineLeftMargin}; ";
			$html .= "line-height:{$pstyle->interLineSpacing}; ";
			$html .= "margin-left:{$pstyle->leftMargin}; ";
			$html .= "width:{$pstyle->rightMargin}; ";
			$html .= "padding-top:{$pstyle->spacingAbove}; ";
			$html .= "padding-bottom:{$pstyle->spacingBelow}; ";
			if (empty($pstyle->pagination)) {
				$html .= 'page-break-inside:auto; ';
			}
			elseif ($pstyle->pagination == 1) {
				$html .= 'page-break-before:always; ';
			}
			elseif ($pstyle->pagination == 2) {
				$html .= 'page-break-after:always; ';
			}
		}
		
		$html .= "'> $inputtext </$elem>";
		return $html;
	}
}