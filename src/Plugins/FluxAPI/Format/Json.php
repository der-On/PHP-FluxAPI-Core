<?php

namespace Plugins\FluxAPI\Format;

class Json extends \FluxAPI\Format
{
    /**
     * Returns the file extension of the format.
     *
     * @return string
     */
    public static function getExtension()
    {
        return 'json';
    }

    /**
     * Returns the mime-type of the format.
     *
     * @return string
     */
    public static function getMimeType()
    {
        return  'application/json';
    }

    public static function decode($json, array $options = NULL)
    {
        $data = array();

        if (is_string($json) && !empty($json)) {
            return json_decode($json,TRUE);
        } else {
            return NULL;
        }
    }

    public static function encode($data, array $options = NULL)
    {
        if ((is_object($data) || is_array($data))) {
            return json_encode($data);
        } else {
            return NULL;
        }
    }

    public static function encodeFromModels($model_name, array $models)
    {
        $_models = array();

        foreach($models as $model) {
            $_models[] = $model->toArray();
        }

        return self::encode($_models);
    }
}