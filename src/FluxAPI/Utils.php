<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 01.04.13
 * Time: 18:52
 * To change this template use File | Settings | File Templates.
 */

namespace FluxAPI;


class Utils
{
    public static function dateToString(\DateTime $date)
    {
        return $date->format('Y-m-d');
    }

    public static function dateTimeToString(\DateTime $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public static function dateTimeFromString($str)
    {
        return new \DateTime($str);
    }
}