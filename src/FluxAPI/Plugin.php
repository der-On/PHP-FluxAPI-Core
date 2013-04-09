<?php
namespace FluxAPI;

/**
 * A general plugin
 *
 * All general plugins must inherit from this.
 * General plugins do not fall into one of the following categories: Model, Storage, Cache, Permission, Controller
 * For these categories special base classes exist.
 *
 * @package FluxAPI
 */
abstract class Plugin implements PluginInterface
{

}
