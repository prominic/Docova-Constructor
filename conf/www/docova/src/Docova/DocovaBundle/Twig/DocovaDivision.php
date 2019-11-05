<?php

namespace Docova\DocovaBundle\Twig;

/**
 * Redifine division operand for migrated apps to handle division same as how Domino handles it
 * @author javad_rahimi
 */
class DocovaDivision extends \Twig_Node_Expression_Binary_Div
{
	public function compile(\Twig_Compiler $compiler)
	{
		$compiler
			->raw(sprintf('$this->docova_division('))
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