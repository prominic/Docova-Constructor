<?php

namespace Docova\DocovaBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\Lexer;

/**
 * Convert a string to an integer
 * SQL Server suport only currently
 * @author Jeff Primeau
 *        
 */
class StrToInteger extends FunctionNode 
{
	private $stringSubject = null;
	
	public function getSql(SqlWalker $sqlWalker)
	{
		if ($this->stringSubject) 
		{
			$driver = $sqlWalker->getConnection()->getDriver()->getName();
			if ($driver == 'pdo_mysql')
			{
				return "CONVERT({$this->stringSubject->dispatch($sqlWalker)}, UNSIGNED INTEGER)";
			}
			else {
				return "TRY_CONVERT(integer, {$this->stringSubject->dispatch($sqlWalker)})";
			}
		}
		else {
			return '';
		}
	}
	
	public function parse(Parser $parser)
	{		
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->stringSubject = $parser->StringPrimary();		
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}