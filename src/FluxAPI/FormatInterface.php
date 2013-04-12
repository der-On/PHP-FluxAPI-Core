<?php

namespace FluxAPI;

interface FormatInterface
{
    /**
     * Converts data in the format to an array
     *
     * You must implement this in method.
     *
     * @param mixed $raw
     * @param [array $options]
     * @return null|Array
     */
    public static function decode($raw, array $options = NULL);

    /**
     * Converts an array to the format.
     *
     * You must implement this in method.
     *
     * @param mixed $data
     * @param [array $options]
     * @return mixed|null
     */
    public static function encode($data, array $options = NULL);

    /**
     * Returns the file extension of the format.
     *
     * You must implement this in method.
     *
     * @return string
     */
    public static function getExtension();

    /**
     * Returns the mime-type of the format.
     *
     * You must implement this in method.
     *
     * @return string
     */
    public static function getMimeType();

    /**
     * @param Api $api
     */
    public static function setApi(\FluxAPI\Api $api);

    /**
     * Encodes a model instance.
     *
     * @param $model_name
     * @param Model $model
     * @return mixed
     */
    public static function encodeFromModel($model_name, \FluxAPI\Model $model);

    /**
     * Encodes multiple model instances of same type.
     *
     * You must implement this in method.
     *
     * @param $model_name
     * @param array $models
     * @return mixed
     */
    public static function encodeFromModels($model_name, array $models);

    /**
     * Decodes encoded format for population of a single model instance.
     *
     * @param $model_name
     * @param $raw
     * @return mixed
     */
    public static function decodeForModel($model_name, $raw);

    /**
     * Decodes encoded format for population of multiple model instances.
     *
     * @param $model_name
     * @param $raw
     * @return mixed
     */
    public static function decodeForModels($model_name, $raw);

    /**
     * Returns the name of the Format.
     * @return string
     */
    public static function getName();
}