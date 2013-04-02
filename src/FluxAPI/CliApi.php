<?php

namespace FluxAPI;

/**
 * Command line FluxAPI
 * @package FluxAPI
 */
class CliApi extends Api
{
    protected $_cli_app;

    public function setCliApplication($cli_app)
    {
        $this->_cli_app = $cli_app;
    }

    public function run()
    {
        $this->_cli_app->add(new \FluxAPI\Cli\Migrate());
        $this->_cli_app->add(new \FluxAPI\Cli\Load());
        $this->_cli_app->run();
    }
}