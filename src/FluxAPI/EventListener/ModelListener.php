<?php

namespace FluxAPI\EventListener;

use \FluxAPI\Event\ModelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ModelListener implements EventSubscriberInterface
{
    protected $_api;

    public function __construct(\FluxAPI\Api $api)
    {
        $this->_api = $api;
    }

    public function onModelBeforeCreate(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelCreate(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelBeforeLoad(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelLoad(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelBeforeUpdate(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelUpdate(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelBeforeSave(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelSave(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelBeforeDelete(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public function onModelDelete(\FluxAPI\Event\ModelEvent $event)
    {

    }

    public static function getSubscribedEvents()
    {
        return array(
            ModelEvent::BEFORE_CREATE  => array('onModelBeforeCreate', -1024),
            ModelEvent::CREATE  => array('onModelCreate', -1024),
            ModelEvent::BEFORE_LOAD  => array('onModelBeforeLoad', -1024),
            ModelEvent::LOAD  => array('onModelLoad', -1024),
            ModelEvent::BEFORE_UPDATE  => array('onModelBeforeUpdate', -1024),
            ModelEvent::UPDATE  => array('onModelUpdate', -1024),
            ModelEvent::BEFORE_SAVE  => array('onModelBeforeSave', -1024),
            ModelEvent::SAVE  => array('onModelSave', -1024),
            ModelEvent::BEFORE_DELETE  => array('onModelBeforeDelete', -1024),
            ModelEvent::DELETE  => array('onModelDelete', -1024),
        );
    }
}