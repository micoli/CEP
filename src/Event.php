<?php
namespace CEP;

/**
 */
class Event extends \Symfony\Component\EventDispatcher\Event{
	protected $context;
	protected $data;
	protected $additionalData;
	protected $cancelled = false;

	public function __construct($context=null,$data=null,$additionalData=null){
		$this->context			= $context;
		$this->data				= $data;
		$this->additionalData	= $additionalData;
	}

	public function getData(){
		return $this->data;
	}

	public function setData($data=null){
		$this->data = $data;
	}

	public function setContext($context=null){
		$this->context = $context;
	}

	public function getContext(){
		return $this->context;
	}

	public function getAdditionalData(){
		return $this->additionalData;
	}

	public function setAdditionalData($additionalData=null){
		$this->additionalData = $additionalData;
	}

	public function setCancelled($cancelled=true){
		$this->cancelled=$cancelled;
		$this->stopPropagation();
	}

	public function isCancelled(){
		return (boolean)$this->cancelled;
	}
}