<?php

namespace Docova\DocovaBundle\Twig;

//use Docova\DocovaBundle\Twig\Node\DocovaModuleNode;
//use Docova\DocovaBundle\Twig\Node\DocovaForSetNode;
//use Docova\DocovaBundle\Twig\Node\DocovaReturnNode;
//use Docova\DocovaBundle\Twig\DocovaOutputNode;
use Docova\DocovaBundle\Twig\Node\DocovaHandleReturnNode;
use Docova\DocovaBundle\Twig\Node\DocovaForNode;
use Docova\DocovaBundle\Twig\Node\DocovaWhileNode;
use Docova\DocovaBundle\Twig\Node\DocovaDoWhileNode;
use Docova\DocovaBundle\Twig\Node\DocovaDoNode;
use Docova\DocovaBundle\Twig\Node\DocovaIfNode;


class DocovaNodeAdjuster 
{
	/* function that goes through all docovascript body nodes
	/* and adjusts the nodes to remove any print nodes and text nodes 
	*/

	protected $functionList = Array();

  public function getFunctionList() {
    return $this->functionList;
  }

  public function addToFunctionList($name, $type){
      $this->functionList[$name] = $type;
  }

  public function setFunctionHasReturn ($name) 
  {
    if (array_key_exists ( $name ,$this->functionList ))
      $this->functionList[$name] = true;
  }

  public function getAllFunctions ($node)
  {

      if  ( $node instanceof DocovaDoTagNode ){
      	$this->addToFunctionList ($node->getFunctionID(), "DocovaDoTagNode" );
      }else if  ( $node instanceof DocovaForTagNode ){
        $this->addToFunctionList ($node->getFunctionID(), "DocovaForTagNode" );
      }else if  ( $node instanceof DocovaWhileTagNode ){
        $this->addToFunctionList ($node->getFunctionID(), "DocovaWhileTagNode" );     
	  }else if  ( $node instanceof DocovaDoWhileTagNode ){
        $this->addToFunctionList ($node->getFunctionID(), "DocovaDoWhileTagNode" );        	
	  }else if ( $node instanceof DocovaIfTagNode ){
        $this->addToFunctionList ($node->getFunctionID(), "DocovaIfTagNode" );
      }else if ( $node instanceof \Twig_Node_Expression_Function ){
        $funcname = $node->getAttribute('name');
             
        if ( $funcname == "f_Return")
        {
            if ( ! $node->hasAttribute("throwexception")){
               $node->setAttribute("throwexception", false);
            }
        }
      }
      
      $it = $node->getIterator();
      while( $it->valid() )
      {
        $tmpnode = $it->current();
        $this->getAllFunctions($tmpnode);
        $it->next();
      }
  }

  /*---------------------------------------------------------------------------------------------
  /* function to remove all "output" nodes except for the last one
  /* or if a non last node has a f_Return in it.
  /*----------------------------------------------------------------------------------------------*/

  public function _removePrintNodes($originalNode)
  {
    $prearr = Array();
    $newarr = Array();
    $foundprint = false;

    for ($x = $originalNode->count()-1; $x >= 0; $x--) 
      {
          $tmpnode = $originalNode->getNode($x);

          if ($tmpnode instanceof \Twig_Node_Print  && !$foundprint )
          {
              $foundprint = true;
              array_unshift($prearr, $tmpnode);
          }else if  ($tmpnode instanceof \Twig_Node_Print && $foundprint ){
             //we will keep the node if there a a for node..or if there is a f_return node
              $tmpexpr = $tmpnode->getNode('expr');
              if ( $tmpexpr->hasNode("node") ){
                $tmpexprnode = $tmpexpr->getNode("node");
                
                if ( $tmpexprnode instanceof DocovaForNode || $tmpexprnode instanceof DocovaDoNode || $tmpexprnode instanceof DocovaIfNode ||  $tmpexprnode instanceof DocovaWhileNode ||  $tmpexprnode instanceof DocovaDoWhileNode){
                     $tmpexprnode->setAttribute("usesemicolon", true);
                     array_unshift($prearr, $tmpexprnode);
                }else if ($tmpexprnode instanceof DocovaHandleReturnNode){
                     $tmpexprnode->throwexception= true;
                     array_unshift($prearr, $tmpexprnode);
                }
              }
          }else{
            //ignore any text nodes as those nodes are outside the delimeters
            if ( ! $tmpnode instanceof \Twig_Node_Text)
              array_unshift($prearr, $tmpnode);
          }
      } 

      for ($x = 0; $x < count($prearr); $x++) {
         $tmpnode = $prearr[$x];
          if ( $tmpnode instanceof \Twig_Node_Print){
            $newModuleNode = new DocovaOutputNode($tmpnode);
            array_push($newarr, $newModuleNode);
         }else{
            array_push($newarr, $tmpnode);
        }
     
    }
    return $newarr;
  }

	public function adjustScriptNodes($node)
	{
		  $prearr = Array();
		  $newarr = Array();
		  $foundprint = false;
		  $originalNode = $node->getNode("body");
      $class = get_class($originalNode);
      if ( ($class != "Twig_Node" ))
        $originalNode = new \Twig_Node(array($originalNode));
   

    	$newarr = $this->_removePrintNodes($originalNode);
  	  $node->setNode("body", new \Twig_Node($newarr));
    
	}

	

	public function adjustForScriptNodes($node)
	{
		$prearr = Array();
		$newarr = Array();
		
		$body = $node->getNode("body");
    $class = get_class($body);
    if ( ($class != "Twig_Node" ))
      $body = new \Twig_Node(array($body));
   



    for ($x = 0; $x < $body->count();  $x++) 
    {
       $tmpnode = $body->getNode($x);
       if ( $tmpnode instanceof \Twig_Node_Set)
         array_push($newarr, $tmpnode);
       else if ($tmpnode instanceof \Twig_Node_Print )
       {
              $tmpexpr = $tmpnode->getNode('expr');
              if ( $tmpexpr->hasNode("node") ){
                $tmpexprnode = $tmpexpr->getNode("node");
                if ( $tmpexprnode instanceof DocovaDoNode || $tmpexprnode instanceof DocovaForNode || $tmpexprnode instanceof DocovaWhileNode || $tmpexprnode instanceof DocovaDoWhileNode){
                     $tmpexprnode->setAttribute("usesemicolon", true);
                     array_push($newarr, $tmpexprnode);
                }else if ($tmpexprnode instanceof DocovaHandleReturnNode){
                     $tmpexprnode->throwexception= true;
                     array_push($newarr, $tmpexprnode);
                }
              }
       }
  	} 
  	$node->setNode("body", new \Twig_Node($newarr));
	}

}