<?php

namespace Docova\DocovaBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\Lexer;

/**
 * Convert string value to a numeric fixed-point precision type
 * Supported in SQL and MySQL
 * @author javad rahimi
 *        
 */
class StrToNumeric extends FunctionNode 
{
	protected $stringExpression = null;
	protected $precision = null;
	protected $scale = null;

	public function getSql(SqlWalker $sqlWalker) 
	{
		$driver = $sqlWalker->getConnection()->getDriver()->getName();
		if ($driver == 'pdo_sqlsrv') 
		{
			$cast = 'TRY_CAST(' . $this->stringExpression->dispatch($sqlWalker) . ' AS DECIMAL';			
		}
		else {
			$cast = 'CAST(' . $this->stringExpression->dispatch($sqlWalker) . ' AS DECIMAL';
		}
		if ($this->precision)
		{
			$cast .= '(' . $this->precision->dispatch($sqlWalker) ;
			if ($this->scale) 
			{
				$cast .= ',' . $this->scale->dispatch($sqlWalker);
			}
			else {
				$cast .= ',2';
			}
			$cast .= ')';
		}
		else {
			$cast .= '(10,2)';
		}
		
		$cast .= ')';
		
		return $cast;
	}
	
	public function parse(Parser $parser) 
	{
		$lexer = $parser->getLexer();
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->stringExpression = $parser->ArithmeticPrimary();
		$parser->match(Lexer::T_AS);
		$parser->match(Lexer::T_IDENTIFIER);
		if (Lexer::T_OPEN_PARENTHESIS === $lexer->lookahead['type']) 
		{
			$parser->match(Lexer::T_OPEN_PARENTHESIS);
			$this->precision = $parser->ArithmeticPrimary();
			if (Lexer::T_COMMA === $lexer->lookahead['type']) 
			{
				$parser->match(Lexer::T_COMMA);
				$this->scale = $parser->ArithmeticPrimary();
			}
			$parser->match(Lexer::T_CLOSE_PARENTHESIS);
		}
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}