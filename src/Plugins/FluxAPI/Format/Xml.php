<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 06.04.13
 * Time: 13:09
 * To change this template use File | Settings | File Templates.
 */

namespace Plugins\FluxAPI\Format;

class Xml extends \FluxAPI\Format
{
    /**
     * Returns the file extension of the format.
     *
     * @return string
     */
    public static function getExtension()
    {
        return 'xml';
    }

    /**
     * Returns the mime-type of the format.
     *
     * @return string
     */
    public static function getMimeType()
    {
        return 'text/xml';
    }

    public static function decode($xml, array $options = NULL)
    {
        $root = (!empty($options) && isset($options['root']))?$options['root']:NULL;

        if (is_string($xml) && !empty($xml)) {
            self::$_api->app['serializer.encoders'][1]->setRootNodeName($root);
            return self::$_api->app['serializer']->decode($xml,'xml');
        } else {
            return NULL;
        }
    }

    public static function encode($data, array $options = NULL)
    {
        $root = (!empty($options) && isset($options['root']))?$options['root']:'data';

        if ((is_object($data) || is_array($data))) {
            self::$_api->app['serializer.encoders'][1]->setRootNodeName($root);
            return self::$_api->app['serializer']->serialize($data,'xml');
        } else {
            return NULL;
        }
    }

    public static function encodeFromModel($model_name, \FluxAPI\Model $model)
    {
        return self::encode($model->toArray(),array('root'=>$model_name));
    }

    public static function encodeFromModels($model_name, array $models)
    {
        $xml = '<?xml version="1.0"?>'."\n";
        $xml .= '<'.$model_name.'s>'."\n";

        foreach($models as $_model) {
            $xml .= trim(str_replace('<?xml version="1.0"?>','',self::encodeFromModel($model_name, $_model)))."\n";
        }
        $xml .= '</'.$model_name.'s>';
        return $xml;
    }

    public static function decodeForModel($model_name, $xml)
    {
        return self::decode($xml, array('root'=>$model_name));
    }

    public static function decodeForModels($model_name, $xml)
    {
        return self::decode($xml, array('root'=>$model_name.'s'));
    }
}