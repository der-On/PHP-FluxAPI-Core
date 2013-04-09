<?php

namespace Plugins\FluxAPI\Format;

class Yaml extends \FluxAPI\Format
{
    /**
     * Returns the file extension of the format.
     *
     * @return string
     */
    public static function getExtension()
    {
        return 'yaml';
    }

    /**
     * Returns the mime-type of the format.
     *
     * @return string
     */
    public static function getMimeType()
    {
        return 'text/yaml';
    }

    public static function decode($yaml, array $options = NULL)
    {
        if (is_string($yaml) && !empty($yaml)) {
            $parser = new \Symfony\Component\Yaml\Parser();
            return $parser->parse($yaml);
        } else {
            return NULL;
        }
    }

    public static function encode($data, array $options = NULL)
    {
        if ((is_array($data) || is_object($data))) {
            $dumper = new \Symfony\Component\Yaml\Dumper();
            return $dumper->dump($data,2);
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