<?php

namespace Docova\DocovaBundle\Twig;

/**
 * Class to override default twig comparison operator (greater equal than) functionality
 * @author javad_rahimi
 */
class DocovaGreaterEqual extends \Twig_Node_Expression_Binary {
	
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->raw(sprintf('$this->docova_greaterequal('))
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