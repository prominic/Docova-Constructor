<?php

namespace Docova\DocovaBundle\Twig;

class DocovaTagDefNode extends \Twig_Node
{
    private $mode = "output";
    private $expect = "string";
    private $id = "";
    public function __construct(\Twig_Node $body, $id,  $mode, $expect)
    {
       
       $this->mode = $mode;
       $this->expect = $expect;
       $this->id = $id;
        parent::__construct(array('body' => $body), array());      

    }

    
   public function compile(\Twig_Compiler $compiler)
   {

     $funcname = $this->id;
     $compiler
        ->write("\n")
        ->write("try {")
        ->write("\n")
        ->indent()
        ->write(sprintf( '$retval = $this->%s($context);', $funcname))
        ->write("\n")
        ->outdent()
        ->write('}  catch (Exception $e) { ')
        ->write("\n")
        ->indent()
        ->write (' $retval = $e->getMessage() ;')
        ->write ("\n")
        ->outdent()
        ->write('}')
        ->write ("\n")
        ->write ( '$context["__dexpreresraw"] = $retval ; ')
        ->raw("\n")
     ;

     if ( $this->mode == "raw" )
      return;

     if ( $this->mode == "output")
     {
         if ( $this->expect == "string"){
           $compiler
           		->write('if (is_array($retval)) {')
           		->raw("\n")
           		->indent()
           		->write('$temp_val = "";')
           		->raw("\n")
           		->write('for ($x = 0; $x < count($retval); $x++) {')
           		->raw("\n")
           		->indent()
           		->write('$temp_val .= ((!empty($retval[$x]) && ($retval[$x] instanceof DateTime)) ? $retval[$x]->format(\'d-m-Y H:i:s\') : strval($retval[$x])) . \',\';')
           		->raw("\n")
           		->outdent()
           		->write('}')
           		->raw("\n")
           		->write('$retval = substr_replace($temp_val, \'\', -1);')
           		->raw("\n")
           		->outdent()
           		->write('} else {')
           		->raw("\n")
           		->indent()
                ->write( '$retval = (!empty($retval) && ($retval instanceof DateTime)) ? $retval->format(\'d-m-Y H:i:s\') : strval($retval);')
                ->raw("\n")
                ->outdent()
                ->write('}')
          	;
          }else if ( $this->expect == "bool"){
            $compiler
              ->write('$retval = (is_bool($retval) && ! $retval ) ? "0" : $retval);')
              ->raw("\n")
            ;
          }else if ( $this->expect == "array "){
             $compiler
                ->write('$retval = (is_array($retval) ) ? implode(",", $retval) : (is_bool($retval) && !$retval ? "0" : $retval );')
                ->raw("\n")
            ;
          }else if ( $this->expect == "json"){
              $compiler
              ->write('$retval = json_encode($retval);')
              ->raw("\n")
              ;
          }
          $compiler
            ->write('echo  $retval;')
            ->raw("\n")
          ;
     }
     elseif ($this->mode == 'variable') {
     	$compiler
     		->write('if (is_array($retval)) {')
     		->raw("\n")
     		->indent()
     		->write('for ($x = 0; $x < count($retval); $x++) {')
     		->raw("\n")
     		->indent()
     		->write('$retval[$x] = ((!empty($retval[$x]) && ($retval[$x] instanceof DateTime)) ? serialize($retval[$x]) : strval($retval[$x]));')
     		->raw("\n")
     		->outdent()
     		->write('}')
     		->raw("\n")
     		->outdent()
     		->write('} else {')
     		->raw("\n")
     		->indent()
     		->write('$retval = (!empty($retval) && ($retval instanceof DateTime)) ? serialize($retval) : strval($retval);')
     		->raw("\n")
     		->outdent()
     		->write('}')
     		->raw("\n")
     	;
     	if ($this->expect == 'array') {
     		$compiler
     		 	->write('$retval = \'DOCOVA_ARR::\'. (is_array($retval) ? implode(\'#~#\', $retval) : $retval);')
     		 	->raw("\n");
     	}
     	else {
     		$compiler
     		 ->write('$retval = \'DOCOVA_VAR::\'. (is_array($retval) ? implode(",", $retval) : $retval);')
     		 ->raw("\n");
     	}
        $compiler
           ->write('echo $retval;')
           ->raw("\n");
     }
     else{
        if ( $this->expect == "array"){
           $compiler
            ->write('$retval = (! is_array($retval) ) ? $this->docova_get_array($retval) : $retval;')
            ->raw("\n")
          ;
        }else if ($this->expect == "string" ){
            $compiler
            ->write('$retval = ( is_array($retval) ) ? implode(",", $retval) : $retval;')
            ->raw("\n")
          ;
        }
        $compiler
            ->write ( '$context["__dexpreres"] = $retval ')
            ->raw(";\n")
        ;
      }

      
   }

}