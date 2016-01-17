<?php
namespace CEP;
use \Symfony\Component\EventDispatcher\Event as RootEvent;
use \Symfony\Component\EventDispatcher\EventDispatcher as RootEventDispatcher;

/**
 */
class EventDispatcher extends RootEventDispatcher{
	/**
	 * {@inheritdoc}
	 */
	public function dispatch($eventName, RootEvent $event = null){
		if (null === $event) {
			$event = new Event();
		}

		Interceptor::addTrace($eventName,$event);

		$event->setDispatcher($this);
		$event->setName($eventName);

		if ($listeners = $this->getListeners($eventName)) {
			$this->doDispatch($listeners, $eventName, $event);
		}

		return $event;
	}
}
