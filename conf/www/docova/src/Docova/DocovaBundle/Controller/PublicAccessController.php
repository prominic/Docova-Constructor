<?php

namespace Docova\DocovaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
//use Docova\DocovaBundle\Entity\UserAccounts;
//use Docova\DocovaBundle\Entity\UserProfile;
//use Docova\DocovaBundle\Entity\UserPanels;
//use Docova\DocovaBundle\Entity\PanelWidgets;
use Docova\DocovaBundle\Entity\PublicAccessResources;
//use Docova\DocovaBundle\Security\User\CustomACL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicAccessController extends Controller
{
	protected $user;
	protected $global_settings;
	
	private function initialize()
	{
		$securityContext = $this->container->get('security.token_storage');
		$this->user = $securityContext->getToken()->getUser();
		
		$this->global_settings = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:GlobalSettings')->findAll();
		$this->global_settings = $this->global_settings[0];
	}
	private function canAccessDocument($document,$aclCheck=null){
		if ($aclCheck==null)
			$aclCheck = new Miscellaneous($this->container);
		return $aclCheck->canReadDocument($document);
	}
	private function canViewFolder($folder,$aclCheck=null){		
		return (true === $this->container->get('security.authorization_checker')->isGranted('VIEW', $folder) || true === $this->container->get('security.authorization_checker')->isGranted('ROLE_ADMIN') || true === $this->container->get('security.authorization_checker')->isGranted('MASTER', $folder->getLibrary()));
	}
	private function canAccessFolder($folder,$aclCheck=null){
		if ($aclCheck==null)
			$aclCheck = new Miscellaneous($this->container);
		return ($aclCheck->isFolderVisible($folder) && $this->canViewFolder($folder));
	}
	
	private function verifySourceAccess($sourceId,$sourceType,$aclCheck=null){
		//Verify that the author has access to the source folder or document being shared
		if ($sourceType=="Document"){
			$dr = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Documents');
			$document=$dr->find($sourceId);
			if (empty($document)){
				return new Response("Error:  The source document that you are attempting to share does not exist.");
			}
			if ($this->canAccessDocument($document,$aclCheck)==false){
				return new Response("Authorization Error:  You are not authorized to access the source document that you are attempting to share.");
			}
		}
		else if ($sourceType=="Folder"){
			$fr = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:Folders');
			$folder=$fr->find($sourceId);
			if (empty($folder)){
				return new Response("Error:  The source folder that you are attempting to share does not exist.");
			}
			if ($this->canAccessFolder($folder,$aclCheck)==false){
				return new Response("Authorization Error:  You are not authorized to access the source folder that you are attempting to share.");
			}
		}
		return null;
	}
	public function publicAccessAdminAction(Request $request){
		$this->initialize();
		
		if ($request->isMethod('GET') ) {			
			return new Response("No user interface is available for public access administration!");
		}
		else if ($request->isMethod('POST') ) {
			//Process the post action
			$action = $request->request->get("Action");
			switch ($action){
				case "createPAP":										
					return $this->createPAP($request->request);					
					break;
				
			}
		}
	}
	
	private function createPAP($request){
		$sourceId = $request->get("Source_Id");
		if (empty($sourceId)){
			return new Response("Error: The id of the source document of folder being shared must be provided");
		}
		//if a full link is provided truncate to the last 36 characters
		$idLen = \strlen($sourceId);
		if ($idLen>36){
			$sourceId = \substr($sourceId, $idLen-36, 36);
		}
		$sourceType = $request->get("Source_Type");
		if (empty($sourceType)){
			return new Response("Error: The type of item being shared (Folder or Document) must be provided");
		}
			
		//Make sure that the current user has access to the folder or document that they are attempting to share
		$securityCheck = $this->verifySourceAccess($sourceId,$sourceType);
		if (!empty($securityCheck)){
			return $securityCheck;
		}
		
		//Collect the rest of the optional post details
		$sourcePassword = $request->get("Source_Password");
		$expirationDate = $request->get("Expiration_Date");
		$attachmentNames = $request->get("Attachment_Names");
			
		//Create a new public access resource record from the post
		$par = new PublicAccessResources();
		$par->setSourceId($sourceId);
		$par->setSourceType($sourceType);
			
		if (!empty($sourcePassword))
			$par->setPasswordHash(md5(md5(md5($sourcePassword))));
		
		if (!empty($expirationDate) && $expirationDate!=""){
			//"Y-m-d H:i:s",
			$timeStamp = \strtotime($expirationDate." 00:00:00");
			$dtExpiration = new \DateTime();
			$dtExpiration->setTimestamp($timeStamp);
			$par->setExpirationDate($dtExpiration);
		}
			
		if (!empty($attachmentNames)){
			$par->setAttachmentNames($attachmentNames);
		}
			
		$par->setCreationDate(new \DateTime());
		$par->setAuthor($this->user);
			
		//Save and refresh the public access resource record
		$this->getDoctrine()->getManager()->persist($par);
		$this->getDoctrine()->getManager()->flush();
		$this->getDoctrine()->getManager()->refresh($par);
			
		//Return the public access id
		return new Response($par->getId());
	}
	public function publicAccessAction(Request $request){
		$publicResource = null;
		$pai=null;
		if ($request->isMethod('GET') ) {
			$pai = $request->query->get("PublicAccessID");  //public access id
			if (!empty($pai)){
				//Obtain the related public access resource record
				$par = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:PublicAccessResources');
				if ($par->isPublic($pai)){
					$publicResource = $par->getPublicAccessResource($pai,null);
				}
			}
		}
				 
		if ($request->isMethod('POST') || !empty($publicResource)) {			
			$pai = (empty($pai) ? $request->request->get("inputAccessID") : $pai);  //public access id			
			$pap = $request->request->get("inputPassword");  //public access password
			$pat = $request->request->get("inputToken");     //public access token (password hash)
			$paf = $request->request->get("inputFileName");  //public access file download
			$pafk = $request->request->get("inputFileKey");  //public access file download key
		
			try {
				//Obtain the related public access resource record
				$par = $this->getDoctrine()->getManager()->getRepository('DocovaBundle:PublicAccessResources');

				if (!empty($publicResource))
					$resource = $publicResource;				
				else
					$resource = $par->getPublicAccessResource($pai);
				
				if (empty($resource)){
					throw new \Exception("Invalid public access id");	
				}
				
				//Check for resource expiration
				if ($resource->isExpired()){
					throw new \Exception("The resource that you have requested has expired and is no longer available for public access.  Please contact the sender to request a new public access link.");
				}
				
				//Compute the password hash if necessary
				if (!empty($pap))	
					$paph = md5(md5(md5($pap)));
				else if (!empty($pat))
					$paph = $pat;					
				else
					$paph=null;
								
				if (!empty($paf)){	
					if ($paf=="ZIP"){
						//Download all of the files
						$attachments = $resource->getSourceAttachments($this->getDoctrine()->getManager(),$pai,$paph);
						return $this->downloadZip($attachments);
					}
					else{	
						//Download the selected file
						$selectedAttachments = $resource->getSourceAttachments($this->getDoctrine()->getManager(),$pai,$paph,$paf,$pafk);
						return $this->downloadDocumentAttachment($selectedAttachments[0]);
					}
					
				}  
				
				//Display the list of files available for download
				return $this->render('DocovaBundle:Form:PublicAccessResource.html.twig', array(
				'user' => $this->user,
				'settings' => $this->global_settings,				
				'token'	=> $paph,
				'attachments' => (empty($resource) ? null : $resource->getSourceAttachments($this->getDoctrine()->getManager(),$pai,$paph) ),							
				'accessId' => $pai		
				));
					
			} 
			catch (\Exception $e) {
				return $this->render('DocovaBundle:Form:PublicAccessResource.html.twig', array(
						'user' => $this->user,
						'settings' => $this->global_settings,
						'errorMessage' => $e->getMessage()
				));
			}			
		}
		else if ( $request->isMethod('GET') ) {
			//Diplay the authentication dialog
			return $this->render('DocovaBundle:Form:PublicAccessResource.html.twig', array(
					'user' => $this->user,
					'settings' => $this->global_settings
			));
		}
		
	}

	public function publicAccessSEAction()
	{
		$this->initialize();
		return $this->render('DocovaBundle:Form:PublicAccessSE.html.twig', array(
			'user' => $this->user,
			'settings' => $this->global_settings
		));
	}

	public function opendPublicAccessSettingsAction(Request $request)
	{		
		$folderOrDocumentId = $request->query->get('ParentUNID');
		$sourceType = $request->query->get('SourceType');
		
		$selectedDocIds = $request->query->get('SelectedDocIds');		
		$this->initialize();
		$em = $this->getDoctrine()->getManager();	
		$source = $this->getSourceDocumentOrFolder($em, $folderOrDocumentId, $sourceType);
		
		$access_check = new Miscellaneous($this->container);
				
		//Make sure that the current user has access to the folder or document that they are attempting to share
		$securityCheck = $this->verifySourceAccess($folderOrDocumentId,$sourceType,$access_check);
		if (!empty($securityCheck)){
			return $securityCheck;
		}
				
        $attachments = $this->getSourceAttachments($em, $source, $sourceType);
		return $this->render('DocovaBundle:Default:dlgPublicAccessSettings.html.twig', array(
				'settings' => $this->global_settings,
				'user' => $this->user,
				'source' => $source,
				'sourceType' => $sourceType,				
				'attachments' => (empty($selectedDocIds)) ? $attachments : $this->getSourceAttachments($em, $source, $sourceType,$selectedDocIds)
		
		));
	
	}
	
	private function getSourceDocumentOrFolder($em,$id,$sourceType){
		if ($sourceType=="Document"){
			$source = $em->find("DocovaBundle:Documents",$id);
		}
		else{
			$source = $em->find('DocovaBundle:Folders', $id);
		}
		return $source;
	}
	
	private function getSourceAttachments($em,$source,$sourceType,$selectedDocIds=null){
		try{			
			$qb = $em->getRepository("DocovaBundle:AttachmentsDetails")->createQueryBuilder("att");
			
						
			$qb->select("att");
				
			if ($sourceType=="Document"){
				if (true==$source->getTrash())
					throw new \Exception("The requested document has been deleted.");
					
				$qb->join("att.Document","doc")
				->where('att.Document = :doc')
				->andWhere('att.File_Size > 0')
				->setParameter('doc', $source->getId() );

				//Ignore content from deleted documents
				$qb->andWhere('doc.Trash = :trash')
				->setParameter('trash',false);
			}
			else if ($sourceType=="Folder"){
				if (true==$source->getDel())
					throw new \Exception("The requested folder has been deleted.");
	
				$qb->join("att.Document","doc")
				->join("doc.folder","folder")
				->where('folder = :folder')
				->setParameter('folder', $source->getId() );

				//Ignore content from deleted documents
				$qb->andWhere('folder.Del = false')
				->andWhere('doc.Trash = false');
			}

			//add the filter for the selected documents
			if (!empty($selectedDocIds)){
				if (\is_array($selectedDocIds)){
					$qb->andWhere($qb->expr()->in('doc.id', $selectedDocIds));				
				}
				else{															
					$qb->andWhere($qb->expr()->in('doc.id', \explode(',',$selectedDocIds)));
				}
				
			}
			
			/*
			//If file names are specified then filter on the specified names as well
			$hasNameFilter = !empty($attachmentNames) && $attachmentNames!="";
			if ($hasNameFilter){
				$attachmentList = explode(';',$attachmentNames );
				$altAttachmentList = null;
				//if any of the file names contain single quotes also search for #39;
				if (false!==\strpos($attachmentNames, "'")){
					foreach($attachmentList as $attachmentItem){
						if (false!==\strpos($attachmentItem, "'")){
							$altAttachmentList[] = \str_replace("'","&#39;", $attachmentItem);
						}
					}
				}
	
				//Add the primary attachment name filters
				$qb->andWhere($qb->expr()->in('att.File_Name', $attachmentList ));
					
				//add the attachment name alternate filters if any
				if (!empty($altAttachmentList)){
					$qb->orWhere($qb->expr()->in('att.File_Name', $altAttachmentList));
				}
			}
				
			//If file names are specified then filter on the attachment key as well
			$hasAttachmentKey = !empty($attachmentKey) && $attachmentKey!="";
			if ($hasAttachmentKey){
				$qb->andWhere('att.id LIKE :attachmentKey')
				->setParameter("attachmentKey",$attachmentKey."%");
			}
			*/
				
			//Filter out 0 byte files and sort by name
			$qb->andWhere('att.File_Size > 0')
			->addOrderBy('att.File_Name', 'ASC')
			->addOrderBy('att.File_Date', 'DESC');
				
			//We do not expect results in all cases
			$result= $qb->getQuery()->getResult();
			if (!empty($result) && !empty($result[0])){
				return $result;
			}
			else
				return null;
		}
		catch (\Exception $e){
			throw new \Exception($e->getMessage());
		}
	}
	private function downloadZip($attachments){
		$zip = new \ZipArchive();
		$filename = tempnam("/tmp","");		
		if ($zip->open($filename, \ZipArchive::CREATE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}
		
		foreach ($attachments as $attachment){
			$id = $attachment->getDocument()->getId();
			$file_path = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT'];
			$file_path = $file_path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
			$file_path = $file_path . DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.md5($attachment->getFileName());
            if (\file_exists($file_path))
                $fileName = $attachment->getFileName();                
                //The file names may be duplicated, create unique names in the zip if necessary
                if ( false!==$zip->locateName($fileName)) {
                	$copyCount=0;
                	$hasExt = \strrchr($fileName,".");
                	if ($hasExt!==false){
                		$ext = $hasExt;
                	}
                	else
                		$ext = "";
                	
                	$fileNameOnly = \substr($fileName,0,\strlen($fileName)-\strlen($ext));
                	$entryLimit = 100;
                	
                	$zipFile = $fileName;                
                    do{	
                    	$copyCount++;
                    	$zipFile = $fileNameOnly . " (".$copyCount.")".$ext;	                		                	
	                }
	                while (false!==$zip->locateName($zipFile) && $copyCount<=$entryLimit);
	                $zip->addFile($file_path,$zipFile);
                }
                else	
					$zip->addFile($file_path,$attachment->getFileName());
		}
		
		$zip->close();		
		$dt = new \DateTime();
		return $this->downloadFile($filename,"application/zip","DOCOVA_Documents_".$dt->format("Y-m-d_H-i-s").".zip");
	}
	private function downloadFile($file_path,$file_type,$file_name){
		$response = new StreamedResponse();
		$response->setCallback(function() use($file_path) {
			$handle = fopen($file_path, 'r');
			while (!feof($handle)) {
				$buffer = fread($handle, 1024);
				echo $buffer;
				flush();
			}
			fclose($handle);
		});
		$headers = array(
				'Content-Type' => $file_type,
				'Content-Disposition' => 'inline; filename="'.$file_name.'"',
		);
		$response->headers->add($headers);
		$response->setStatusCode(200);
		return $response;
	}
	private function downloadAttachment($file,$file_path){
		return $this->downloadFile($file_path, $file->getFileMimeType(), $file->getFileName());				
	}
	private function downloadDocumentAttachment($file){
		$file_path = $this->container->getParameter('document_root') ? $this->container->getParameter('document_root') : $_SERVER['DOCUMENT_ROOT'];
		$file_path = $file_path.DIRECTORY_SEPARATOR.'Docova'.DIRECTORY_SEPARATOR.'_storage';
		if (file_exists($file_path.DIRECTORY_SEPARATOR.$file->getDocument()->getId().DIRECTORY_SEPARATOR.md5($file->getFileName())))
		{		
			$file_path = $file_path . DIRECTORY_SEPARATOR.$file->getDocument()->getId().DIRECTORY_SEPARATOR.md5($file->getFileName());
			return $this->downloadAttachment($file,$file_path);			
		}
		else {
			throw new \Exception('File does not exist.');
		}
	}
}