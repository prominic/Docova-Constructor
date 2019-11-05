<?php

namespace Docova\DocovaBundle\ObjectModel;

use Docova\DocovaBundle\Entity\Libraries;
use Docova\DocovaBundle\Security\User\CustomACL;

/**
 * Back-end class to handle application acl
 * @author javad_rahimi
 */
class DocovaAcl 
{
	private $_container;
	private $application;
	private $custom_acl;
	private $security_checker;
	private $security_token;
	private $user;
	private $user_app_access;
	
	/**
	 * @param Libraries $application
	 */
	public function __construct(Libraries $application, Docova $docova_obj = null) 
	{
		if (!empty($docova_obj)) {
			$this->_container = $docova_obj->getContainer();
		}
		else {
			global $docova;
			$this->_container = $docova->getContainer();
		}
		$this->application = $application;
		$this->security_checker = $this->_container->get('security.authorization_checker');
		$this->security_token = $this->_container->get('security.token_storage');
		$this->user = $this->security_token->getToken()->getUser();
		$this->custom_acl = new CustomACL($this->_container);
		$this->user_app_access = $this->getUserAccessLevel();
	}
	
	
	public function getUserAccessLevel()
	{
	    $result = 0; //No Access
	    
	    if ($this->security_checker->isGranted('ROLE_ADMIN')) {
	        $result = 7; //Super User Admin
	        return $result;
	    }
	    
	    $security_identity = $this->user;
	    $usermasks = $this->custom_acl->getUserMasks($this->application, $security_identity);	  
	    $defaultmasks = [];
	    $groupmasks = [];
	    $checkmasks = [];
	    
	    $acl_property = $this->getAclProperty();
	    if (!empty($acl_property))
	    {
	        if(is_array($acl_property)){
	            for($i=0; $i<count($acl_property); $i++){
	                if ($acl_property[$i]->getNoAccess()){
	                    return $result; //exit early since this user is marked as having no access in ACL
	                }
	            }
	        }else{
	            if ($acl_property->getNoAccess()){
	                return $result; //exit early since this user is marked as having no access in ACL
	            }
	        }
	    }
	    
	    if(!empty($usermasks)){
	        $checkmasks = $usermasks;
	    }else{
	        $groups = $security_identity->getRoles();
	        if (!empty($groups)) {
	            foreach ($groups as $g) {
	                $tempmasks = $this->custom_acl->getGroupMasks($this->application, $g);
	                if(!empty($tempmasks)){
	                    //differentiate between role_user and other groups so that we can tell if this is a default level of access
	                    if ($g == "ROLE_USER" ){
	                        $defaultmasks =  $tempmasks;
	                    }else{
	                        $groupmasks =  array_merge($groupmasks, $tempmasks);
	                    }
	                }
	            }
	        }
	        if(!empty($groupmasks)){
	            $checkmasks = $groupmasks;
	        }elseif(!empty($defaultmasks)){
	            $checkmasks = $defaultmasks;
	        }
	    }
	    
	    if(!empty($checkmasks)){
	        if(in_array("owner", $checkmasks)){
	            $result = 6;  //Manager
	        }elseif(in_array("master", $checkmasks)){
	            $result = 5; //Designer
	        }elseif(in_array("operator", $checkmasks)){
	            $result = 4;  //Editor
	        }elseif(in_array("edit", $checkmasks)){
	            $result = 3; //Author
	        }elseif(in_array("view", $checkmasks)){
	            $result = 2;  //Reader
	        }
	  
	    }
	    
	    return $result;
	}
	
	
	/**
	 * Check if current user has super admin access
	 * 
	 * @return boolean
	 */
	public function isSuperAdmin()
	{
		return ($this->user_app_access == 7);
	}
	
	/**
	 * Check if current user has manager access
	 * 
	 * @return boolean
	 */
	public function isManager()
	{
	    return ($this->user_app_access >= 6);
	}
	
	/**
	 * Check if current user has designer access
	 * 
	 * @return boolean
	 */
	public function isDesigner()
	{	
	    return ($this->user_app_access == 5);
	}
	
