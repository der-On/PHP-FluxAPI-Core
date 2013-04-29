<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ondrej
 * Date: 17.04.13
 * Time: 00:07
 * To change this template use File | Settings | File Templates.
 */

namespace Plugins\FluxAPI;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class DbalTypeVarbinary extends Type
{
    const VARBINARY = 'varbinary';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'VARBINARY('.$fieldDeclaration['length'].')';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function getName()
    {
        return self::VARBINARY;
    }
}