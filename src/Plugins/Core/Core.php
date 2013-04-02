<?php
namespace Plugins\Core;

use \FluxAPI\Event\ModelEvent;

class Core extends \FluxAPI\Plugin
{
    public static function register(\FluxAPI\Api $api)
    {
        parent::register($api);

        // create RESTfull webservice
        $rest = new Rest($api);

        // register listeners for models to update author, updatedAt and createdAt
        $api->on(ModelEvent::CREATE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\Core\\Model') && $model->isNew()) {
                $now = new \DateTime();
                $model->createdAt = $now;
            }
        }, \FluxAPI\Api::EARLY_EVENT);

        $api->on(ModelEvent::BEFORE_SAVE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\Core\\Model')) {
                $now = new \DateTime();
                $model->updatedAt = $now;
            }
        }, \FluxAPI\Api::EARLY_EVENT);
    }
}
