<?php

namespace Docova\DocovaBundle\Twig;

class DocovaConcat extends \Twig_Node_Expression_Binary
{
   public function compile(\Twig_Compiler $compiler)
  	{
  		
		//$left = $compiler->getVarName();
        //$right = $compiler->getVarName();

        $compiler
            ->raw(sprintf('$this->docova_concat('))
            ->subcompile($this->getNode('left'))
            ->raw(sprintf(','))
            ->subcompile($this->getNode('right'))
            ->raw(sprintf(')'))
        ;
       /*
        $compiler
        	->raw(sprintf('( !empty($%s = ' , $left))
        	->subcompile($this->getNode('left'))
        	->raw(sprintf(') && !empty($%s = ', $right))
        	->subcompile($this->getNode('right'))
        	->raw(sprintf('))'))
        	->raw(sprintf('?'))
        	->raw(sprintf('(is_string($%1$s) && is_string($%2$s))', $left, $right))
        	->raw(sprintf('?'))
        	->raw(sprintf('($%1$s.$%2$s)', $left, $right))
        	->raw(sprintf(': ( (is_numeric($%1$s) && is_numeric($%2$s) ? $%1$s + $%2$s ', $left, $right))
        	->raw(sprintf(': ( is_array($%1$s) && is_array($%2$s) ? (array_map(function($a, $b) { return $a . $b; }, $%1$s, $%2$s)) ', $left, $right))
        	->raw(sprintf(': ( is_string($%1$s) && is_array($%2$s)? (array_map(function($a, $b) { return $a . $b; },  array_fill(0, count($%2$s),$%1$s), $%2$s))', $left, $right))
        	->raw(sprintf(': ( is_array($%1$s) && is_string($%2$s) ? (array_map(function($a, $b) { return $a . $b; }, $%1$s, array_fill(0, count($%1$s),$%2$s)) ): "")))))', $left, $right))
        	->raw(sprintf(': ("")'));
        */
    

	}

	public function operator(\Twig_Compiler $compiler)
	{

    	return $compiler->raw('');
	}
}