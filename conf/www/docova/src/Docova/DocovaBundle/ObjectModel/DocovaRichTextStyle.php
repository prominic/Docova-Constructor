<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Represents a style object in a rich text item.
 * @author javad_rahimi
 */
class DocovaRichTextStyle 
{
	/**
	 * font-weight: bold
	 * @var boolean
	 */
	public $bold = false;

	/**
	 * font-style:italic
	 * @var boolean
	 */
	public $italic = false;

	/**
	 * text-decoration: underline
	 * @var boolean
	 */
	public $underline = false;

	/**
	 * font-family
	 * @var string
	 */
	public $font = 'Arial';

	/**
	 * color
	 * @var string
	 */
	public $color = 'black';

	/**
	 * font-size
	 * @var string
	 */
	public $fontSize = '10px';

	public function setBold($newval)
	{
		$this->bold = $newval === true ? true : false;
	}
	
	public function setItalic($newval)
	{
		$this->italic = $newval === true ? true : false;
	}
	
	public function setUnderline($newval)
	{
		$this->underline = $newval === true ? true : false;
	}
	
	public function setFont($newval)
	{
		$this->font = $newval;
	}
	
	public function setColor($newval)
	{
		$this->color = $newval;
	}
	
	public function setFontSize($newval)
	{
		$this->color = $newval;
	}
}