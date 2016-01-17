<?php
namespace CEP;
use \Symfony\Component\EventDispatcher\Event as RootEvent;
use \Symfony\Component\EventDispatcher\EventDispatcher as RootEventDispatcher;
use \Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use \Symfony\Component\Stopwatch\Stopwatch;

/**
 * inspired by https://blog.rsuter.com/21/
 */
require_once('Event.php');
require_once('EventContainer.php');
require_once('EventDispatcher.php');
require_once('CEP_EventHandler.php');
require_once('CEP_Interceptable.php');

class Interceptor {
	private	$_object;
	private	$_class;
	private	$_rootObject;
	private	$_eventKey=null;
	private	$traceableEventDispatcher	= null;
	private	static	$EventDispatcher	= null;
	private	static	$RawEventDispatcher	= null;
	private	static	$aListeners	= array();
	private	static	$aTraces	= array();
	public	static	$debugEvents= false;

	public function __construct($object,$aPluginClasses=null) {
		$this->_object	= $object;
		$this->_class	= get_class($object);
		$this->_eventKey= (isset($object->dispatchKey))?$object->dispatchKey:$this->_class;

		if($this->hasMethod('getInterceptorClasses')){
			$aPluginClasses = $object->getInterceptorClasses($aPluginClasses);
		}
		$this->initEventDispatcher();
		$this->initPlugins($aPluginClasses);

		if($this->hasMethod('attachDispatcher')){
			$object->attachDispatcher(self::$EventDispatcher);
		}

		self::$EventDispatcher->dispatch($this->_eventKey.'.__construct',new Event($this->_object));

		if (is_a($object, "Interceptor")){
			$this->_rootObject = $object->_rootObject;
		}else{
			$this->_rootObject = $object;
		}

		$object->intercepted = $this;
	}

	private function initEventDispatcher(){
		if(is_null(self::$EventDispatcher)){
			if(self::$debugEvents){
				self::$RawEventDispatcher	= new EventDispatcher();
				self::$EventDispatcher		= new TraceableEventDispatcher(
					self::$RawEventDispatcher,
					new Stopwatch()
				);
			}else{
				self::$EventDispatcher		= new EventDispatcher();
				self::$RawEventDispatcher	= self::$EventDispatcher;
			}
		}
	}

	public function hasMethod($method){
		return in_array($method,get_class_methods($this->_class));
	}

	private function initPlugins($aPluginClasses){
		if(is_array($aPluginClasses)){
			foreach($aPluginClasses as $listenerClass){
				if(!array_key_exists($listenerClass, self::$aListeners))
				self::$aListeners[$listenerClass] = new $listenerClass(self::$RawEventDispatcher);
			}
		}
	}

	public static function addTrace(&$eventName,&$event) {
		self::$aTraces[]=array(
			'e'	=> $eventName,
			't'	=> microtime(true)
		);
		//db(array('e'	=> $eventName,'t'	=> microtime(true)));
	}

	public function debug() {
		return array(
			'traces'			=> self::$aTraces,
			'calledListeners'	=> self::$EventDispatcher->getCalledListeners(),
			'notCalledListeners'=> self::$EventDispatcher->getNotCalledListeners(),
			'listeners'			=> self::$EventDispatcher->getListeners()
		);
	}

	public function callMethod($method, $args){
		$methodKey = $this->_eventKey.'.'.preg_replace('!^(pub_|svc_)!','',$method);

		$e = self::$EventDispatcher->dispatch($methodKey.'.pre',new Event($this->_object,$args));
		if ($e->isCancelled()){
			return self::$EventDispatcher->dispatch($methodKey.'.transform',new Event($this->_object,$e->getData()))->getData();
		}
		$result = call_user_func_array(array($this->_object, $method),$e->getData());

		return  self::$EventDispatcher->dispatch($methodKey.'.transform',new Event($this->_object,$result))->getData();
	}

	public function __isset($name) {
		return isset($this->_rootObject->$name);
	}

	public function __unset($name) {
		unset($this->_rootObject->$name);
	}

	public function __set($name, $value) {
		$this->_rootObject->$name = $value;
	}

	public function __get($name) {
		return $this->_rootObject->$name;
	}

	public function __call($method, $args) {
		if ($method[0] == "_")
			$method = substr($method, 1);

		return $this->callMethod($method, $args);
	}
}