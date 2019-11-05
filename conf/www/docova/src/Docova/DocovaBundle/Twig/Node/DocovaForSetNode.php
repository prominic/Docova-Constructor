<?php

namespace Docova\DocovaBundle\Twig\Node;


class DocovaForSetNode extends \Twig_Node
{
   protected $functionname;
   
    public function __construct(\Twig_Node $originalNode)
    {
        

        \Twig_Node::__construct($originalNode->nodes, $originalNode->attributes, $originalNode->lineno, $originalNode->tag);
   
    }

    public function compile(\Twig_Compiler $compiler)
    {

        $compiler->subcompile($this->getNode('names'), false);
        $compiler->raw(' = ');
        $compiler->subcompile($this->getNode('values'));
    }
}