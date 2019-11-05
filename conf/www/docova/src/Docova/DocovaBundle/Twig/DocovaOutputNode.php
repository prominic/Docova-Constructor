<?php

namespace Docova\DocovaBundle\Twig;


class DocovaOutputNode extends \Twig_Node_Print
{
    public function __construct(\Twig_Node_Print $originalNode)
    {
      //parent::__construct(array('expr' => $orignode->expr, array(), $orignode->lineno, $orignode->tag);
      //$nd = $orignode->getNode('expr');
    //  parent::__construct(array('expr' => 'dummy', array(), 4, "");    
        \Twig_Node::__construct($originalNode->nodes, $originalNode->attributes, $originalNode->lineno, $originalNode->tag);
   
    }

    public function compile(\Twig_Compiler $compiler)
    {

          $compiler
            ->addDebugInfo($this)
           // ->write('$echo mytest() ')
            //->raw(";\n")
            ->write('return  ')
            ->subcompile($this->getNode('expr'))
            ->raw(";\n")
        ;
    }
}