<?php

namespace Docova\DocovaBundle\Twig\Node;


class DocovaModuleNode extends \Twig_Node
{
    public function __construct(\Twig_Node_Module $originalNode)
    {
        \Twig_Node::__construct($originalNode->nodes, $originalNode->attributes, $originalNode->lineno, $originalNode->tag);
    }

    public function compile(\Twig_Compiler $compiler)
	{
		$this->compileAppendMethod($compiler);
	}

    private function compileAppendMethod(\Twig_Compiler $compiler)
    {
        $nodearr = $this->getAttribute("docovascriptblocks");
        foreach (  $nodearr as $tmpobj){
              $compiler->write ("\n");
              $compiler->subcompile( $tmpobj, false);
              $compiler->write ("\n");
        }

        $doarr = $this->getAttribute("docovadoblocks");
        foreach ( $doarr as $tmpobj){
             $compiler->write ("\n");
             $compiler->subcompile( $tmpobj, false);
             $compiler->write ("\n");
        }

        $ifarr = $this->getAttribute("docovaifblocks");
        foreach ( $ifarr as $tmpobj){
             $compiler->write ("\n");
             $compiler->subcompile( $tmpobj, false);
             $compiler->write ("\n");
        }

        $forarr = $this->getAttribute("docovaforblocks");
        foreach ( $forarr as $tmpobj){
             $compiler->write ("\n");
             $compiler->subcompile( $tmpobj, false);
             $compiler->write ("\n");
        }
        
        $whilearr = $this->getAttribute("docovawhileblocks");
        foreach ( $whilearr as $tmpobj){
        	$compiler->write ("\n");
        	$compiler->subcompile( $tmpobj, false);
        	$compiler->write ("\n");
        }        
        
        $dowhilearr = $this->getAttribute("docovadowhileblocks");
        foreach ( $dowhilearr as $tmpobj){
        	$compiler->write ("\n");
        	$compiler->subcompile( $tmpobj, false);
        	$compiler->write ("\n");
        }        

  
		//declare docova_concat function
        $compiler
        ->write("\n", 'function docova_concat($left,$right) {',"\n")
        ->indent()
        ->write('if ( is_null($left) && !is_null($right)) {', "\n" )
        ->indent()
        ->write('return $right;', "\n" )
        ->outdent()
        ->write('} elseif ( !is_null($left) && is_null($right)) {', "\n" )
        ->indent()
        ->write('return $left;', "\n" )
        ->outdent()
        ->write('} elseif ( gettype($left) == "string" && gettype($right) == "string") {', "\n" )
        ->indent()
        ->write('return $left . $right;', "\n" )
        ->outdent()
        ->write('} elseif ( (gettype($left)=="integer" || gettype($left) == "double") && (gettype($right)=="integer" || gettype($right) == "double")) {', "\n" )
        ->indent()
        ->write('return $left+$right;', "\n" )
        ->outdent()        
        ->write('}', "\n")
        ->write('if ( ! is_array($left)) {', "\n" )
        ->indent()
        ->write('$left = [$left];', "\n" )
        ->outdent()
        ->write('}', "\n")
        ->write('if ( ! is_array($right)) {', "\n")
        ->indent()
        ->write('$right = [$right];', "\n" )
        ->outdent()
        ->write('}', "\n")
        ->write('$lcount = count($left);', "\n" )
        ->write('$rcount = count($right);', "\n" )
        ->write('if ( $lcount > $rcount ) {', "\n" )
        ->indent()
        ->write('$last = end($right);', "\n" )
        ->write('for ( $t =0; $t< $lcount-$rcount; $t++) {', "\n" )
        ->indent()
        ->write('array_push($right, $last);', "\n" )
        ->outdent()
        ->write('}', "\n")
        ->outdent()
        ->write('} elseif ( $rcount > $lcount ) {', "\n" )
        ->indent()
        ->write('$last = end($left);', "\n" )
        ->write('for ( $t =0; $t< $rcount-$lcount; $t++) {', "\n" )
        ->indent()
        ->write('array_push($left, $last);', "\n" )
        ->outdent()
        ->write('}', "\n")
        ->outdent()
        ->write('} ', "\n" )
        ->write('$retarr =  array_map(function($a, $b)', "\n" )
        ->write('{ ', "\n" )
        ->indent()
        ->write('if ( (gettype($a)=="integer" || gettype($a) == "double") && (gettype($b)=="integer" || gettype($b) == "double")) {', "\n" )
        ->indent()
        ->write('return $a + $b; ', "\n" )
        ->outdent()
        ->write('} elseif ( gettype($a) == "string" && gettype($b) == "string") {', "\n" )
        ->indent()
        ->write('return $a . $b; ', "\n" )
        ->outdent()
        ->write('} else {', "\n" )
        ->indent()
        ->write('return null;', "\n" )
        ->outdent()
        ->write('}', "\n")
        ->write('}, $left,$right ', "\n" )
        ->outdent()
        ->write(');    ', "\n" )
        ->write('if ( count($retarr) == 0 ) {', "\n" )
        ->indent()
        ->write('return "";', "\n" )
        ->outdent()
        ->write('} elseif (is_null($retarr[0])) {', "\n" )
        ->indent()
        ->write('return "";', "\n" )
        ->outdent()
        ->write('} else {', "\n" )
        ->indent()
        ->write('return $retarr;', "\n" )
        ->outdent()
        ->write('}', "\n");
        
        $compiler
        ->outdent()
        ->write("}\n");
        //-- end of docova_concat function
            
		//declare docova_get_array function
        $compiler
            ->write("\n", 'function docova_get_array($instring){', "\n")
            ->indent()
            ->write('if ( strstr( $instring, ";"))', "\n")
            ->indent()
            ->write(' return explode(";", $instring);', "\n")

            ->write('if ( strstr( $instring, ","))', "\n")
            ->indent()
            ->write(' return explode(",", $instring);', "\n")
            ->outdent()
            ->write ('return [$instring];', "\n");

         $compiler
            ->outdent()
            ->write("}\n");
        //-- end of docova_get_array function
			
		//declare docova_nequality function to do type and value check
        $compiler
            ->write("\n", 'function docova_nequality($left, $right){', "\n")
            ->indent()
            ->write(' return $left !== $right;', "\n")
            ->outdent();

        $compiler
            ->outdent()
            ->write("}\n");
         //-- end of docova_nequality function
            
        //declare docova_equality function to do === comparison
        $compiler
        	->write("\n", 'function docova_equality($left, $right){', "\n")
        	->indent()
        	->write('if ((is_array($left) && !is_array($right)) || (is_array($right) && !is_array($left))) {', "\n")
        	->indent()
        	->write('$array = is_array($left) ? $left : $right;', "\n")
        	->write('$value = !is_array($left) ? $left : $right;', "\n")
        	->write('return in_array($value, $array);', "\n")
        	->outdent()
        	->write("} \n")
        	->write('elseif (is_array($left) && is_array($right)) {', "\n")
        	->indent()
        	->write('$left = array_values($left);' ,"\n")
        	->write('$right = array_values($right);', "\n")
        	->write('$lcount = count($left);', "\n")
        	->write('$rcount = count($right);', "\n")
        	->write('for ($x = 0; $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if (array_key_exists($x, $right) && $left[$x] == $right[$x]) {', "\n")
        	->indent()
        	->write('return true;', "\n")
        	->outdent()
        	->write("} \n")
        	->outdent()
        	->write("} \n")
        	->write('if ($lcount < $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($lcount - 1); $x < $rcount; $x++) {', "\n")
        	->indent()
        	->write('if ($right[$x] == $left[$lcount - 1]) {', "\n")
        	->indent()
        	->write('return true;', "\n")
        	->outdent()
        	->write("} \n")
        	->outdent()
        	->write("} \n")
        	->outdent()
        	->write('} elseif ($rcount < $lcount) {', "\n")
        	->indent()
        	->write('for ($x = ($rcount - 1); $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$x] == $right[$rcount - 1]) {')
        	->indent()
        	->write('return true;', "\n")
        	->outdent()
        	->write("} \n")
        	->outdent()
        	->write("} \n")
        	->outdent()
        	->write("} \n")
        	->write('return false;', "\n")
        	->outdent()
        	->write("} \n")
        	->write('else {', "\n")
        	->indent()
        	->write('return $left === $right;', "\n")
        	->outdent()
        	->write('}', "\n");
        
        $compiler
        	->outdent()
        	->write("}\n");
        //-- end of docova_equality function
        
        //declare docova_greaterthan function for >>> operator
        $compiler
	       	->write("\n", 'function docova_greaterthan($left, $right){', "\n")
	       	->indent()
	       	->write('if (!is_array($left) && !is_array($right)) {', "\n")
	       	->indent()
        	->write('return $left > $right;', "\n")
        	->outdent()
	       	->write('} elseif (is_array($left) && !is_array($right)) {', "\n")
	       	->indent()
	       	->write('foreach ($left as $value) {', "\n")
	       	->indent()
	       	->write('if ($value > $right) { return true; }', "\n")
	       	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif (!is_array($left) && is_array($right)) {', "\n")
        	->indent()
        	->write('foreach ($right as $value) {', "\n")
        	->indent()
        	->write('if ($left > $value) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} else {', "\n")
        	->indent()
        	->write('$lcount = count($left);', "\n")
        	->write('$rcount = count($right);', "\n")
        	->write('for ($x = 0; $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if (array_key_exists($x, $right) && $left[$x] > $right[$x]) { return true; }', "\n")
        	->write('if ($lcount < $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($lcount-1); $x < $rcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$lcount-1] > $right[$x]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif ($lcount > $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($rcount-1); $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$x] > $right[$rcount - 1]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return false;', "\n")
        	->outdent()
        	->write('}', "\n");

        $compiler
        	->outdent()
        	->write("}\n");
        //-- end of docova_greaterthan function

        //declare docova_greaterequal function for >== operator
        $compiler
	       	->write("\n", 'function docova_greaterequal($left, $right){', "\n")
	       	->indent()
	       	->write('if (!is_array($left) && !is_array($right)) {', "\n")
	       	->indent()
        	->write('return $left >= $right;', "\n")
        	->outdent()
	       	->write('} elseif (is_array($left) && !is_array($right)) {', "\n")
	       	->indent()
	       	->write('foreach ($left as $value) {', "\n")
	       	->indent()
	       	->write('if ($value >= $right) { return true; }', "\n")
	       	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif (!is_array($left) && is_array($right)) {', "\n")
        	->indent()
        	->write('foreach ($right as $value) {', "\n")
        	->indent()
        	->write('if ($left >= $value) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} else {', "\n")
        	->indent()
        	->write('$lcount = count($left);', "\n")
        	->write('$rcount = count($right);', "\n")
        	->write('for ($x = 0; $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if (array_key_exists($x, $right) && $left[$x] >= $right[$x]) { return true; }', "\n")
        	->write('if ($lcount < $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($lcount-1); $x < $rcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$lcount-1] >= $right[$x]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif ($lcount > $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($rcount-1); $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$x] >= $right[$rcount - 1]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return false;', "\n")
        	->outdent()
        	->write('}', "\n");

        $compiler
        	->outdent()
        	->write("}\n");
        //-- end of docova_greaterequal function

        //declare docova_smallerthan function for <<< operator
        $compiler
        	->write("\n", 'function docova_smallerthan($left, $right){', "\n")
        	->indent()
        	->write('if (!is_array($left) && !is_array($right)) {', "\n")
        	->indent()
        	->write('return $left < $right;', "\n")
        	->outdent()
        	->write('} elseif (is_array($left) && !is_array($right)) {', "\n")
        	->indent()
        	->write('foreach ($left as $value) {', "\n")
        	->indent()
        	->write('if ($value < $right) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif (!is_array($left) && is_array($right)) {', "\n")
        	->indent()
        	->write('foreach ($right as $value) {', "\n")
        	->indent()
        	->write('if ($left < $value) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} else {', "\n")
        	->indent()
        	->write('$lcount = count($left);', "\n")
        	->write('$rcount = count($right);', "\n")
        	->write('for ($x = 0; $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if (array_key_exists($x, $right) && $left[$x] < $right[$x]) { return true; }', "\n")
        	->write('if ($lcount < $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($lcount-1); $x < $rcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$lcount-1] < $right[$x]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif ($lcount > $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($rcount-1); $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$x] < $right[$rcount - 1]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return false;', "\n")
        	->outdent()
        	->write('}', "\n");
        	
        $compiler
        	->outdent()
        	->write("}\n");
        //-- end of docova_smallerthan function

        //declare docova_smallerequal function for <== operator
        $compiler
        	->write("\n", 'function docova_smallerequal($left, $right){', "\n")
        	->indent()
        	->write('if (!is_array($left) && !is_array($right)) {', "\n")
        	->indent()
        	->write('return $left <= $right;', "\n")
        	->outdent()
        	->write('} elseif (is_array($left) && !is_array($right)) {', "\n")
        	->indent()
        	->write('foreach ($left as $value) {', "\n")
        	->indent()
        	->write('if ($value <= $right) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif (!is_array($left) && is_array($right)) {', "\n")
        	->indent()
        	->write('foreach ($right as $value) {', "\n")
        	->indent()
        	->write('if ($left <= $value) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} else {', "\n")
        	->indent()
        	->write('$lcount = count($left);', "\n")
        	->write('$rcount = count($right);', "\n")
        	->write('for ($x = 0; $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if (array_key_exists($x, $right) && $left[$x] <= $right[$x]) { return true; }', "\n")
        	->write('if ($lcount < $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($lcount-1); $x < $rcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$lcount-1] <= $right[$x]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('} elseif ($lcount > $rcount) {', "\n")
        	->indent()
        	->write('for ($x = ($rcount-1); $x < $lcount; $x++) {', "\n")
        	->indent()
        	->write('if ($left[$x] <= $right[$rcount - 1]) { return true; }', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return false;', "\n")
        	->outdent()
        	->write('}', "\n");
        	
        $compiler
        	->outdent()
        	->write("}\n");
        //-- end of docova_smallerequal function

        //add the method docova_multiply
        $compiler
        	->write("\n", 'function docova_multiply($left, $right) {', "\n")
        	->indent()
        	->write('if (!is_array($left) && !is_array($right)) {', "\n")
        	->indent()
        	->write('return $left * $right;', "\n")
        	->outdent()
        	->write('} elseif ((is_array($left) && !is_array($right)) || (is_array($right) && !is_array($left))) {', "\n")
        	->indent()
        	->write('$single_value = is_array($left) ? $right : $left;' ,"\n")
        	->write('$multi_value = is_array($left) ? $left : $right;', "\n")
        	->write('foreach($multi_value as $index => $value) {', "\n")
        	->indent()
        	->write('$multi_value[$index] = $single_value * $value;', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return $multi_value;', "\n")
        	->outdent()
        	->write('} else {', "\n")
        	->indent()
        	->write('$lcount = count($left);', "\n")
        	->write('$rcount = count($right);', "\n")
        	->write('$less = $lcount < $rcount ? array_values($left) : array_values($right);', "\n")
        	->write('$more = $lcount >= $rcount ? array_values($left) : array_values($right);', "\n")
        	->write('if ($lcount !== $rcount) {', "\n")
        	->indent()
        	->write('$less = array_merge($less, array_fill(0, abs($lcount - $rcount), $less[count($less) - 1]));', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('$output = [];', "\n")
        	->write('for($x = 0; $x < count($more); $x++) {', "\n")
        	->indent()
        	->write('$output[] = $less[$x] * $more[$x];', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return $output;', "\n")
        	->outdent()
        	->write('}', "\n");

        $compiler
        	->outdent()
        	->write("}\n");
        //-- end of docova_multiply function


        //add the method docova_division
        $compiler
        	->write("\n", 'function docova_division($left, $right) {', "\n")
        	->indent()
        	->write('if (!is_array($left) && !is_array($right)) {', "\n")
        	->indent()
        	->write('return $left / $right;', "\n")
        	->outdent()
        	->write('} elseif ((is_array($left) && !is_array($right)) || (is_array($right) && !is_array($left))) {', "\n")
        	->indent()
        	->write('$single_value = is_array($left) ? $right : $left;' ,"\n")
        	->write('$multi_value = is_array($left) ? $left : $right;', "\n")
        	->write('foreach($multi_value as $index => $value) {', "\n")
        	->indent()
        	->write('$multi_value[$index] = $single_value / $value;', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return $multi_value;', "\n")
        	->outdent()
        	->write('} else {', "\n")
        	->indent()
        	->write('$lcount = count($left);', "\n")
        	->write('$rcount = count($right);', "\n")
        	->write('$less = $lcount < $rcount ? array_values($left) : array_values($right);', "\n")
        	->write('$more = $lcount >= $rcount ? array_values($left) : array_values($right);', "\n")
        	->write('if ($lcount !== $rcount) {', "\n")
        	->indent()
        	->write('$less = array_merge($less, array_fill(0, abs($lcount - $rcount), $less[count($less) - 1]));', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('$output = [];', "\n")
        	->write('for($x = 0; $x < count($more); $x++) {', "\n")
        	->indent()
        	->write('$output[] = $less[$x] / $more[$x];', "\n")
        	->outdent()
        	->write('}', "\n")
        	->write('return $output;', "\n")
        	->outdent()
        	->write('}', "\n");
        	
        $compiler
        	->outdent()
        	->write("}\n");
        //-- end of docova_division function
        
       	//add the method docova_subtraction
       	$compiler
           	->write("\n", 'function docova_subtraction($left = null, $right)', "\n")
           	->write('{', "\n")
           	->indent()
           	->write('if (is_null($left)) {', "\n")
           	->indent()
           	->write('return (- 1 * $right);', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('if (! is_array($left) && ! is_array($right)) {', "\n")
           	->indent()
           	->write('if (is_a($left, "DateTime")) {', "\n")
           	->indent()
           	->write('$left = $left->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('if (is_a($right, "DateTime")) {', "\n")
           	->indent()
           	->write('$right = $right->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('return ($left - $right);', "\n")
           	->outdent()
           	->write('} elseif ((is_array($left) && ! is_array($right)) || (is_array($right) && ! is_array($left))) {', "\n")
           	->indent()
           	->write('$output = [];', "\n")
           	->write('if (is_array($left)) {', "\n")
           	->indent()
           	->write('for ($x = 0; $x < count($left); $x ++) {', "\n")
           	->indent()
           	->write('if (is_a($left[$x], "DateTime")) {', "\n")
           	->indent()
           	->write('$left[$x] = $left[$x]->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('if (is_a($right, "DateTime")) {', "\n")
           	->indent()
           	->write('$right = $right->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('$output[] = $left[$x] - $right;', "\n")
           	->outdent()
           	->write('}', "\n")
           	->outdent()
           	->write('} elseif (is_array($right)) {', "\n")
           	->indent()
           	->write('for ($x = 0; $x < count($right); $x ++) {', "\n")
           	->indent()
           	->write('if (is_a($left, "DateTime")) {', "\n")
           	->indent()
           	->write('$left = $left->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('if (is_a($right[$x], "DateTime")) {', "\n")
           	->indent()
           	->write('$right[$x] = $right[$x]->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('$output[] = $left - $right[$x];', "\n")
           	->outdent()
           	->write('}', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('return $output;', "\n")
           	->outdent()
           	->write('} else {', "\n")
           	->indent()
           	->write('$lcount = count($left);', "\n")
           	->write('$rcount = count($right);', "\n")
           	->write('if ($lcount < $rcount) {', "\n")
           	->indent()
           	->write('$left = array_merge($left, array_fill(0, ($rcount - $lcount), $left[$lcount - 1]));', "\n")
           	->outdent()
           	->write('} elseif ($rcount < $lcount) {', "\n")
           	->indent()
           	->write('$right = array_merge($right, array_fill(0, ($lcount - $rcount), $right[$rcount - 1]));', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('$output = [];', "\n")
           	->write('for ($x = 0; $x < count($left); $x ++) {', "\n")
           	->indent()
           	->write('if (is_a($left[$x], "DateTime")) {', "\n")
           	->indent()
           	->write('$left[$x] = $left[$x]->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('if (is_a($right[$x], "DateTime")) {', "\n")
           	->indent()
           	->write('$right[$x] = $right[$x]->getTimeStamp();', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('$output[] = $left[$x] - $right[$x];', "\n")
           	->outdent()
           	->write('}', "\n")
           	->write('return $output;', "\n")
           	->outdent()
           	->write('}', "\n");

       	$compiler
        	->outdent()
        	->write("}\n");
       	//-- end of docova_subtraction function

        //add the method docova_array_append
          $compiler
            ->write("\n", 'function docova_array_append($left, $right ){', "\n")
            ->indent()
            ->write('if ( $left instanceof DateTime )', "\n")
            ->indent()
            ->write(' $left = [$left];', "\n")
            ->outdent()
            ->write('if ( $right instanceof DateTime )', "\n")
            ->indent()
            ->write(' $right = [$right];', "\n")
            ->outdent()
            ->write( 'if ( is_numeric($left) && is_numeric($right) ) ', "\n")
            ->indent()
            ->write('return [$left, $right];', "\n" )
            ->outdent()
            ->write('if ( is_string($left) && is_string($right))', "\n" )
            ->indent()
            ->write('return [$left, $right];', "\n" )
            ->outdent()
            ->write('if (  is_Array($left) && is_array($right) )', "\n")
            ->indent()
            ->write('return array_merge($left, $right);', "\n")
            ->outdent()
            ->write('if ( is_Array($left) )', "\n")
            ->write("{\n") 
            ->indent()
            ->write('array_push($left, $right );', "\n")
            ->write('return $left;', "\n")
            ->outdent()
            ->write('}else if ( is_array($right) ){', "\n")
            ->indent()
            ->write('array_unshift( $right, $left );', "\n")
            ->write('return $right;', "\n")
            ->outdent()
            ->write('}', "\n");
        
        $compiler
            ->outdent()
            ->write("}\n");
        //-- end of docova_array_append function

    } 
    
}