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
class StrReplace extends FunctionNode 
{
	private $stringSubject = null;
	private $stringSearch = null; 
	private $stringReplace = null;
	
	public function getSql(SqlWalker $sqlWalker)
	{
		if ($this->stringSubject && $this->stringSearch) {
			return 'REPLACE(' .
				$this->stringSubject->dispatch($sqlWalker) . ','.					
				$this->stringSearch->dispatch($sqlWalker) . ','.
				$this->stringReplace->dispatch($sqlWalker) .
			')';
		}
		else {
			return '';
		}
	}
	
	public function parse(Parser $parser)
	{
		$lexer = $parser->getLexer();
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->stringSubject = $parser->StringPrimary();
		if (Lexer::T_COMMA === $lexer->lookahead['type']) 
		{
			$parser->match(Lexer::T_COMMA);
			$this->stringSearch = $parser->StringPrimary();
		}
		if (Lexer::T_COMMA === $lexer->lookahead['type'])
		{
			$parser->match(Lexer::T_COMMA);
			$this->stringReplace = $parser->StringPrimary();
		}
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}