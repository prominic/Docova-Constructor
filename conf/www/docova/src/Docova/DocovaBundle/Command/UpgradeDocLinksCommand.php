<?php
namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Docova\DocovaBundle\Extensions\ViewManipulation;
use Docova\DocovaBundle\Entity\FormTextValues;
use Docova\DocovaBundle\ObjectModel\Docova;

/**
 * Class to upgrade migrated DocLinks from Notes in SE
 * @author javad_rahimi
 */
class UpgradeDocLinksCommand extends ContainerAwareCommand
{
	private $_em;
	private $_app = null;
	private $_count = 0;
	private $_total = 0;
	private $_unresolved = 0;
	private $_resolved = 0;
	private $_resolved_images = 0;
	private $debug = false;
	
	protected function configure()
	{
		$this->setName('docova:upgradedoclinks')
			->setDescription('Upgrade migrated DocLinks and Image Links in DOCOVA SE')
			->addArgument('application', InputArgument::OPTIONAL, 'application id to upgrade doclinks and image links; empty means all apps.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$app = $input->getArgument('application');
		$this->_em = $this->getContainer()->get('doctrine')->getManager();
		$appcoll = null;
		
		if (!empty($app))
		{
			if ($app == "all" ){
				$appcoll = $this->_em->getRepository('DocovaBundle:Libraries')->findBy(array('isApp' => true, 'Trash' => false));
			}else{
				$appcoll = array($this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'isApp' => true, 'Trash' => false)));
			}
		}else{
			throw new \Exception('Unspecified application source.');
		}
		
