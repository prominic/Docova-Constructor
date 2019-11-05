<?php

namespace Docova\DocovaBundle\Twig;

/**
 * Class to override default twig comparison operator (smaller than) functionality
 * @author javad_rahimi
 */
class DocovaSmallerThan extends \Twig_Node_Expression_Binary {
	
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->raw(sprintf('$this->docova_smallerthan('))
			->subcompile($this->getNode('left'))
			->raw(sprintf(','))
			->subcompile($this->getNode('right'))
			->raw(sprintf(')'));
	}
	
	public function operator(\Twig_Compiler $compiler)
	{
		return $compiler->raw('');
	}
}