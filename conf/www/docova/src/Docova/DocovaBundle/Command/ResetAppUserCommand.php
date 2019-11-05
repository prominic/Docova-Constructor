<?php

namespace Docova\DocovaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to reset all user IDs in migrated app to DOCOVA SE user
 * @author javad_rahimi
 */
class ResetAppUserCommand extends ContainerAwareCommand 
{
	private $_em;
	private $_application;
	private $_user;
	
	protected function configure()
	{
		$this->setName('docova:resetappuser')
			->setDescription('Reset all user IDs in migrated app.')
			->addArgument('application', InputArgument::REQUIRED, 'application ID/Name');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$app = $input->getArgument('application');
		$this->_em = $this->getContainer()->get('doctrine')->getManager();
		$this->_application = $this->_em->getRepository('DocovaBundle:Libraries')->findOneBy(array('id' => $app, 'Trash' => false, 'isApp' => true));
		if (empty($this->_application))
		{
			$this->_application = $this->_em->getRepository('DocovaBundle:Libraries')->getByName($app);
		}
		
		if (empty($this->_application) || !$this->_application->getIsApp()) {
			$output->writeln('Unspecified application ID or name.');
			return false;
		}
		
		$this->_user = $this->_em->getRepository('DocovaBundle:UserAccounts')->findOneBy(array('username' => 'DOCOVA SE', 'Trash' => false));
		if (empty($this->_user)) {
			$output->writeln('Cannot find DOCOVA SE user to rest selected app user IDs.');
			return false;
		}
		
		try {
			if (false === $count = $this->resetAppAcl()) {
				$output->writeln('Nothing to reset App ACL.');
			}
			else {
				$output->writeln($count . ' App ACL records are reset.');
			}
			if (false === $count = $this->resetAppAgents()) {
				$output->writeln('Nothing to reset App Agents.');
			}
			else {
				$output->writeln($count . ' App Agents records are reset.');
			}
			if (false === $count = $this->resetAppCss()) {
				$output->writeln('Nothing to reset App CSS.');
			}
			else {
				$output->writeln($count . ' App CSS records are reset.');
			}
			if (false === $count = $this->resetAppFiles()) {
				$output->writeln('Nothing to reset App Files.');
			}
			else {
				$output->writeln($count . ' App Files records are reset.');
			}
			if (false === $count = $this->resetAppForms()) {
				$output->writeln('Nothing to reset App Forms.');
			}
			else {
				$output->writeln($count . ' App Forms records are reset.');
			}
			/*
			 * COMMENTED OUT BY JAVAD FOR NOW.
			 * Right now all form properties's Modified_By field is set to NULL and there is no call to change it in back-end code
			 * Maybe in future we need to take out the field because form property cannot be changed without the form, so we can rely on form Modified_By instead.
			 * 
			if (false === $count = $this->resetAppFormProperties()) {
				$output->writeln('Nothing to reset App Form Properties.');
			}
			else {
				$output->writeln($count . ' App Form Properties records are reset.');
			}
			*/
			if (false === $count = $this->resetAppJs()) {
				$output->writeln('Nothing to reset App JavaScripts.');
			}
			else {
				$output->writeln($count . ' App JavaScripts records are reset.');
			}
			if (false === $count = $this->resetAppLayouts()) {
				$output->writeln('Nothing to reset App Layouts.');
			}
			else {
				$output->writeln($count . ' App Layouts records are reset.');
			}
			if (false === $count = $this->resetAppMenus()) {
				$output->writeln('Nothing to reset App Menus/Outlines.');
			}
			else {
				$output->writeln($count . ' App Menus/Outlines records are reset.');
			}
			if (false === $count = $this->resetAppPages()) {
				$output->writeln('Nothing to reset App Pages.');
			}
			else {
				$output->writeln($count . ' App Pages records are reset.');
			}
			if (false === $count = $this->resetAppPhp()) {
				$output->writeln('Nothing to reset App PHP Scripts.');
			}
			else {
				$output->writeln($count . ' App PHP Scripts records are reset.');
			}
			if (false === $count = $this->resetAppSubforms()) {
				$output->writeln('Nothing to reset App Suborms.');
			}
			else {
				$output->writeln($count . ' App Suborms records are reset.');
			}
			if (false === $count = $this->resetAppViews()) {
				$output->writeln('Nothing to reset App Views.');
			}
			else {
				$output->writeln($count . ' App Views records are reset.');
			}
			if (false === $count = $this->resetDesignElements()) {
				$output->writeln('Nothing to reset App Design Elements.');
			}
			else {
				$output->writeln($count . ' App Design Elements records are reset.');
			}

			$output->writeln('Applicatoin User ID reset is done successfully.');
		}
		catch (\Exception $e) {
			$output->writeln('Oops! Something went wrong: '. $e->getMessage());
			return false;
		}
	}
	
