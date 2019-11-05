<?php
namespace Docova\DocovaBundle\Twig;

//use Docova\DocovaBundle\Twig\DocovaTagNode;


class DocovaTokenParser extends \Twig_TokenParser 
{

   public function parse(\Twig_Token $token)
   {
        $parser = $this->parser;
        $stream = $parser->getStream();
        $values = null;
        if (!$stream->test(\Twig_Token::BLOCK_END_TYPE))   
           $values = $stream->expect(\Twig_Token::STRING_TYPE)->getValue(); // editor
           

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

       
       
        $body = $this->parser->subparse(array($this, 'decideMyTagEnd'), true);

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new DocovaTagNode($body, $values, $token->getLine(), $this->getTag());

      
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
     
     return $token->test('enddocovascript');
   }

   /**
    * Your tag name: if the parsed tag match the one you put here, your parse()
    * method will be called.
    *
    * @return string
    */
   public function getTag()
   {
      return 'docovascript';
   }

}