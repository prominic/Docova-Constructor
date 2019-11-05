<?php

namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to handle all views functionalities either 
 * DOCOVA views or custom applicatoin views
 * @author javad_rahimi
 *        
 */
class ViewsController extends Controller 
{
	private $user;
	private  $global_settings;
	protected $UPLOAD_FILE_PATH;
	protected $root_path;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();

		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];

		$this->root_path = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT']; 
		$this->UPLOAD_FILE_PATH = $this->root_path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
	}

	public function viewServicesAction(Request $request)
	{
		$post_req = $request->getContent();
		if (empty($post_req)) {
			throw $this->createNotFoundException('No post request is submitted.');
		}
	
		$this->initialize();
		$post_xml = new \DOMDocument();
		$post_xml->loadXML(urldecode($post_req));
	
		$action = $post_xml->getElementsByTagName('Action')->item(0)->nodeValue;
		if ($action === 'PASTE') {
			$target_folder	= $post_xml->getElementsByTagName('targetfolder')->item(0)->nodeValue;
				
			if (empty($target_folder) || empty($post_xml->getElementsByTagName('Unid')->length)) {
				throw $this->createNotFoundException('Target folder or Document ID is missed.');
			}
				
			$em = $this->getDoctrine()->getManager();
			$folder_obj	= $em->find('DocovaBundle:Folders', $target_folder);
			if (empty($folder_obj))
			{
				throw $this->createNotFoundException('Could not find any folder for ID = '. $target_folder);
			}
				
			$security_check = new Miscellaneous($this->container);
			$done = false;
			foreach ($post_xml->getElementsByTagName('Unid') as $item)
			{
				$document_obj = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $item->nodeValue, 'Archived' => false));
				if (!empty($document_obj))
				{
					if (true === $security_check->canCreateDocument($folder_obj))
					{
						$clip_action = $post_xml->getElementsByTagName('clipaction')->item(0)->nodeValue;
						if ($clip_action == 'copy') {
							$this->pasteFolderDocuments($folder_obj, array($document_obj));
						}
						elseif ($clip_action == 'cut')
						{
							$document_obj->setFolder($folder_obj);
							$document_obj->setModifier($this->user);
							$document_obj->setIndexed(false);
							$document_obj->setDateModified(new \DateTime());
								
							$em->flush();
							$security_check->createDocumentLog($em, 'UPDATE', $document_obj, 'Moved Document.');
						}
						$done = true;
					}
				}
			}
				
			$response_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			if ($done === true)
			{
				$child = $response_xml->createElement('Result', 'OK');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			else {
				$child = $response_xml->createElement('Result', 'FAILED');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
	
				$child = $response_xml->createElement('Result', 'Could not complete copying selected documents. Try again or contact admin.');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'ErrMsg';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			$response = new Response($response_xml->saveXML());
			$response->headers->set('Content-Type', 'text/xml');
				
			return $response;
		}
		elseif ($action === 'DELETESELECTED')
		{
			if (empty($post_xml->getElementsByTagName('Unid')->length) || empty($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue))
			{
				throw $this->createNotFoundException('Document ID(s) is/are missed.');
			}
				
			$deleted = 0;
			$security_check = new Miscellaneous($this->container);
			$em = $this->getDoctrine()->getManager();
			$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $post_xml->getElementsByTagName('Unid')->item(0)->nodeValue, 'Archived' => false));
			if (true === $security_check->canSoftDeleteDocument($document))
			{
				unset($document);
				foreach ($post_xml->getElementsByTagName('Unid') as $doc_id)
				{
					$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id->nodeValue, 'Archived' => false));
					if (!empty($document))
					{
						if ($document->getFolder()->getFolderName() == html_entity_decode($post_xml->getElementsByTagName('FolderName')->item(0)->nodeValue))
						{
							$document->setTrash(true);
							$document->setDateDeleted(new \DateTime());
							$document->setDeletedBy($this->user);
							$bmk = $document->getBookmarks();
							if ($bmk->count()) {
								foreach ($bmk as $b) {
									$em->remove($b);
								}
							}
							$em->flush();
							$deleted++;
	
							$security_check->createDocumentLog($em, 'DELETE', $document);
						}
						else {
							$query = $em->getRepository('DocovaBundle:Bookmarks')->createQueryBuilder('B')
							->join('B.Target_Folder', 'F')
							->where('B.Document = :document')
							->andWhere('F.Folder_Name = :folder')
							->setParameters(array('document' => $document->getId(), 'folder' => html_entity_decode($post_xml->getElementsByTagName('FolderName')->item(0)->nodeValue)))
							->getQuery();
							$bmk = $query->getSingleResult();
							$em->remove($bmk);
							$em->flush();
							$deleted++;
						}
					}
					$document = $bmk = $b = null;
				}
			}
				
			$response_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			if ($deleted !== 0)
			{
				$child = $response_xml->createElement('Result', 'OK');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
	
				$child = $response_xml->createElement('Result', $deleted+1);
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Ret1';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			else {
				$child = $response_xml->createElement('Result', 'FAILED');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
	
				$child = $response_xml->createElement('Result', 'You have insufficient access to delete one or more of the selected documents.');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'ErrMsg';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			$response = new Response($response_xml->saveXML());
			$response->headers->set('Content-Type', 'text/xml');
			return $response;
		}
		elseif ($action === 'UNDELETESELECTED')
		{
			if (empty($post_xml->getElementsByTagName('Unid')->length) || empty($post_xml->getElementsByTagName('Unid')->item(0)->nodeValue))
			{
				throw $this->createNotFoundException('Document ID(s) is/are missed.');
			}
	
			$security_check = new Miscellaneous($this->container);
			$restored = 0;
			$em = $this->getDoctrine()->getManager();
			foreach ($post_xml->getElementsByTagName('Unid') as $index => $doc_id)
			{
				if ($post_xml->getElementsByTagName('rectype')->item($index)->nodeValue == 'fld') {
					$document = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $doc_id->nodeValue, 'Del' => true));
					if (!empty($document))
					{
						$document->setDel(false);
						$document->setDateDeleted();
						$document->setDeletedBy();
						$em->flush();
						$em->getRepository('DocovaBundle:Folders')->resetInactiveSubfolders($document->getPosition(), $document->getLibrary()->getId());
	
						$restored++;
						$security_check->createFolderLog($em, 'DELETE', $document, 'Restored document from recycle bin.');
					}
				}
				else {
					$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $doc_id->nodeValue, 'Trash' => true, 'Archived' => false));
					if (!empty($document))
					{
						$document->setTrash(false);
						$document->setDateDeleted();
						$document->setDeletedBy();
						$em->flush();
	
						$restored++;
						$security_check->createDocumentLog($em, 'DELETE', $document, 'Restored document from recycle bin.');
					}
				}
				unset($document);
			}
	
			$response_xml = new \DOMDocument("1.0", "UTF-8");
			$root = $response_xml->appendChild($response_xml->createElement('Results'));
			if ($restored > 0)
			{
				$child = $response_xml->createElement('Result', 'OK');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
					
				$child = $response_xml->createElement('Result', $restored+1);
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Ret1';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			else {
				$child = $response_xml->createElement('Result', 'FAILED');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'Status';
				$child->appendChild($attrib);
				$root->appendChild($child);
					
				$child = $response_xml->createElement('Result', 'Could not restore the selected items.');
				$attrib = $response_xml->createAttribute('ID');
				$attrib->value = 'ErrMsg';
				$child->appendChild($attrib);
				$root->appendChild($child);
			}
			$response = new Response($response_xml->saveXML());
			$response->headers->set('Content-Type', 'text/xml');
			return $response;
		}
		elseif ($action === 'REMOVE')
		{
			$security_context = $this->container->get('security.authorization_checker');
			if ($security_context->isGranted('ROLE_ADMIN') && !empty($post_xml->getElementsByTagName('Unid')->length))
			{
				$cnt = 0;
				$em = $this->getDoctrine()->getManager();
				foreach ($post_xml->getElementsByTagName('Unid') as $item)
				{
					$id = substr($item->nodeValue, 1);
					$document = $em->getRepository('DocovaBundle:Documents')->findOneBy(array('id' => $id, 'Trash' => true));
					if (!empty($document) && $this->deleteDocumentPermanently($document)) {
						$cnt++;
					}
					else {
						$folder = $em->getRepository('DocovaBundle:Folders')->findOneBy(array('id' => $id, 'Del' => true));
						if (!empty($folder) && $this->deleteFolderPermanently($folder)) {
							$cnt++;
						}
					}
				}
	
				$response_xml = new \DOMDocument("1.0", "UTF-8");
				$root = $response_xml->appendChild($response_xml->createElement('Results'));
				if ($cnt > 0)
				{
					$child = $response_xml->createElement('Result', 'OK');
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$child->appendChild($attrib);
					$root->appendChild($child);
					$child = $response_xml->createElement('Resutl', $cnt);
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'Ret1';
					$child->appendChild($attrib);
					$root->appendChild($child);
				}
				else {
					$child = $response_xml->createElement('Result', 'FAILED');
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'Status';
					$child->appendChild($attrib);
					$root->appendChild($child);
						
					$child = $response_xml->createElement('Result', 'Could not remove document(s). Please contact your systems administrator.');
					$attrib = $response_xml->createAttribute('ID');
					$attrib->value = 'ErrMsg';
					$child->appendChild($attrib);
					$root->appendChild($child);
				}
				$response = new Response($response_xml->saveXML());
				$response->headers->set('Content-Type', 'text/xml');
	
				return $response;
			}
		}
	}
}