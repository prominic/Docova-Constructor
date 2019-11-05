<?php

namespace Docova\DocovaBundle\Twig\Node;


class DocovaWhileNode extends \Twig_Node
{
    protected $functionname;
  
    public function __construct(\Twig_Node $originalNode, $funcname)
    {
    

        $this->functionname = $funcname;
       
        \Twig_Node::__construct($originalNode->nodes, $originalNode->attributes, $originalNode->lineno, $originalNode->tag);
   
    }

    public function getFunctionName(){
        return $this->functionname;
    }

    public function compile(\Twig_Compiler $compiler)
    {
         $semicolon = false;
         if ( $this->hasAttribute("usesemicolon"))
            $semicolon = $this->getAttribute("usesemicolon");

     
         
        if ( $semicolon){
            $compiler->write(sprintf( '$this->%1$s($context)', $this->functionname ));
            $compiler->raw(";");
        }else
            $compiler->raw(sprintf( ' $this->%1$s($context)', $this->functionname ));

    }
}