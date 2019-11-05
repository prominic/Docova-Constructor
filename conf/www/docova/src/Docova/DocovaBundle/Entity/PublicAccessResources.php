<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PublicAccessResources
 *
 * @ORM\Table(name="tb_public_access_resources") 
 * @ORM\Entity(repositoryClass="Docova\DocovaBundle\Entity\PublicAccessResourcesRepository")
 */
class PublicAccessResources
{
    /**
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="Source_Id", type="guid")
     */
    protected $source_id;

    /**
     * @var string
     *
     * @ORM\Column(name="Source_Type", type="string", length=100, nullable=false)
     */
    protected $Source_Type;
       
    /**
     * @var string
     *
     * @ORM\Column(name="Password_Hash", type="string", length=100, nullable=true)
     */
    protected $Password_Hash;
    
    /**
     * @var string
     *
     * @ORM\Column(name="Attachment_Names", type="text", nullable=true)
     */
    protected $AttachmentNames;
    
    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Author", referencedColumnName="id")
     */
    protected $Author;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Creation_Date", type="datetime", nullable=true)
     */
    protected $Creation_Date;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="Expiration_Date", type="datetime", nullable=true)
     */
    protected $Expiration_Date;
    
       
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	public function getSourceId() {
		return $this->source_id;
	}
	public function setSourceId($source_id) {
		$this->source_id = $source_id;
		return $this;
	}
	public function getSourceType() {
		return $this->Source_Type;
	}
	public function setSourceType($Source_Type) {
		$this->Source_Type = $Source_Type;
		return $this;
	}
	public function getAttachmentNames() {
		return $this->AttachmentNames;
	}
	public function setAttachmentNames($AttachmentNames) {
		$this->AttachmentNames = $AttachmentNames;
		return $this;
	}
	public function getPasswordHash() {
		return $this->Password_Hash;
	}
	public function setPasswordHash($Password_Hash) {
		$this->Password_Hash = $Password_Hash;
		return $this;
	}
	public function getSourceAttachments($em,$pai,$passwordHash,$attachmentsFilter=null,$attachmentKey=null){
		try{
			$sourceType = $this->getSourceType();
			$qb = $em->getRepository("DocovaBundle:AttachmentsDetails")->createQueryBuilder("att");
			$source = $this->getSource($em, $pai, $passwordHash);
			
			$attachmentNames = $this->getAttachmentNames();		
						
			if (!empty($attachmentsFilter)){
				$attachmentNames = $attachmentsFilter;
			}			
			$qb->select("att");
			
			if ($sourceType=="Document"){
				if (true==$source->getTrash())
					throw new \Exception("The requested document has been deleted.");
					
				$qb->join("att.Document","doc")
				->where('att.Document = :doc')				
				->andWhere('att.File_Size > 0')
				->setParameter('doc', $source->getId() );		
			}
			else if ($sourceType=="Folder"){				
				if (true==$source->getDel())
					throw new \Exception("The requested folder has been deleted.");

				$qb->join("att.Document","doc")
				->join("doc.folder","folder")
				->where('folder = :folder');
				
				//add the filter for the selected documents
				if (!empty($attachmentNames) && empty($attachmentsFilter)){
					$idsList = $idsList=\explode(';',$attachmentNames);
					
					//For folders the attachmentname contains both the name and the source document id
					$subQuery = "(";
					$elementCount=0;
					foreach($idsList as $nameAndId){
						$elementCount++;
						//split the attachment name from the document id
						$values = \explode("|",$nameAndId);
						$attName = $values[0];
						$docId = $values[1];
						 
						//add a condition to specify attachment by file name and target document id
						if ($elementCount==1)
							$subQuery.=" (doc.id = '".$docId."' AND att.File_Name='".$attName."')";
						//add an OR condition for each additional item
						else 
							$subQuery.=" OR (doc.id = '".$docId."' AND att.File_Name='".$attName."')";
					}					
				    $subQuery.=')';				    
				    $qb->andWhere( $subQuery );
				    
				}
								
				$qb->setParameter('folder', $source->getId() );				
			}
			
			//Ignore content from deleted documents
			$qb->andWhere('doc.Trash = :trash')
			->setParameter('trash',false);
			
			//echo "SQL: ".$qb->getQuery()->getSQL()."<br/>";
			//If file names are specified then filter on the specified names as well
			//Only do so for Document requests or folder requests where an attachments filter is provided
			if (!empty($attachmentsFilter) || $sourceType=="Document"){
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
			}
			
			//If file names are specified then filter on the attachment key as well
			$hasAttachmentKey = !empty($attachmentKey) && $attachmentKey!="";
			if ($hasAttachmentKey){
				$qb->andWhere('att.id LIKE :attachmentKey')
				->setParameter("attachmentKey",$attachmentKey."%");
			}
			
			//Filter out 0 byte files and sort by name
			$qb->andWhere('att.File_Size > 0')
			->addOrderBy('att.File_Name', 'ASC')
			->addOrderBy('att.File_Date', 'DESC');
			
			//We expect results in all cases, if no results are found throw an exception
			$result= $qb->getQuery()->getResult();
			if (!empty($result) && !empty($result[0])){
				return $result;
			}
			else
				throw new \Exception("No content found for access id: ".$pai);
		}
		catch (\Exception $e){
			throw new \Exception($e->getMessage());
		}
	}
	public function isExpired(){
		$dtExpiration = $this->getExpirationDate();
		if (empty($dtExpiration) || $dtExpiration=='')
			return false;
		
		$dt = new \DateTime();
		if ($dtExpiration->getTimestamp()<=$dt->getTimestamp()){
			return true;
		}
		else
			return false;
		
	}
	public function getSource($em,$pai,$passwordHash){
		$qb = $em->getRepository("DocovaBundle:PublicAccessResources")->createQueryBuilder("par");
		$qb->select("par")
		->where("par.id=?1")
		->setParameter(1, $pai);
		$result= $qb->getQuery()->getResult();
		if (!empty($result) && !empty($result[0])){
			$resource = $result[0];
			$resourceHash = $resource->getPasswordHash();			
			if (!empty( $resourceHash ) && $resourceHash!=$passwordHash){
				throw new \Exception("Invalid password");
			}
			$sourceType = $resource->getSourceType();
			if ($sourceType=="Document"){
				$dr = $em->getRepository("DocovaBundle:Documents");
				return $dr->find($resource->getSourceId());
			}
			else if ($sourceType=="Folder") {				
				$fr = $em->getRepository("DocovaBundle:Folders");
				return $fr->find($resource->getSourceId());
			}
		}
		else
			throw new \Exception("Invalid access id");
	}
	public function getExpirationDate() {
		return $this->Expiration_Date;
	}
	public function setExpirationDate(\DateTime $Expiration_Date) {
		$this->Expiration_Date = $Expiration_Date;
		return $this;
	}
	public function getAuthor() {
		return $this->Author;
	}
	public function setAuthor($Author) {
		$this->Author = $Author;
		return $this;
	}
	public function getCreationDate() {
		return $this->Creation_Date;
	}
	public function setCreationDate(\DateTime $Creation_Date) {
		$this->Creation_Date = $Creation_Date;
		return $this;
	}
	
	
    
    
}
