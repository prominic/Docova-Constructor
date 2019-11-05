<?php

namespace Docova\DocovaBundle\Twig\Node;
//use Docova\DocovaBundle\Twig\DocovaDoTagNode;

class DocovaHandleReturnNode extends \Twig_Node
{
    protected $retvalue  = null;
    public $throwexception = true;
  
    public function __construct(\Twig_Node $originalNode, $retval = "", $throwexception = true)
    {
    
      $this->retvalue = $retval;
      $this->throwexception = $throwexception;
      //$tmpnode = clone $originalNode->nodes;
      \Twig_Node::__construct($originalNode->nodes, $originalNode->attributes, $originalNode->lineno, $originalNode->tag);
   
    }


    public function compile(\Twig_Compiler $compiler)
    {

        $throwexception = $this->throwexception;

        if ( $throwexception )
        {
          $compiler
            ->raw(sprintf( 'throw new Exception('))
            ->subcompile($this->retvalue)
            ->raw(");")
          ;
        }else{
           $compiler->subcompile($this->retvalue);
        }

    }
}