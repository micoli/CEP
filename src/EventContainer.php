<?php
namespace CEP;
use \Symfony\Component\EventDispatcher\EventDispatcher as RootEventDispatcher;

class_exists('Addendum');

/**
 */
class EventContainer{
	public function __construct(RootEventDispatcher $EventDispatcher){
		$reflection = new \ReflectionAnnotatedClass(get_class ($this));
		foreach($reflection->getMethods() as $method){
			if($method->hasAnnotation('CEP_EventHandler')){
				$annotation = $method->getAnnotation('CEP_EventHandler');
				$EventDispatcher->addListener(
					$annotation->event,
					array($method->class,$method->name),
					$annotation->priority
				);
			}
		}
	}
}