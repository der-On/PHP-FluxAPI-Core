<?php
require_once __DIR__ . '/../FluxApi_TestCase.php';

use \FluxApi\Query;

class PluginFactoryTest extends FluxApi_TestCase
{
    public function testDisabledPluginsByFile()
    {
        $config = $this->getConfig();
        $config['plugin.options'] = array(
            'disabled' => array("FluxAPI/Model/UserGroup","FluxAPI/Model/User")
        );

        $fluxApi = $this->getFluxApi($config);

        // user and usergroup plugin should not be there
        $this->assertFalse(self::$fluxApi['plugins']->hasPlugin('Model','UserGroup'));
        $this->assertFalse(self::$fluxApi['plugins']->hasPlugin('Model','User'));
    }

    public function testDisabledPluginsByType()
    {
        $config = $this->getConfig();
        $config['plugin.options'] = array(
            'disabled' => array("FluxAPI/Model","FluxAPI/Format")
        );

        $fluxApi = $this->getFluxApi($config);

        // model and format plugins should not exists
        $this->assertCount(0, self::$fluxApi['plugins']->getPlugins('Model'));
        $this->assertCount(0, self::$fluxApi['plugins']->getPlugins('Format'));
    }

    public function testDisabledPluginsBySuite()
    {
        $config = $this->getConfig();
        $config['plugin.options'] = array(
            'disabled' => array("FluxAPI")
        );

        $fluxApi = $this->getFluxApi($config);

        // no plugins should exists
        $this->assertCount(0, self::$fluxApi['plugins']->getPlugins('Model'));
        $this->assertCount(0, self::$fluxApi['plugins']->getPlugins('Format'));
        $this->assertCount(0, self::$fluxApi['plugins']->getPlugins('Storage'));
    }
}