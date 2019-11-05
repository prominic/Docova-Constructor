<?php
namespace Docova\DocovaBundle\Twig;

//use Docova\DocovaBundle\Twig\DocovaForTagNode;


class DocovaForTokenParser extends \Twig_TokenParser 
{

   public function parse(\Twig_Token $token)
   {
        $parser = $this->parser;
        $stream = $this->parser->getStream();
       
        $id = $stream->expect(\Twig_Token::STRING_TYPE)->getValue();
        //skip over "("
        $stream->next();
        $init = $this->parseAssignment();
      
        $ifexpr = null;
        $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma in for loop');
        $ifexpr = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(\Twig_Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma in for loop');
        $increment = $this->parseAssignment();
        //skip over ")"
        $stream->next();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

       
       
        //$body = new \Twig_Node(array($this->parser->subparse(array($this, 'decideMyTagEnd'), true)));
        $body = $this->parser->subparse(array($this, 'decideMyTagEnd'), true);

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new DocovaForTagNode($body, $id, $init, $ifexpr, $increment, $token->getLine(), $this->getTag());
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
     
     return $token->test('enddocovafor');
   }

   /**
    * Your tag name: if the parsed tag match the one you put here, your parse()
    * method will be called.
    *
    * @return string
    */
   public function getTag()
   {
      return 'docovafor';
   }

}