	/**
	 * Check if current user has editor access
	 * 
	 * @return boolean
	 */
	public function isEditor()
	{
	    return ($this->user_app_access == 4);		
	}
	
	/**
	 * Check if current user has author access
	 * 
	 * @return boolean
	 */
	public function isAuthor()
	{
	    return ($this->user_app_access == 3);	
	}
	
	/**
	 * Check if current user has reader access
	 * 
	 * @return boolean
	 */
	public function isReader()
	{
	    return ($this->user_app_access == 2);	
	}
	
	/**
	 * Check if current user has edit rights to document
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return boolean
	 */
	public function isDocAuthor($document)
	{
	    if ($this->isEditor() || $this->isDesigner() || $this->isManager()){
			return true;
	    }else if($this->isAuthor()){
	        if (!empty($document) && $document instanceof \Docova\DocovaBundle\Entity\Documents)
	        {
//	            return $this->security_checker->isGranted('EDIT', $document);

	             $security_identity = $this->security_token->getToken()->getUser();
	             if ($this->custom_acl->isUserGranted($document, $security_identity, 'edit')){
    	             return true;
	             }
	             
	             $groups = $security_identity->getRoles();
	             if (!empty($groups)) {
    	             foreach ($groups as $g) {
                         //exclude role_user since it is too generic for the author level users   	                 
    	                 if ($g !== "ROLE_USER" && $this->custom_acl->isRoleGranted($document, $g, 'edit')){
	                       return true;
	                     }
	                 }
	             }
	        }
	    }		

		return false;
	}
	
	/**
	 * Check if current user is in document readers field
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @return boolean
	 */
	public function isDocReader($document)
	{
		if ($this->isSuperAdmin())
			return true;
		
		if (empty($this->user_app_access)) {
			return false;
		}
		
		if (!empty($document) && $document instanceof \Docova\DocovaBundle\Entity\Documents)
		{
			if ($this->security_checker->isGranted('VIEW', $document))
				return true;

		}
		return false;
	}
	
	/**
	 * Check if current user can create document in the app
	 * 
	 * @return boolean
	 */
	public function canCreateDocument()
	{
	    if ($this->isSuperAdmin()){
			return true;
	    }
		
	    $acl_property = $this->getAclProperty();
	    if (!empty($acl_property))
	    {
	        if(is_array($acl_property)){
	            for($i=0; $i<count($acl_property); $i++){
	                if (!$acl_property[$i]->getNoAccess() && $acl_property[$i]->getCreateDocument()){
                        return true;
	                }
	            }
	        }else{
	            if (!$acl_property->getNoAccess() && $acl_property->getCreateDocument()){
	                return true;
	            }
	        }
	    }
		
		return false;
	}
	
	/**
	 * Check if current user can delete document in the app
	 * 
	 * @return boolean
	 */
	public function canDeleteDocument()
	{
	    if ($this->isSuperAdmin()){
			return true;
	    }
		
	    $acl_property = $this->getAclProperty();
	    if (!empty($acl_property))
	    {
	        if(is_array($acl_property)){
	            for($i=0; $i<count($acl_property); $i++){
	                if (!$acl_property[$i]->getNoAccess() && $acl_property[$i]->getDeleteDocument()){
	                    return true;
	                }
	            }
	        }else{
	            if (!$acl_property->getNoAccess() && $acl_property->getDeleteDocument()){
	                return true;
	            }
	        }
	    }
	    
		return false;
	}
	
	/**
	 * Given a name, finds its entry in an ACL.
	 *  
	 * @param string $entry
	 * @return NULL|DocovaAclEntry
	 */
	public function getEntry($entry)
	{
		if (empty($entry)) return null;
		$acl_property = new DocovaAclEntry($this, $entry);
		return $acl_property;
	}
	
	/**
	 * Creates an entry in the ACL with the name and level that you specify
	 * 
	 * @param string $name
	 * @param integer $level
	 * @return DocovaAclEntry
	 */
	public function createAclEntry($name, $level)
	{
		$acl_entry = new DocovaAclEntry($this, $name, $level);
		$acl_entry->new = true;
		return $acl_entry;
	}
	
