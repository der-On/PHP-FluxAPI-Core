<?php
require_once __DIR__ . '/../../FluxAPI/FluxApi_Database_TestCase.php';

class RestTest extends FluxApi_Database_TestCase
{
    public function testCrudNodes()
    {
        $this->migrate();

    }
}