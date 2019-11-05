<?php

namespace Docova\DocovaBundle\Twig;

class DocovaIfTagNode extends \Twig_Node
{
   protected $id ;
   public $expr2hasreturn = false;
   public $expr3hasreturn = false;

   public function __construct($id,  $exprArray, $lineno)
    {
        $this->id = $id;
        parent::__construct(array('exprArray' => new \Twig_Node($exprArray)), array(), $lineno);
    }

    public function getFunctionID(){
        return $this->id;
    }

    public function compile(\Twig_Compiler $compiler)
    {
       
        $exprarr = $this->getNode('exprArray');
        $compiler
            ->raw("\n")
            ->write(sprintf('public function %s ( &$context )' , $this->id))
            ->write("\n")
            ->write("{")
            ->raw("\n")
        ;
        $total = count($exprarr);
        $ifcond = true;
        for ( $x = 0; $x < $total-1; $x++){
            if ($ifcond )
            {
                //odd
                $compiler->indent();
                if ( $x == 0)
                     $compiler->write('if (');
                else
                     $compiler->raw ('else if (');
                $compiler
                    ->subcompile($exprarr->getNode($x))
                    ->raw(')')
                    ->raw("\n")
                    ->write('{')
                    ->write("\n")
                    ->indent()
                ;
                $ifcond = false;
            }else{
                 $compiler
                    ->write("return ")
                    ->subcompile($exprarr->getNode($x))
                    ->raw(";")
                    ->write("\n")
                    ->outdent()
                    ->write("}")
                ;
                $ifcond = true;
            }
        }

        //last one is else part
        $compiler
            ->raw("else")
            ->raw('{')
            ->write("\n")
            ->indent()
            ->write("return ")
            ->subcompile($exprarr->getNode($total-1))
            ->raw(";")
            ->write("\n")
            ->outdent()
            ->write("}")
        ;
        

        /*
        if ( ! $this->expr2hasreturn ) 
            $compiler->write("return ");
        
        $compiler->subcompile($this->getNode('expr2'));

        if ( ! $this->expr2hasreturn ) 
            $compiler->raw(";");

        $compiler
            ->write("\n")
            ->outdent()
            ->write('} else {')
            ->write("\n")
            ->indent()
        ;
        if (  ! $this->expr3hasreturn ) 
            $compiler->write("return ");
        
        $compiler->subcompile($this->getNode('expr3'));

        if (  ! $this->expr3hasreturn ) 
            $compiler->raw(";");
        */
        $compiler
            //->write("\n")
           // ->outdent()
           // ->write('}')
            ->write("\n")
            ->outdent()
            ->write ('}')
        ;
    }

}