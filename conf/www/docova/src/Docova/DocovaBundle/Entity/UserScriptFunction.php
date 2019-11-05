<?php

namespace Docova\DocovaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserScriptFunction
 *
 * @ORM\Table(name="tb_user_script_function")
 * @ORM\Entity
 */
class UserScriptFunction
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
     * @ORM\Column(name="Function_Name", type="string", length=50)
     */
    protected $Function_Name;

    /**
     * @var string
     *
     * @ORM\Column(name="Function_Arguments", type="string", length=255)
     */
    protected $Function_Arguments = '$document_context';

    /**
     * @var string
     *
     * @ORM\Column(name="Function_Script", type="text")
     */
    protected $Function_Script;

    /**
     * @var string
     *
     * @ORM\Column(name="Return_Type", type="string", length=50, nullable=true)
     */
    protected $Return_Type;


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
     * Set Function_Name
     *
     * @param string $functionName
     * @return UserScriptFunction
     */
    public function setFunctionName($functionName)
    {
        $this->Function_Name = $functionName;
    
        return $this;
    }

    /**
     * Get Function_Name
     *
     * @return string 
     */
    public function getFunctionName()
    {
        return $this->Function_Name;
    }

    /**
     * Set Function_Arguments
     *
     * @param string $functionArguments
     * @return UserScriptFunction
     */
    public function setFunctionArguments($functionArguments)
    {
        $this->Function_Arguments = $functionArguments;
    
        return $this;
    }

    /**
     * Get Function_Arguments
     *
     * @return string 
     */
    public function getFunctionArguments()
    {
        return $this->Function_Arguments;
    }

    /**
     * Set Function_Script
     *
     * @param string $functionScript
     * @return UserScriptFunction
     */
    public function setFunctionScript($functionScript)
    {
        $this->Function_Script = $functionScript;
    
        return $this;
    }

    /**
     * Get Function_Script
     *
     * @return string 
     */
    public function getFunctionScript()
    {
        return $this->Function_Script;
    }

    /**
     * Set Return_Type
     *
     * @param string $returnType
     * @return UserScriptFunction
     */
    public function setReturnType($returnType)
    {
        $this->Return_Type = $returnType;
    
        return $this;
    }

    /**
     * Get Return_Type
     *
     * @return string 
     */
    public function getReturnType()
    {
        return $this->Return_Type;
    }
}
