<?php

namespace FluxAPI;


abstract class Format implements FormatInterface
{
    /**
     * @var FluxAPI\Api
     */
    protected static $_api;

    public static function setApi(\FluxAPI\Api $api)
    {
        self::$_api = $api;
    }

    public static function encodeFromModel($model_name, \FluxAPI\Model $model)
    {
        return static::encode($model->toArray());
    }

    public static function encodeFromModels($model_name, array $models)
    {
        return static::encode($models);
    }

    public static function decodeForModel($model_name, $raw)
    {
        return static::decode($raw);
    }

    public static function decodeForModels($model_name, $raw)
    {
        return static::decode($raw);
    }

    /**
     * Returns the name of the Format.
     * @return string
     */
    public static function getName()
    {
        $class_name = get_called_class();
        $parts = explode('\\',$class_name);
        return $parts[count($parts)-1];
    }
}