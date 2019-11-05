<?php

namespace Docova\DocovaBundle\Twig;

class DocovaArrayAppend extends \Twig_Node_Expression_Binary
{
   public function compile(\Twig_Compiler $compiler)
  	{
  		
		//$left = $compiler->getVarName();
        //$right = $compiler->getVarName();

        $compiler
            ->raw(sprintf('$this->docova_array_append('))
            ->subcompile($this->getNode('left'))
            ->raw(sprintf(','))
            ->subcompile($this->getNode('right'))
            ->raw(sprintf(')'))
        ;
       

	}

	public function operator(\Twig_Compiler $compiler)
	{

    	return $compiler->raw('');
	}
}