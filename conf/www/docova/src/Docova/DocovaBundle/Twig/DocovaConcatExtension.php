<?php

namespace Docova\DocovaBundle\Twig;
//use Docova\DocovaBundle\Twig\DocovaConcatExtension;

class DocovaConcatExtension extends \Twig_Extension
{
    public function getName() {
        return 'DocovaConcat';
    }

    public function getOperators() {
        return [
            [
            	'--' => array('precedence' => 500, 'class' => 'Twig_Node_Expression_Unary_Neg'),
            ],
            [
                '++' => array('precedence' => 30, 'class' => DocovaConcat::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            	'--' => array('precedence' => 30, 'class' => DocovaSubtract::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
                '::' => array('precedence' => 20, 'class' => DocovaArrayAppend::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
				'!==' => array('precedence' => 20, 'class' => DocovaNEquality::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
                '===' => array('precedence' => 20, 'class' => DocovaEquality::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            	'>>>' => array('precedence' => 20, 'class' => DocovaGreaterThan::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            	'<<<' => array('precedence' => 20, 'class' => DocovaSmallerThan::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            	'>==' => array('precedence' => 20, 'class' => DocovaGreaterEqual::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            	'<==' => array('precedence' => 20, 'class' => DocovaSmallerEqual::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            	'***' => array('precedence' => 60, 'class' => DocovaMultiply::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            	'///' => array('precedence' => 60, 'class' => DocovaDivision::class, 'associativity' => \Twig_ExpressionParser::OPERATOR_LEFT),
            ]
        ];
        
    }
}