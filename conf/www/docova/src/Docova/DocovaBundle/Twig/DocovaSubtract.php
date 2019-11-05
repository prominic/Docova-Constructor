<?php

namespace Docova\DocovaBundle\Twig;

/**
 * Class to handle subtraction same as Domino when left and/or right operands are array
 * @author javad_rahimi
 */
class DocovaSubtract extends \Twig_Node_Expression_Binary_Sub
{
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->raw(sprintf('$this->docova_subtraction('))
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