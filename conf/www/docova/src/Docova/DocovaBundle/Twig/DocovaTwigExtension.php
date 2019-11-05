<?php

namespace Docova\DocovaBundle\Twig;

class DocovaTwigExtension extends \Twig_Extension
{
    public function getName() {
        return 'DocovaTwig';
    }

    public function getTokenParsers()
   {
      return array (
            new DocovaTokenParser(),
            new DocovaDoTokenParser(),
            new DocovaForTokenParser(),
      		new DocovaWhileTokenParser(),
      		new DocovaDoWhileTokenParser(),      		
            new DocovaIfTokenParser(),
      );
   }

   
}