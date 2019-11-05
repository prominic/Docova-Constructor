<?php
namespace Docova\DocovaBundle\Extensions;

class ExternalConnections {
	public static function getConnection($name){
		switch ($name){
			case "Video Games":
				return ExternalConnections::getVideoGamesDbConnection();
				break;
			case "DOCOVA LOOKUPS":
				return ExternalConnections::getLookupsDbConnection();
				break;
		}
	}	
	
	public static function getVideoGamesDbConnection(){
		//Connection Info
		$host = "localhost";
		$port = "1433";
		$user="sa";
		$pwd = "SyncMa5t3r";
			
		//Connect
		try {
			return new \PDO('sqlsrv:Server='.$host.','.$port.';Database=VideoGames',$user,$pwd);
		} catch (\PDOException $e) {
			echo "PDO Exception: ".$e->getMessage();
			throw $e;
		}
	}
	public static function getLookupsDbConnection(){
		//Connection Info
		$host = "localhost";
		$port = "1433";
		$user="sa";
		$pwd = "SyncMa5t3r";
		
		//Connect
		try {
			return new \PDO('sqlsrv:Server='.$host.','.$port.';Database=DOCOVA_LOOKUPS',$user,$pwd);			
		} catch (\PDOException $e) {
			echo "PDO Exception: ".$e->getMessage();
			throw $e;
		}		
	}	
}
?>