<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * WorkflowAssignee
 *
 * @ORM\Table(name="tb_document_workflow_assignee", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="Unique_Assignee", columns={"Workflow_Step_Id", "Assignee_Id"})})
 * @ORM\Entity
 * @UniqueEntity(fields={"DocWorkflowStep", "assignee"})
 */
class WorkflowAssignee
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Group_Member", type="boolean")
     */
    protected $groupMember;

    /**
     * @ORM\ManyToOne(targetEntity="DocumentWorkflowSteps", inversedBy="assignee")
     * @ORM\JoinColumn(name="Workflow_Step_Id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $DocWorkflowStep;

    /**
     * @ORM\ManyToOne(targetEntity="UserAccounts")
     * @ORM\JoinColumn(name="Assignee_Id", referencedColumnName="id", nullable=false)
     */
    protected $assignee;


    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set groupMember
     *
     * @param boolean $groupMember
     * @return WorkflowAssignee
     */
    public function setGroupMember($groupMember)
    {
        $this->groupMember = $groupMember;
    
        return $this;
    }

    /**
     * Get groupMember
     *
     * @return boolean 
     */
    public function getGroupMember()
    {
        return $this->groupMember;
    }

    /**
     * Set DocWorkflowStep
     *
     * @param \Docova\DocovaBundle\Entity\DocumentWorkflowSteps $docWorkflowStep
     * @return WorkflowAssignee
     */
    public function setDocWorkflowStep(\Docova\DocovaBundle\Entity\DocumentWorkflowSteps $docWorkflowStep)
    {
        $this->DocWorkflowStep = $docWorkflowStep;
    
        return $this;
    }

    /**
     * Get DocWorkflowStep
     *
     * @return \Docova\DocovaBundle\Entity\DocumentWorkflowSteps 
     */
    public function getDocWorkflowStep()
    {
        return $this->DocWorkflowStep;
    }

    /**
     * Set assignee
     *
     * @param \Docova\DocovaBundle\Entity\UserAccounts $assignee
     * @return WorkflowAssignee
     */
    public function setAssignee(\Docova\DocovaBundle\Entity\UserAccounts $assignee = null)
    {
        $this->assignee = $assignee;
    
        return $this;
    }

    /**
     * Get assignee
     *
     * @return \Docova\DocovaBundle\Entity\UserAccounts 
     */
    public function getAssignee()
    {
        return $this->assignee;
    }
}