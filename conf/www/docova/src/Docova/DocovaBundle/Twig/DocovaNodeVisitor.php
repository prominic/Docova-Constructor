<?php

namespace Docova\DocovaBundle\Twig;

//use Docova\DocovaBundle\Twig\DocovaOutputNode;
//use Docova\DocovaBundle\Twig\Node\DocovaFunctionNode;
use Docova\DocovaBundle\Twig\Node\DocovaHandleReturnNode;
use Docova\DocovaBundle\Twig\Node\DocovaModuleNode;
use Docova\DocovaBundle\Twig\Node\DocovaForSetNode;
//use Docova\DocovaBundle\Twig\DocovaTagDefNode;
use Docova\DocovaBundle\Twig\Node\DocovaForNode;
use Docova\DocovaBundle\Twig\Node\DocovaWhileNode;
use Docova\DocovaBundle\Twig\Node\DocovaDoWhileNode;
use Docova\DocovaBundle\Twig\Node\DocovaDoNode;
use Docova\DocovaBundle\Twig\Node\DocovaIfNode;

class DocovaNodeVisitor implements \Twig_NodeVisitorInterface
{
    private $dodata = array();
  
    private $isDocovaBlock = false;
    //private $parentFunctionName = "";
    private $processed = false;
    private $doprocessed = false;
    private $nodeAdjuster;
    private $docovascriptblocks = array();
    private $docovadoblocks = array();
    private $docovaforblocks = array();
    private $docovawhileblocks = array();    
    private $docovadowhileblocks = array();    
    private $docovaifblocks = array();

  
    public function __construct()
    {
       

        $this->nodeAdjuster = new DocovaNodeAdjuster();    

    }


    public function enterNode(\Twig_Node $node, \Twig_Environment $env)
    {
       


        if ($node instanceof DocovaTagNode){
            $this->isDocovaBlock = true; 
            $this->nodeAdjuster->getAllFunctions($node->getNode("body"));
        }

        return $node;
    }

   

