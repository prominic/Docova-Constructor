<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Represents rich text paragraph attributes.
 * @author javad_rahimi
 */
class DocovaRichTextParagraphStyle 
{
	/**
	 * text-align
	 * @var string
	 */
	public $alignment = 'left';

	/**
	 * /line-height
	 * @var string
	 */
	public $interLineSpacing = '100%';

	/**
	 * text indent
	 * @var string
	 */
	public $firstLineLeftMargin = '0px';

	/**
	 * left padding
	 * @var string
	 */
	public $leftMargin = '0px';

	/**
	 * page-break
	 * @var integer
	 */
	public $pagination = 0;

	/**
	 * width
	 * @var string
	 */
	public $rightMargin = '100%';

	/**
	 * top padding
	 * @var string
	 */
	public $spacingAbove = '0px';

	/**
	 * bottom padding
	 * @var string
	 */
	public $spacingBelow = '0px';
	
	public function setAlignment($newval)
	{
		$this->alignment = $newval;
	}
	
	public function setInterLineSpacing($newval)
	{
		$this->interLineSpacing = $newval;
	}
	
	public function setFirstLineLeftMargin($newval)
	{
		$this->firstLineLeftMargin = $newval;
	}
	
	public function setLfetMargin($newval)
	{
		$this->leftMargin = $newval;
	}
	
	public function setPagination($newval)
	{
		$this->pagination = $newval;
	}
	
	public function setRightMargin($newval)
	{
		$this->rightMargin = $newval;
	}
	
	public function setSpacingAbove($newval)
	{
		$this->spacingAbove = $newval;
	}
	
	public function setSpacingBelow($newval)
	{
		$this->spacingBelow = $newval;
	}
}