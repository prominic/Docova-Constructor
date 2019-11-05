<?php
namespace Docova\DocovaBundle\Twig;
  
//use Docova\DocovaBundle\Twig\DocovaForTagNode;


class DocovaIfTokenParser extends \Twig_TokenParser 
{

   public function parse(\Twig_Token $token)
   {
        $parser = $this->parser;
        $stream = $this->parser->getStream();
        $exprArr = Array();
        $exprCount = 0;
       
       //parse string of the format f_if id (testexpression, trueexpression, falseexpression)
        //read in the id
        $id = $stream->expect(\Twig_Token::STRING_TYPE)->getValue(); // editor
        //skip over "("
        $stream->next();
        $expr1 = $this->parser->getExpressionParser()->parseExpression();

        array_push($exprArr, $expr1);
       
        $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma in for loop');

        while (true)
        {
          
           $assign = false;
          //now check if this is an assinment
           $count = 1;
           while (true){
            $tmptok = $stream->look($count);
            
            if ( $tmptok->test(\Twig_token::OPERATOR_TYPE,'='))
            {
              $assign = true;
              break;
            }else if ( $tmptok->test(\Twig_Token::PUNCTUATION_TYPE, ",") or ($tmptok->test(\Twig_Token::PUNCTUATION_TYPE, ")") ) )
              break;
            $count++;
          }

         
          if ( $assign ){
              $expr2 = $this->parseAssignment();
          }else{
               $expr2 = $this->parser->getExpressionParser()->parseExpression();
          }
           array_push($exprArr, $expr2);

          if ($stream->test(\Twig_Token::PUNCTUATION_TYPE, ")"))
              break;


           $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma in for loop');
        }
        //skip over ")"
        $stream->next();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        /*
        $assign = false;
        //now check if this is an assinment
        $count = 1;
        while (true){
          $tmptok = $stream->look($count);
          
          if ( $tmptok->test(\Twig_token::OPERATOR_TYPE,'='))
          {
            $assign = true;
            break;
          }else if ( $tmptok->test(\Twig_Token::PUNCTUATION_TYPE, ",") )
            break;
          $count++;
        }

        if ( $assign ){
            $expr2 = $this->parseAssignment();
        }else{
             $expr2 = $this->parser->getExpressionParser()->parseExpression();
        }
        $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma in for loop');
       
        $assign = false;
        //now check if this is an assinment
        $count = 1;
        while (true){
          $tmptok = $stream->look($count);
         
          if ( $tmptok->test(\Twig_token::OPERATOR_TYPE,'='))
          {
            $assign = true;
            break;
          }else if ($tmptok->test(\Twig_Token::PUNCTUATION_TYPE, ")"))
            break;

          $count++;
        }

        if ( $assign ){
            $expr3 = $this->parseAssignment();
        }else{
             $expr3 = $this->parser->getExpressionParser()->parseExpression();
        }

        //skip over ")"
        $stream->next();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

       
       
//$body = $this->parser->subparse(array($this, 'decideMyTagEnd'), true);

     //   $stream->expect(\Twig_Token::BLOCK_END_TYPE);
      */

        return new DocovaIfTagNode($id, $exprArr, $token->getLine(), $this->getTag());
    }

    private function parseAssignment ()
    {
        $parser = $this->parser;
        $stream = $this->parser->getStream();
        $targets = array();
        
        $token = $stream->expect(\Twig_Token::NAME_TYPE, null, 'Only variables can be assigned to');
        $value = $token->getValue();
        if (in_array(strtolower($value), array('true', 'false', 'none', 'null'))) {
            throw new \Twig_Error_Syntax(sprintf('You cannot assign a value to "%s".', $value), $token->getLine(), $stream->getSourceContext());
        }
        $targets[] = new \Twig_Node_Expression_AssignName($value, $token->getLine());
        $names = new \Twig_Node($targets);
        if ($stream->nextIf(\Twig_Token::OPERATOR_TYPE, '=')) 
        {

          $targets2 = array();
       
          $targets2[] = $this->parser->getExpressionParser()->parseExpression();
            
       

           // $values = $this->parser->getExpressionParser()->parseMultitargetExpression();
          $values = new \Twig_Node($targets2);
           
        }
        return new \Twig_Node_Set(false, $names, $values, 0, "Set");

    }


    

   /**
    * Callback called at each tag name when subparsing, must return
    * true when the expected end tag is reached.
    *
    * @param \Twig_Token $token
    * @return bool
    */
   public function decideMyTagEnd(\Twig_Token $token)
   {
     
     return $token->test('endf_If');
   }

   /**
    * Your tag name: if the parsed tag match the one you put here, your parse()
    * method will be called.
    *
    * @return string
    */
   public function getTag()
   {
      return 'f_If';
   }

}