		foreach ( $appcoll as $this->_app)
		{
			$output->writeln("\r\n\r\nDoing APP : ".$this->_app->getId()." Title: ".$this->_app->getLibraryTitle()."\r\n\r\n");
			$this->_total = 0;
			$this->_count = 0;
		
			//step1. get all text values
			$document_values = $this->_em->getRepository('DocovaBundle:FormTextValues')->fetchDocumentTexts(!empty($this->_app) ? $this->_app->getId() : null);
			$router = $this->getContainer()->get('router');
			$docova = new Docova($this->getContainer());
			$handler = new ViewManipulation($docova);
			$docova = null;
			//step2. look for Notes DocLinks format in each one and replace it with SE links
			if (!empty($document_values) && !empty($document_values[0]))
			{
				$contexts = array();
				$len = count($document_values);
				for ($x = 0; $x < $len; $x++)
				{
					if (array_key_exists($document_values[$x]['Doc_Id'].';'.$document_values[$x]['Field_Id'], $contexts)) {
						$contexts[$document_values[$x]['Doc_Id'].';'.$document_values[$x]['Field_Id']][] = $document_values[$x]['fieldValue'];
					}
					else {
						$contexts[$document_values[$x]['Doc_Id'].';'.$document_values[$x]['Field_Id']] = array($document_values[$x]['fieldValue']);
					}
				}
				$migrationid = "";
				$this->_unresolved = 0;
				$this->_resolved = 0;
				$this->_resolved_images = 0;
				
				foreach ($contexts as $dockey => $value)
				{
					$full_text = implode('', $value);
					
					if (false !== stripos($full_text, 'href="Notes://') || false !== stripos($full_text, '_storage/Embedded/') || false !== stripos(urldecode($full_text), '/$FILE/'))
					{
						//unencode any characters that might have been encoded by a previous run of this code
						$full_text = html_entity_decode($full_text);
						
						//now encode any left over & to &amp;
						$full_text = str_replace('&', '&amp;', $full_text);
						$full_text = str_replace('<doclinkdata>', '&lt;doclinkdata&gt;', $full_text);
						$full_text = str_replace('</doclinkdata>', '&lt;/doclinkdata&gt;', $full_text);
						
						$docid = explode(';', $dockey);
						$field = $docid[1];
						
						//if ( $docid[0] != "6D6F8F0C-0823-4D4A-9A7A-EF78E86FA1AD" ){
							//$output->writeln("SKIPPING UNID ".$docid[0]);
						//	continue;
						//}

						if (empty($migrationid)){
							$query = "SELECT M.MIGRATION_ID FROM tb_migration_details AS M WHERE M.ID = ?";
							$result = $handler->selectQuery($query, array($docid[0]));
							$migrationid = $result[0]['MIGRATION_ID'];
						}
						
						if (false !== stripos($full_text, 'href="Notes://'))
						{
							//step3. replace the old doclinks with new DocLinks
							$replaced = $this->replaceNotesDocLink($full_text, $migrationid, $handler, $router, $output, $docid[0]);
						}
						if (false !== stripos(urldecode($full_text), '/$FILE/'))
						{
							//step4. update the URL for all embedded files to a valid SE URL
							$replaced = $this->replaceEmbeddedFilePath((!empty($replaced) ? $replaced : $full_text), $docid[0], $handler);
						}
						if ((!empty($replaced) && false !== stripos($replaced, '_storage/Embedded/')) || (empty($replaced) && false !== stripos($full_text, '_storage/Embedded/')))
						{
							//step5. change rich text embedded images' path to a valid URL
							$replaced = $this->replaceEmbeddedImagesPath((!empty($replaced) ? $replaced : $full_text), $docid[0]);
						}

						if (!empty($replaced ))
						{
							//$output->writeln($replaced);
							$len = floor(strlen($replaced) / count($value));
							$replaced = str_split($replaced, $len);
							if (count($replaced) > $len) {
								$replaced[count($replaced) - 2] .= $replaced[count($replaced) - 1];
								$replaced[count($replaced) - 1] = null;
								unset($replaced[count($replaced) - 1]);	
							}
							
							$document = $this->_em->getReference('DocovaBundle:Documents', $docid[0]);
							$field = $this->_em->getReference('DocovaBundle:DesignElements', $field);
							
							$this->_em->getRepository('DocovaBundle:FormTextValues')->createQueryBuilder('V')
								->delete()
								->where('V.Document = :docid')
								->andWhere('V.Field = :field_id')
								->setParameter('docid', $docid[0])
								->setParameter('field_id', $field)
								->getQuery()
								->execute();

							for ($x = 0; $x < count($replaced); $x++)
							{
								if (!empty($replaced[$x])) {
									$text_value = new FormTextValues();
									$text_value->setDocument($document);
									$text_value->setField($field);
									$text_value->setFieldValue($replaced[$x]);
									$text_value->setOrder($x);
									$text_value->setSummaryValue(substr($replaced[$x], 0, 450));
									$this->_em->persist($text_value);
									$this->_em->flush();
									$text_value = null;
								}
							}
							
							if ( $this->debug ) $output->writeln("Updated doclinks written to database.");
							
							$replaced = $full_text = null;
							$this->_count++;
						}
						$this->_total++;
						
					}
				}
				
				$output->writeln("Updated application doclinks.  Resolved: ".$this->_resolved." Unresolved: ".$this->_unresolved.PHP_EOL);
				if($this->_resolved_images > 0){
				    $output->writeln("Updated application image links.  Resolved: ".$this->_resolved_images.PHP_EOL);
				}
							
			}
			else {
				$output->writeln('No text value found in this app.');
			}
		}
	}
	
	
	/**
	 * Find AppID base on migrated rep ID
	 * 
	 * @param string $repid
	 * @param ViewManipulation $handler
	 * @return NULL|string
	 */
	private function getSEIdfromRepId($repid, $handler)
	{
		$query = "SELECT M.Doc_id, M.Field_Id FROM tb_form_text_values AS M WHERE M.field_value = ?";
		$resultnew = $handler->selectQuery($query, array($repid));
		$targetdbid = null;
		foreach ( $resultnew as $res)
		{
			//$newunid = $res['Doc_id'];
			$fieldid = $res['Field_Id'];
			//look for app id from form id
			$query = "SELECT M.Form_id FROM tb_design_elements AS M WHERE M.id = ?";
			$resultformid = $handler->selectQuery($query, array( $fieldid));
			if ( isset($resultformid[0]['Form_id'] ) ){
				$query = "SELECT M.App_id FROM tb_app_forms AS M WHERE M.id = ?";
				$resultappid = $handler->selectQuery($query, array( $resultformid[0]['Form_id']));
			}
			
			if ( isset($resultappid[0]['App_id'])){
				$targetdbid = $resultappid[0]['App_id'];
			}
			
			//we can find multiples...make sure that this app is not trashed
			$query = "SELECT M.Id, M.Trash FROM tb_libraries AS M WHERE M.Id = ?";
			$resultnew = $handler->selectQuery($query, array($targetdbid));
			
			if ($resultnew[0]['Trash'] != "1" )
				break;	
		}
		return $targetdbid;
		
	}

	/**
	 * Find AppID using the original Domino ID
	 * 
	 * @param ViewManipulation $handler
	 * @param string $unid
	 * @param OutputInterface $output
	 * @return array
	 */
	private function getseidfromdocoriginalid($handler, $unid, $output)
	{
		$result = array();
		$result['docid'] = null;
		$result['targetdbid'] = null;
		$query = "SELECT M.Doc_id, M.Field_Id FROM tb_form_text_values AS M WHERE M.field_value = ?";
		$resultnew = $handler->selectQuery($query, array($unid));
		$newunid = null;
		$targetdbid  = null;
		foreach ( $resultnew as $res)
		{
			$newunid = $res['Doc_id'];
			//make sure doc isn't deleted
			$query = "SELECT M.App_id, M.Trash FROM tb_folders_documents AS M WHERE M.id = ?";
			$resultfd= $handler->selectQuery($query, array( $newunid));

			//make sure app isn't deleted
			$query = "SELECT M.Id, M.Trash FROM tb_libraries AS M WHERE M.Id = ?";
			$resultappid = $handler->selectQuery($query, array( $resultfd[0]['App_id']));

			if ( $resultfd[0]['Trash'] != '1' && $resultappid[0]['Trash'] != '1' ){
				$targetdbid = $resultappid[0]['Id'];
				break;	
			}
		}
		
		if (!empty($newunid) and !empty($targetdbid)){
			if ( $this->debug) $output->writeln("  SUCCESS: Old id: ".$unid. " New id: ". $newunid." the the app ".$targetdbid);
			$result['docid'] = $newunid;
			$result['targetdbid'] = $targetdbid ;
		}
					
		return $result;

	}

	/**
	 * Change doclink attribute base on hyper-link (doclink) type
	 * 
	 * @param array $urlexploded
	 * @param ViewManipulation $handler
	 * @param \DOMElement $hyper
	 * @param OutputInterface $output
	 * @return boolean
	 */
	private function handledbdoclink($urlexploded, $handler, $hyper, $output)
	{
		//this is a database link
		$modded = false;
		$dbrepid = trim($urlexploded[3]);
		if ($this->debug) $output->writeln("  Database Link found with dbrepid : ".$urlexploded[3] );
		$SEDbId = $this->getSEIdfromRepId($dbrepid, $handler);
		if (!empty( $SEDbId ) ){
			if ( $this->debug) $output->writeln("  SUCCESS: For dbrepid : ".$urlexploded[3] ." found SE ID ". $SEDbId );
			$hyper->setAttribute('seid', $SEDbId);
			$hyper->setAttribute('class', 'docova_dbdoclink');
			$hyper->setAttribute('href', '');
			$firstchild = $hyper->firstChild;
			
			if ( $firstchild->nodeName == "i" ){
				$firstchild->removeAttribute("class");
				$firstchild->setAttribute("class", "far fa-database");
			}else{
				$firstchild->removeAttribute("class");
				$firstchild->setAttribute("class", "far fa-database");
				$hyper->appendChild($firstchild);
			}
			
			$image = $hyper->getElementsByTagName('img')->item(0);
			if (!empty($image))
				$image->parentNode->removeChild($image);
			
		
			
			if (false !== stripos($firstchild->nodeValue,  'doclinkdata'))
			{
				
				$firstchild->parentNode->removeChild($firstchild);
			}
			$this->_resolved ++;
			$modded = true;
		}
		else
		{
			$this->_unresolved ++;
			if ( $this->debug) $output->writeln("  ERROR : For dbrepid : ".$urlexploded[3] ." SE ID NOT FOUND" );
		}
		return $modded;
	}


	
	/**
	 * Find a Notes DocLink and replace it with an image
	 * 
	 * @param string $context
	 * @param string $migrationid
	 * @param ViewManipulation $handler
	 * @param \Symfony\Component\Routing\Router $router
	 * @param OutputInterface $output
	 * @param string $curunid
	 * @return NULL|string
	 */
	private function replaceNotesDocLink($context, $migrationid, $handler, $router, $output, $curunid)
	{
		if (false === stripos($context, '<html>')) {
			$context = '<html>'.$context.'</html>';
		}
		$html = new \DOMDocument();

		$context = mb_convert_encoding($context, 'HTML-ENTITIES', 'UTF-8');
		
		$loadedok = true;
		libxml_use_internal_errors(true);
		try{
			@$html->loadHTML($context, LIBXML_NOWARNING);
		}catch (\Exception $e) {
			$loadedok = false;
		}
		if(count(libxml_get_errors()) > 0){
			$loadedok = false;
		}
		libxml_clear_errors();
		if(!$loadedok){
			$output->writeln("ERROR loading HTML \r\n" . $context);
			return null;
		}
		
		$sectionsNames = [];
		$modded = false;

		foreach ($html->getElementsByTagName('a') as $hyper)
		{
			$name = $hyper->getAttribute('name');
			if ( !empty($name))
				$sectionsNames[]  = urldecode ($name ) ;
		}
		
		if ($this->debug) $output->writeln("\r\n------------------------Processing UNID: ".$curunid."-------------------------\r\n");
	
		foreach ($html->getElementsByTagName('a') as $hyper)
		{
			$href = $hyper->getAttribute('href');
			if (false !== stripos($href, 'Notes://'))
			{
				if ( $this->debug) $output->writeln("Link ". $href);
				$doclink = $hyper->previousSibling;
				$doclinktxt = "";
				//if first run then the doclinkdata is before the link..if not, it has been moved to the url as a parameter
				
				if (!empty($doclink) && false !== stripos($doclink->nodeValue, 'doclinkdata')) {
					$doclinktxt = $doclink->nodeValue;
					$unid = substr($href, strrpos($href, '/') + 1);
				}else{
					//get it from the parameter
					$doclinktxt = substr($href, strrpos($href, 'doclinkdata=') + 12);
					$doclinktxt = urldecode($doclinktxt);
					if ($this->debug) $output->writeln( "link text is ".$doclinktxt);
					$unid = substr($href, strrpos($href, '/') + 1);
					$unid = explode('&', $unid)[0];
				}
				
				$unid = trim($unid);
				
				//do we have this id in the migration details 
				$query = "SELECT M.ID FROM tb_migration_details AS M WHERE M.OLD_ID = ? and M.Migration_ID = ?";
				$result = $handler->selectQuery($query, array('DK'.$unid, $migrationid));

				$link_icon = $html->createElement('i');
				$attr = $html->createAttribute('class');
				$attr->value = 'far fa-external-link';
				$link_icon->appendChild($attr);
				$attr = $html->createAttribute('style');
				$attr->value = 'color: #0080ff';
				$link_icon->appendChild($attr);
				$attr = null;
				$targetdbid = null;
				$newunid = null;
				$docsorigid = "";
				
				$urlexploded =explode('/', $href);
				
				if ( !empty($urlexploded[3]) and !isset($urlexploded[4]) and !isset($urlexploded[5]))
				{
					//this is a database link

					$retval =  $this->handledbdoclink($urlexploded, $handler, $hyper, $output);
					$modded = $modded ? $modded : $retval;
					continue;
				}

				if (!empty($result[0])){
					//found using tb_migration_details table
					$newunid = $result[0]['ID'];
					$query = "SELECT M.OLD_ID FROM tb_migration_details AS M WHERE M.ID = ? and M.Migration_ID = ?";
					$resultorigid = $handler->selectQuery($query, array($curunid, $migrationid));
					if (!empty($resultorigid[0])) {
						//will be used to determine if the anchor links points to this doc or a different one
						$docsorigid = $resultorigid[0]['OLD_ID'];
					}
					
				}else{
					//try to find it using the original Domino ID, which will be in tb_form_text_values through the data import process
					$res = $this->getseidfromdocoriginalid($handler, $unid, $output);
					$newunid = $res['docid'];
					$targetdbid = $res['targetdbid'];
				}
				
				if (!empty($newunid))
				{
					if (false !== stripos($doclinktxt,  'doclinkdata')) {
						$linkdata = $doclinktxt;
						$linkdata = str_replace('<doclinkdata>', '', $linkdata);
						$linkdata = str_replace('</doclinkdata>', '', $linkdata);
						$sections = explode('~', $linkdata);
						
						if (!empty($sections[2]) and ($docsorigid == "DK".$unid) and !empty($sections[4]) and in_array($sections[4], $sectionsNames  ) ) {
							$href = '#'.rawurlencode($sections[4]);
							$hyper->setAttribute('href', $href);
							$hyper->setAttribute('class', 'docova_internaldoclink');
							$image = $hyper->getElementsByTagName('img')->item(0);
							$image->parentNode->removeChild($image);
							$firstchild = $hyper->firstChild;
							if ( empty($firstchild) or $firstchild->nodeName != "i" ){
								$hyper->appendChild($link_icon);
							}

							if (!empty($doclink) && false !== stripos($doclink->nodeValue, 'doclinkdata'))
							{
								$doclinkparent = $doclink->parentNode;
								$doclinkparent->removeChild($doclink);
							}
							$this->_resolved ++;
							if ( $this->debug) $output->writeln("  SUCCESS: Found anchor link");
							$modded = true;
							continue;
						}
					}
					$new_id = $newunid;
					$tdbid = empty($targetdbid) ? $this->_app->getId() : $targetdbid;
					$href = $router->generate('docova_readappdocument', array('docid' => $new_id)).'?OpenDocument&ParentUNID='.$tdbid;
					$hyper->setAttribute('href', $href);
					$hyper->setAttribute('class', 'docova_doclink');
					$hyper->setAttribute('targetid', $new_id);
					
					$image = $hyper->getElementsByTagName('img')->item(0);
					if (!empty($image))
						$image->parentNode->removeChild($image);

					$firstchild = $hyper->firstChild;
					if ( empty($firstchild) or $firstchild->nodeName != "i" ){
						$hyper->appendChild($link_icon);
					}
					
					

					if (!empty($doclink) && false !== stripos($doclink->nodeValue, 'doclinkdata'))
					{
						$doclink->parentNode->removeChild($doclink);
					}
					
					$this->_resolved ++;
					if ( $this->debug) $output->writeln("  SUCCESS: Found links target");
					$modded = true;
				}
				else
				{
					$this->_unresolved ++;
					$classname =  $hyper->getAttribute('class');
					if ( $this->debug) $output->writeln("  ERROR: External Link...looking for ".$unid." Got: Nothing");
					if ( empty($classname ) ){
						$hyper->setAttribute('class', 'docova_doclink');
						//store the original doclinkdata and remove the doclinkdatatext
						
						if (!empty($doclink) && false !== stripos($doclink->nodeValue, 'doclinkdata')) {
							$hyper->setAttribute('href', $href."&doclinkdata=".urlencode($doclink->nodeValue) );
							$hyper->appendChild($link_icon);
							$doclink->parentNode->removeChild($doclink);
							if ( $this->debug) $output->writeln("  Keeping original Notes LINK : ".$href."&doclinkdata=".urlencode($doclink->nodeValue));
						}else{
							if ( $this->debug) $output->writeln("  ERROR: no doclinkdata found ".(!empty($doclink) ? $doclink->nodeValue : 'for empty doclink!'));
						}
						$image = $hyper->getElementsByTagName('img')->item(0);
						if ( !empty($image))
							$image->parentNode->removeChild($image);

						$modded = true;
					}
				}
			}
		}
//		$node = $html->getElementsByTagName('docovasection')->item(0);
		if ( $modded ){
			$res = $html->saveHTML();
			return $res;
		}
		else 
			return null;
	}
	
	/**
	 * Replace embedded file links to a valid SE file links
	 * 
	 * @param string $context
	 * @param string $docid
	 * @param ViewManipulation $handler
	 */
	private function replaceEmbeddedFilePath($context, $docid, $handler)
	{
		if (false === stripos($context, '<html>')) {
			$context = '<html>'.$context.'</html>';
		}
		
		$html = new \DOMDocument();
		$context = mb_convert_encoding($context, 'HTML-ENTITIES', 'UTF-8');
		
		$loadedok = true;
		libxml_use_internal_errors(true);
		try{
			@$html->loadHTML($context, LIBXML_NOWARNING);
		}catch (\Exception $e) {
			$loadedok = false;
		}
		if(count(libxml_get_errors()) > 0){
			$loadedok = false;
		}
		libxml_clear_errors();
		if(!$loadedok){
			return false;
		}
				
		$router = $this->getContainer()->get('router');
		$query = 'SELECT V.Field_Value FROM tb_form_text_values AS V JOIN tb_design_elements AS F ON V.Field_Id = F.id WHERE F.Field_Name = ? AND V.Doc_Id = ?';
		$result = $handler->selectQuery($query, array('dockey', $docid));
		if (empty($result[0]['Field_Value'])) {
			return false;
		}
		$orig_unid = substr($result[0]['Field_Value'], 2);
		foreach ($html->getElementsByTagName('a') as $link)
		{
			$href = urldecode($link->getAttribute('href'));
			if (false !== stripos($href, '/$FILE/') && false !== stripos($href, $orig_unid))
			{
				$file_name = urldecode($link->getAttribute('title'));
				$url = '../..'.$router->generate('docova_opendocfile', array('file_name' => rawurlencode($file_name))).'?doc_id='.rawurlencode($docid);
				$link->setAttribute('href', $url);
				$link->setAttribute('target', '_blank');
				$this->_resolved_images ++;
			}
		}
		return $html->saveHTML();
	}
	
	/**
	 * Find all embedded images and replace src with a valid URL
	 * 
	 * @param string $context
	 * @param string $docid
	 * @return string|boolean
	 */
	private function replaceEmbeddedImagesPath($context, $docid)
	{
		if (false === stripos($context, '<html>')) {
			$context = '<html>'.$context.'</html>';
		}
		$html = new \DOMDocument();
		$context = mb_convert_encoding($context, 'HTML-ENTITIES', 'UTF-8');
		
		$loadedok = true;
		libxml_use_internal_errors(true);
		try{
			@$html->loadHTML($context, LIBXML_NOWARNING);
		}catch (\Exception $e) {
			$loadedok = false;
		}
		if(count(libxml_get_errors()) > 0){
			$loadedok = false;
		}
		libxml_clear_errors();
		if(!$loadedok){
			return false;
		}
		
		foreach ($html->getElementsByTagName('img') as $image)
		{
			$src = $image->getAttribute('src');
			if (false !== stripos($src, '_storage/Embedded/'))
			{
				$img_name = htmlspecialchars(htmlspecialchars(substr($src, strrpos($src, '/')+1)));
				$url = '../embeddedImage/'.$docid.'?image='.urlencode($img_name);
				$image->setAttribute('src', $url);
				$this->_resolved_images ++;
			}
		}
		return $html->saveHTML();
	}
}