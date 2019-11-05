<?php

namespace Docova\DocovaBundle\Twig;

class DocovaForTagNode extends \Twig_Node
{
    private $id = "";
   
   
    public function __construct(\Twig_Node $body, $id, $init, $ifexpr, $increment,$line, $tag = null)
    {
        $this->id = $id;

        parent::__construct(array('body' => $body, 'init' => $init, 'ifexpr' => $ifexpr, 'increment' => $increment), array(), $line, $tag);        

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
        ->write(sprintf('for ( '))
        ->write("\n")
        ->subcompile($this->getNode('init'))
        ->raw(";")
        ->write("\n")
        ->subcompile($this->getNode('ifexpr'))
        ->raw(";")
        ->write("\n")
        ->subcompile($this->getNode('increment'))
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