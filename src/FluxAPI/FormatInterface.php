<?php

namespace FluxAPI;

interface FormatInterface
{
    /**
     * Converts data in the format to an array
     *
     * @param mixed $raw
     * @param [array $options]
     * @return null|Array
     */
    public static function decode($raw, array $options = NULL);

    /**
     * Converts an array to the format.
     *
     * @param mixed $data
     * @param [array $options]
     * @return mixed|null
     */
    public static function encode($data, array $options = NULL);

    /**
     * Returns the file extension of the format.
     *
     * @return string
     */
    public static function getExtension();

    /**
     * Returns the mime-type of the format.
     *
     * @return string
     */
    public static function getMimeType();
}