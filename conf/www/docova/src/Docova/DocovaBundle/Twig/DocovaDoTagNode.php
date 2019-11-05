<?php

namespace Docova\DocovaBundle\Twig;

class DocovaDoTagNode extends \Twig_Node
{
    private $id = "";
   
    public function __construct(\Twig_Node $body, $values,$line, $tag = null)
    {
       
        if ($values){
           $this->id = $values;
              
        }

        parent::__construct(array('body' => $body), array(), $line, $tag);        

    }

  public function getFunctionID(){
      return $this->id;
  }

 

    
   public function compile(\Twig_Compiler $compiler)
   {

     // $funcname = $compiler->getVarName();
      $funcname = $this->id;
     // $compiler->addDebugInfo($this);
      $compiler
        ->raw("\n")
        ->write(sprintf('public function %s ( &$context ) {' , $funcname))
        ->raw("\n")
        ->indent()
        ->subcompile($this->getNode('body'))
        ->outdent()
        ->write ('}')
        ->raw("\n")
      ;
    }
    
    /*  //$funcname = $compiler->getVarName();
     $funcname = "__Func_".$this->id;
     
     // $compiler->addDebugInfo($this);
      $compiler
        ->raw(sprintf('    $%s = function() use ( &$context ) {' , $funcname))
        ->raw("\n")
        ->indent()
        ->subcompile($this->getNode('body'))
        ->outdent()
        ->write ('}')
        ->raw(";\n")
      ;

      

      $compiler
        ->write ('if (isset($_SESSION["__dexpreres"]))')
        ->raw("\n")
        ->indent()
        ->write ('unset($_SESSION["__dexpreres"])')
        ->raw(";\n")
        ->outdent()
      ;

      
   }*/

}