	/**
	 * Reset App ACL table
	 * 
	 * @return boolean
	 */
	private function resetAppAcl()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppAcl')->createQueryBuilder('A')
			->update()
			->set('A.userObject', ':newuser')
			->where('A.application = :app')
			->andWhere('A.userObject IS NOT NULL')
			->setParameter('newuser' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();

		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Agents
	 * 
	 * @return boolean
	 */
	private function resetAppAgents()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppAgents')->createQueryBuilder('A')
			->update()
			->set('A.createdBy', ':creator')
			->set('A.modifiedBy', ':modifier')
			->where('A.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Css
	 * 
	 * @return boolean
	 */
	private function resetAppCss()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppCss')->createQueryBuilder('C')
			->update()
			->set('C.createdBy', ':creator')
			->set('C.modifiedBy', ':modifier')
			->where('C.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Forms
	 * 
	 * @return boolean
	 */
	private function resetAppForms()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppForms')->createQueryBuilder('F')
			->update()
			->set('F.createdBy', ':creator')
			->set('F.modifiedBy', ':modifier')
			->where('F.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Form Properties
	 * 
	 * @return boolean
	 */
	private function resetAppFormProperties()
	{
		$forms = $this->_em->getRepository('DocovaBundle:AppForms')->createQueryBuilder('F')
			->select('id')
			->where('F.application = :app')
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->getArrayResult();
		
		if (!empty($forms[0]))
		{
			foreach ($forms as $f)
			{
				$res = $this->_em->getRepository('DocovaBundle:AppFormProperties')->createQueryBuilder('P')
					->update()
					->set('P.modifiedBy', ':modifier')
					->where('P.appForm = :form')
					->setParameter('modifier' , $this->_user->getId())
					->setParameter('form', $f['id'])
					->getQuery()
					->execute();
			}
		}
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App JavaScript
	 * 
	 * @return boolean
	 */
	private function resetAppJs()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppJavaScripts')->createQueryBuilder('J')
			->update()
			->set('J.createdBy', ':creator')
			->set('J.modifiedBy', ':modifier')
			->where('J.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Layouts
	 * 
	 * @return boolean
	 */
	private function resetAppLayouts()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppLayout')->createQueryBuilder('L')
			->update()
			->set('L.createdBy', ':creator')
			->set('L.modifiedBy', ':modifier')
			->where('L.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Menus
	 * 
	 * @return boolean
	 */
	private function resetAppMenus()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppOutlines')->createQueryBuilder('M')
			->update()
			->set('M.createdBy', ':creator')
			->set('M.modifiedBy', ':modifier')
			->where('M.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Pages
	 * 
	 * @return boolean
	 */
	private function resetAppPages()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppPages')->createQueryBuilder('P')
			->update()
			->set('P.createdBy', ':creator')
			->set('P.modifiedBy', ':modifier')
			->where('P.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Php(Script Libraries)
	 * 
	 * @return boolean
	 */
	private function resetAppPhp()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppPhpScripts')->createQueryBuilder('S')
			->update()
			->set('S.createdBy', ':creator')
			->set('S.modifiedBy', ':modifier')
			->where('S.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Views
	 * 
	 * @return boolean
	 */
	private function resetAppViews()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppViews')->createQueryBuilder('V')
			->update()
			->set('V.createdBy', ':creator')
			->set('V.modifiedBy', ':modifier')
			->where('V.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Files
	 * 
	 * @return boolean
	 */
	private function resetAppFiles()
	{
		$res = $this->_em->getRepository('DocovaBundle:AppFiles')->createQueryBuilder('F')
			->update()
			->set('F.createdBy', ':creator')
			->set('F.modifiedBy', ':modifier')
			->where('F.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset App Subforms
	 * 
	 * @return boolean
	 */
	private function resetAppSubforms()
	{
		$res = $this->_em->getRepository('DocovaBundle:Subforms')->createQueryBuilder('S')
			->update()
			->set('S.Creator', ':creator')
			->set('S.Modified_By', ':modifier')
			->where('S.application = :app')
			->setParameter('creator' , $this->_user->getId())
			->setParameter('modifier' , $this->_user->getId())
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->execute();
		
		if (!empty($res))
			return true;
		
		return false;
	}
	
	/**
	 * Reset design elements
	 * 
	 * @return boolean
	 */
	private function resetDesignElements()
	{
		$ids = array();
		$forms = $this->_em->getRepository('DocovaBundle:AppForms')->createQueryBuilder('F')
			->select ('F.id')
			->where('F.application = :app')
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->getArrayResult();
		
		if (!empty($forms[0]))
		{
			foreach ($forms as $f) {
				$ids[] = $f['id'];
			}
		}
		
		$subforms = $this->_em->getRepository('DocovaBundle:Subforms')->createQueryBuilder('S')
			->select ('S.id')
			->where('S.application = :app')
			->setParameter('app', $this->_application->getId())
			->getQuery()
			->getArrayResult();
		
		if (!empty($subforms[0]))
		{
			foreach ($subforms as $s) {
				$ids[] = $s['id'];
			}
		}
		
		if (!empty($ids))
		{
			$res = $this->_em->getRepository('DocovaBundle:DesignElements')->createQueryBuilder('E')
				->update()
				->set('E.modifiedBy', ':modifier')
				->where("E.form IN ('".implode("','", $ids)."') OR E.Subform IN ('". implode("','", $ids) ."')")
				->setParameter('modifier' , $this->_user->getId())
				->getQuery()
				->execute();
		}
		
		if (!empty($res))
			return true;
		
		return false;
	}
}