	/**
	 * Removes an entry from the ACL.
	 * 
	 * @param string $name
	 */
	public function removeAclEntry($name)
	{
		$acl_entry = new DocovaAclEntry($this, $name);
		$acl_entry->remove();
	}
	
	public function save()
	{
		//@todo: if it's confirmed this function should be complete otherwise the createAclEntry should be removed, too.
	}
	
	/**
	 * Get application ID
	 * 
	 * @return string
	 */
	public function getAppId()
	{
		return $this->application->getId();
	}
	
	/**
	 * Add a user/role to document authors field
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\UserAccounts|string $security_identity
	 * @param boolean $isRole
	 */
	public function addDocAuthor($document, $security_identity, $isRole = false)
	{
	    $this->custom_acl->insertObjectAce($document, $security_identity, 'edit', $isRole);
	}
	
	/**
	 * Add a user/role to document readers field
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\UserAccounts|string $security_identity
	 * @param boolean $isRole
	 */
	public function addDocReader($document, $security_identity, $isRole = false)
	{
	    $this->custom_acl->insertObjectAce($document, $security_identity, 'view', $isRole);
	}
	
	/**
	 * Remove a user/role from document readers field
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\UserAccounts|string $security_identity
	 * @param boolean $isRole
	 */
	public function removeDocReader($document, $security_identity, $isRole = false)
	{
	    $this->custom_acl->removeUserACE($document, $security_identity, 'view', $isRole);
	}
	
	/**
	 * Remove a user/role from document authors field
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 * @param \Docova\DocovaBundle\Entity\UserAccounts|string $security_identity
	 * @param string $isRole
	 */
	public function removeDocAuthor($document, $security_identity, $isRole = false)
	{
	    $this->custom_acl->removeUserACE($document, $security_identity, 'edit', $isRole);
	}
	
	/**
	 * Remove all users/roles from document authors field
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 */
	public function removeAllDocAuthors($document)
	{
	    $this->custom_acl->removeMaskACEs($document, 'edit');
	}
	
	/**
	 * Remove all users/roles from document readers field
	 * 
	 * @param \Docova\DocovaBundle\Entity\Documents $document
	 */
	public function removeAllDocReaders($document)
	{
	    $this->custom_acl->removeMaskACEs($document, 'view');
	}
	
	/**
	 * Find app ACL property record(s)
	 * 
	 * @param string $username
	 * @return NULL|\Docova\DocovaBundle\Entity\AppAcl
	 */
	private function getAclProperty($username = null)
	{
		$em = $this->_container->get('doctrine')->getManager();
		if (empty($username)){
			$user = $this->security_token->getToken()->getUser();
		}else {
			$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => $username, 'Trash' => false));
			if (empty($user)){
				$user = $em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('userNameDnAbbreviated' => $username, 'Trash' => false));
			}
			if (empty($user)){
				return null;
			}
		}
		$acl_property = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $this->application->getId(), 'userObject' => $user->getId()));
		if (empty($acl_property))
		{
			$groups = $user->getUserRoles();
			if (!empty($groups) && $groups->count() > 0)
			{
			    $acl_property_array = [];
			    $def_acl_property = null;
			    $temp_acl_property = null;
				foreach ($groups as $g)
				{				    
					$temp_acl_property = $em->getRepository('DocovaBundle:AppAcl')->findOneBy(array('application' => $this->application->getId(), 'groupObject' => $g->getId()));
					if (!empty($temp_acl_property)){
					    if($g->getRole() == "ROLE_USER"){
					        //-- default access so keep on hand in case nothing else comes up
					        $def_acl_property = $temp_acl_property;
					    }else{
				            $acl_property_array[] = $temp_acl_property;
				            $def_acl_property = null;
					    }
					}
				}
				if(empty($acl_property) && !empty($acl_property_array) && count($acl_property_array)>0){
				    $acl_property = (count($acl_property_array) == 1 ? $acl_property_array[0] : $acl_property_array);   
				}else if(empty($acl_property) && !empty($def_acl_property)){
				    //-- if no other access found other than default use default
				    $acl_property = $def_acl_property;
				}
			}
		}
		return $acl_property;
	}
}