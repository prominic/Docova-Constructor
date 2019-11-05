<?php

namespace Docova\DocovaBundle\Twig;

/**
 * Redifine multiply operand for migrated apps to handle multiplication same as how Domino handles it
 * @author javad_rahimi
 */
//class DocovaMultiply extends \Twig_Node_Expression_Binary
class DocovaMultiply extends \Twig_Node_Expression_Binary_Mul
{
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->raw(sprintf('$this->docova_multiply('))
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