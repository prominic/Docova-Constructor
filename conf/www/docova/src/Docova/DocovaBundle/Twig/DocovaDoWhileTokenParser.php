<?php
namespace Docova\DocovaBundle\Twig;

//use Docova\DocovaBundle\Twig\DocovaDoWhileTagNode;


class DocovaDoWhileTokenParser extends \Twig_TokenParser 
{

   public function parse(\Twig_Token $token)
   {
        $parser = $this->parser;
        $stream = $this->parser->getStream();
       
        $id = $stream->expect(\Twig_Token::STRING_TYPE)->getValue();
        //skip over "("
        $stream->next();
        
        $ifexpr = null;
        $ifexpr = $this->parser->getExpressionParser()->parseExpression();
        $stream->next();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
     
        $body = $this->parser->subparse(array($this, 'decideMyTagEnd'), true);

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new DocovaDoWhileTagNode($body, $id, $ifexpr, $token->getLine(), $this->getTag());
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
     
     return $token->test('enddocovadowhile');
   }

   /**
    * Your tag name: if the parsed tag match the one you put here, your parse()
    * method will be called.
    *
    * @return string
    */
   public function getTag()
   {
      return 'docovadowhile';
   }

}