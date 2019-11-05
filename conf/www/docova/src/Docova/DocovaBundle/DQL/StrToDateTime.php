<?php

namespace Docova\DocovaBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\Lexer;

/**
 * Convert a string to a datetime format
 * MySQL and SQL Server are supported
 * @author javad rahimi
 *        
 */
class StrToDateTime extends FunctionNode 
{
	private $stringDateExpression = null;
	private $sourceDateFormat = null; //default will "dd/mm/yyyy hh:mi:ss"
	
	public function getSql(SqlWalker $sqlWalker)
	{
		$driver = $sqlWalker->getConnection()->getDriver()->getName();
		if ($driver == 'pdo_mysql')
		{
			if ($this->sourceDateFormat) {
				return 'STR_TO_DATE('.
					$this->stringDateExpression->dispatch($sqlWalker) . ',' .
					$this->sourceDateFormat->dispatch($sqlWalker) .
				')';
			}
			else {
				return 'STR_TO_DATE('.
					$this->stringDateExpression->dispatch($sqlWalker) . ',\'%d/%m/%Y %H:%i:%s\'' .
				')';
			}
		}
		elseif ($driver == 'pdo_sqlsrv')
		{
			if ($this->sourceDateFormat) {
				return 'TRY_CONVERT(datetime, ' .
					$this->stringDateExpression->dispatch($sqlWalker) . ','.
					$this->sourceDateFormat->dispatch($sqlWalker) .
				')';
			}
			else {
				return 'TRY_CONVERT(datetime, ' .
					$this->stringDateExpression->dispatch($sqlWalker) . ',103'.
				')';
			}
		}
	}
	
	public function parse(Parser $parser)
	{
		$lexer = $parser->getLexer();
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->stringDateExpression = $parser->ArithmeticPrimary();
		if (Lexer::T_COMMA === $lexer->lookahead['type']) 
		{
			$parser->match(Lexer::T_COMMA);
			$this->sourceDateFormat = $parser->ArithmeticPrimary();
		}
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}