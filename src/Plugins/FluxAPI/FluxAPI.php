<?php
namespace Plugins\FluxAPI;

use \FluxAPI\Event\ModelEvent;

class FluxAPI extends \FluxAPI\Plugin
{
    public static function register(\FluxAPI\Api $api)
    {
        // do not enable REST when it's disabled in plugin.options
        if (in_array('FluxAPI/Rest',$api->config['plugin.options']['disabled'])) {
            return;
        }

        // create RESTfull webservice
        $rest = new Rest($api);

        // register listeners for models to update author, updatedAt and createdAt
        $api->on(ModelEvent::CREATE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\FluxAPI\\Model') && $model->isNew()) {
                $now = new \DateTime();
                $model->createdAt = $now;
            }
        }, \FluxAPI\Api::EARLY_EVENT);

        $api->on(ModelEvent::BEFORE_SAVE, function(ModelEvent $event) {
            $model = $event->getModel();

            if (!empty($model) && is_subclass_of($model, '\\Plugins\\FluxAPI\\Model')) {
                $now = new \DateTime();
                $model->updatedAt = $now;
            }
        }, \FluxAPI\Api::EARLY_EVENT);
    }
}
