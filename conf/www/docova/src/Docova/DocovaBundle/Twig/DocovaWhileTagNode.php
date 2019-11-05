<?php

namespace Docova\DocovaBundle\Twig;

class DocovaWhileTagNode extends \Twig_Node
{
    private $id = "";
   
   
    public function __construct(\Twig_Node $body, $id, $ifexpr, $line, $tag = null)
    {
        $this->id = $id;

        parent::__construct(array('body' => $body, 'ifexpr' => $ifexpr), array(), $line, $tag);        

    }

    public function getFunctionID(){
        return $this->id;
    }
 

    
   public function compile(\Twig_Compiler $compiler)
   {
       $funcname = $this->id;
     // $compiler->addDebugInfo($this);
      $compiler
        ->raw("\n")
        ->write(sprintf('public function %s ( &$context ) {' , $funcname))
        ->raw("\n")
        ->indent()
        ->write(sprintf('while ( '))
        ->write("\n")
        ->subcompile($this->getNode('ifexpr'))
        ->raw(")")
        ->raw("\n")
        ->write("{")
        ->raw("\n")
        ->indent()
        ->subcompile($this->getNode('body'))
        ->outdent()
        ->write ('}')
        ->raw("\n")
        ->write("return true;")
        ->raw("\n")
        ->outdent()
        ->write ('}')
        ->raw("\n")
      ;

      
   }

}