    public function leaveNode(\Twig_Node $node, \Twig_Environment $env)
    {

        if ($node instanceof \Twig_Node_Module) {
            $newModuleNode = new DocovaModuleNode($node);

             foreach ( $this->docovascriptblocks as $tnode)
            {
                   $nodeAdjuster = $this->nodeAdjuster;
                   $nodeAdjuster->adjustScriptNodes($tnode);
                   $this->docovascriptblocks[$tnode->getAttribute("key")] = $tnode;
            }

            foreach ( $this->docovadoblocks as $tnode)
            {
                   $nodeAdjuster = $this->nodeAdjuster;
                   $nodeAdjuster->adjustScriptNodes($tnode);
                   $this->docovadoblocks[$tnode->getFunctionID()] = $tnode;
            }

            foreach ( $this->docovaifblocks as $tnode)
            {
                   $this->docovaifblocks[$tnode->getFunctionID()] = $tnode;
            }

            foreach ( $this->docovaforblocks as $tnode)
            {
                    $nodeAdjuster = $this->nodeAdjuster;
                    $nodeAdjuster->adjustForScriptNodes($tnode);
                    $this->docovaforblocks[$tnode->getFunctionID()] = $tnode;
            }
            
            foreach ( $this->docovawhileblocks as $tnode)
            {
            	$nodeAdjuster = $this->nodeAdjuster;
            	$nodeAdjuster->adjustScriptNodes($tnode);
            	$this->docovawhileblocks[$tnode->getFunctionID()] = $tnode;
            }            
            
            foreach ( $this->docovadowhileblocks as $tnode)
            {
            	$nodeAdjuster = $this->nodeAdjuster;
            	$nodeAdjuster->adjustScriptNodes($tnode);
            	$this->docovadowhileblocks[$tnode->getFunctionID()] = $tnode;
            }

            $newModuleNode->setAttribute("docovascriptblocks", $this->docovascriptblocks);
            $newModuleNode->setAttribute("docovadoblocks", $this->docovadoblocks);
            $newModuleNode->setAttribute("docovaifblocks", $this->docovaifblocks);
            $newModuleNode->setAttribute("docovaforblocks", $this->docovaforblocks);
            $newModuleNode->setAttribute("docovawhileblocks", $this->docovawhileblocks);
            $newModuleNode->setAttribute("docovadowhileblocks", $this->docovadowhileblocks);
            
            //return $newModuleNode;

            $node->setNode("class_end", $newModuleNode);
        
        }else if ( $node instanceof DocovaForTagNode ){
            $nodeAdjuster =$this->nodeAdjuster;
             //override the output of the set tags in init and increment expressions of the for loop
            $initnode = $node->getNode("init");
            $setnode = new DocovaForSetNode( $initnode );
            $node->setNode("init", $setnode);

            $increment = $node->getNode("increment");
            $setnode = new DocovaForSetNode( $increment );
            $node->setNode("increment", $setnode);

            //now we remove any non "set" nodes from the for loop...this is what domino does
            $this->docovaforblocks[$node->getFunctionID()] = $node;
             return new \Twig_Node();
             
        }else if ( $node instanceof DocovaWhileTagNode ){
           	$nodeAdjuster =$this->nodeAdjuster;
           	$this->docovawhileblocks[$node->getFunctionID()] = $node;
           	return new \Twig_Node();
       	}else if ( $node instanceof DocovaDoWhileTagNode ){
           	$nodeAdjuster =$this->nodeAdjuster;
           	$this->docovadowhileblocks[$node->getFunctionID()] = $node;
           	return new \Twig_Node();                  
        }else if ( $node instanceof DocovaDoTagNode)
        {   
            $this->docovadoblocks[$node->getFunctionID()] = $node;
            return new \Twig_Node();
        }else if ( $node instanceof DocovaIfTagNode)
        {
            $this->docovaifblocks[$node->getFunctionID()] = $node;
            return new \Twig_Node();        
        }else if ( $node instanceof DocovaTagNode){
           
            $id = $this->getVarName();
            $this->isDocovaBlock= false;
            $node->setAttribute("key", $id);
            $this->docovascriptblocks[$id] = $node;
            return new DocovaTagDefNode(new \Twig_Node(), $node->getAttribute("key"), $node->mode, $node->expect);
        }else if ($node instanceof \Twig_Node_Expression_Name && $this->isDocovaBlock)
        {
              $funcname = $node->getAttribute('name');
              $funclist = $this->nodeAdjuster->getFunctionList() ;
              if ( array_key_exists ( $funcname , $funclist))
              {
                    if ($funclist[$funcname] == "DocovaDoTagNode") {
                        $donode =  new DocovaDoNode( new \Twig_Node(), $funcname);
                        return $donode;
                    }else if ( $funclist[$funcname] == "DocovaIfTagNode" ){
                        $ifnode =  new DocovaIfNode( new \Twig_Node(), $funcname);
                        return $ifnode;
                    }else if ( $funclist[$funcname] == "DocovaForTagNode" ){
                        $fornode =  new DocovaForNode( new \Twig_Node(), $funcname);
                        return $fornode;
                    }else if ( $funclist[$funcname] == "DocovaWhileTagNode" ){
                        $whilenode =  new DocovaWhileNode( new \Twig_Node(), $funcname);
                        return $whilenode;
                    }else if ( $funclist[$funcname] == "DocovaDoWhileTagNode" ){
                        $whilenode =  new DocovaDoWhileNode( new \Twig_Node(), $funcname);
                        return $whilenode;
                    }
                   
              }
        }
        else if ($node instanceof \Twig_Node_Expression_Function && $this->isDocovaBlock)
        {
              $funcname = $node->getAttribute('name');
             
              if ( $funcname == "f_Return")
              { 
                     $retval = $node->getNode("arguments")->getNode(0);
                    $retnode =  new DocovaHandleReturnNode( new \Twig_Node(), $retval, $node->getAttribute("throwexception"));
                    return $retnode;
              }
        }


        return $node;
    }


    public function getVarName()
    {
        return sprintf('__internal_%s', hash('sha256', uniqid(mt_rand(), true), false));
    }

    public function getPriority()
    {
        return 0;
    }
}