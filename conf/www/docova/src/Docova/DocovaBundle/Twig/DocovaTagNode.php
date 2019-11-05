<?php

namespace Docova\DocovaBundle\Twig;

class DocovaTagNode extends \Twig_Node
{
    public $mode = "output";
    public $expect = "string";
    
    public function __construct(\Twig_Node $body, $values,$line, $tag = null)
    {
       
        if ($values){
            $strArray = explode(":", $values);
            if ( count($strArray) == 2 )
            {
                $this->mode = $strArray[0];
                $this->expect = $strArray[1];
            }
        }

        parent::__construct(array('body' => $body), array(), $line, $tag);        

    }

    
   public function compile(\Twig_Compiler $compiler)
   {
      // $funcname = $compiler->getVarName();
      $funcname = $this->getAttribute("key");
     // $compiler->addDebugInfo($this);
      $compiler
        ->raw("\n")
        ->write(sprintf('public function %s ( $context ) {' , $funcname))
        ->raw("\n")
        ->indent()
        ->write ('if (isset($_SESSION["__dexpreres"]))')
        ->raw("\n")
        ->indent()
        ->write ('unset($_SESSION["__dexpreres"])')
        ->raw(";\n")
        ->outdent()
        ->write ('if (isset($_SESSION["__dexpreresraw"]))')
        ->raw("\n")
        ->indent()
        ->write ('unset($_SESSION["__dexpreresraw"])')
        ->raw(";\n")
        ->raw("\n")
        ->subcompile($this->getNode('body'))
        ->outdent()
        ->write ('}')
        ->raw("\n")
      ;

      

      
   }

}