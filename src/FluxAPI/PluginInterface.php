<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 09.04.13
 * Time: 14:48
 * To change this template use File | Settings | File Templates.
 */

namespace FluxAPI;


interface PluginInterface
{
    /**
     * Called when the plugin get's registered
     *
     * Use this method to make your plugin magic.
     *
     * @param Api $api
     */
    public static function register(Api $api);
}