<?php

namespace Docova\DocovaBundle\ObjectModel;

/**
 * Back-end class to represent a date and time. Supports most methodologies of Lotus Script and Domino
 * @author javad_rahimi
 *        
 */
class DocovaDateTime extends \DateTime 
{
	private $format = 'm/d/Y H:i:s A';
	public $DateOnly = null;
	public $IsValidDate = false;
	public $LocalTimeZone = null;
	public $TimeOnly = null;
	
	
	public function __construct($datetime, $default_format = '')
	{
		parent::__construct($datetime);
		
		if (empty($default_format))
			$this->DateOnly = $this->format('m/d/Y');
		else 
			$this->DateOnly = $this->format($this->date_format);
	
		$this->TimeOnly = $this->format('H:i:s A');
		$this->LocalTimeZone = $this->format('m/d/Y H:i:s A');
		$this->IsValidDate = true;
	}
	
	/**
	 * Increments a date-time by the number of days you specify.
	 * 
	 * @param integer $number
	 */
	public function adjustDay($number)
	{
		$number = intval($number) < 0 ? '-'.strval(abs($number)) : '+'.strval($number);
		$number .= ' day';
		$this->modify($number);
	}
	
	/**
	 * Increments a date-time by the number of hours you specify.
	 * 
	 * @param integer $number
	 */
	public function adjustHour($number)
	{
		if (false === $this->hasTime())
			return;
		
		$number = intval($number) < 0 ? '-'.strval(abs($number)) : '+'.strval($number);
		$number .= ' hour';
		$this->modify($number);
	}
	
	/**
	 * Increments a date-time by the number of minutes you specify.
	 * 
	 * @param integer $number
	 */
	public function adjustMinute($number)
	{
		if (false === $this->hasTime())
			return;
		
		$number = intval($number) < 0 ? '-'.strval(abs($number)) : '+'.strval($number);
		$number .= ' minute';
		$this->modify($number);
	}
	
	/**
	 * Increments a date-time by the number of months you specify.
	 * 
	 * @param integer $number
	 */
	public function adjustMonth($number)
	{
		$number = intval($number) < 0 ? '-'.strval(abs($number)) : '+'.strval($number);
		$number .= ' month';
		$this->modify($number);
	}
	
	/**
	 * Increments a date-time by the number of seconds you specify.
	 * 
	 * @param integer $number
	 */
	public function adjustSecond($number)
	{
		if (false === $this->hasTime())
			return;
		
		$number = intval($number) < 0 ? '-'.strval(abs($number)) : '+'.strval($number);
		$number .= ' second';
		$this->modify($number);
	}
	
	/**
	 * Increments a date-time by the number of years you specify.
	 * 
	 * @param integer $number
	 */
	public function adjustYear($number)
	{
		$number = intval($number) < 0 ? '-'.strval(abs($number)) : '+'.strval($number);
		$number .= ' year';
		$this->modify($number);
	}
	
	/**
	 * Sets the value of a date-time to now (today's date and current time).
	 */
	public function setNow()
	{
		$now = new \DateTime();
		parent::setDate($now->format('Y'), $now->format('m'), $now->format('d'));
		parent::setTime($now->format('H'), $now->format('i'), $now->format('s'));
	}
	
	/**
	 * Finds the difference in seconds between one date-time and another.
	 * 
	 * @param \DateTime $dateTime
	 * @return number
	 */
	public function timeDifference($dateTime) 
	{
	    $interval = ($this->getTimestamp() - $dateTime->getTimestamp());
		return $interval;
	}
	
	/**
	 * An alias for timeDifference (is implemented for migration purposes)
	 * 
	 * @param \DateTime $dateTime
	 * @return number
	 */
	public function timeDifferenceDouble($dateTime)
	{
		return $this->timeDifference($dateTime);
	}
	
	/**
	 * Check if time section exists in DateTime object
	 * 
	 * @return boolean
	 */
	private function hasTime()
	{
		$value = $this->format('h:i:s');
		if ($value == '00:00:00')
			return false;
		
		return true;
	}
}