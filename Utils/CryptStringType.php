<?php

namespace Whitewashing\ReviewSquawkBundle\Utils;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use \Doctrine\DBAL\Types\ConversionException;

class CryptStringType extends Type
{
    /**
     * @var string
     */
    static public $key = "";

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) return null;

        if (!is_string(self::$key) || strlen(self::$key) == 0) {
            throw new ConversionException("Conversion failed, encryption key is missing!");
        }

        return self::encrypt($value, self::$key);
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     * @param AbstractPlatform $platform The currently used database platform.
     * @return mixed The PHP representation of the value.
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (!is_string(self::$key) || strlen(self::$key) == 0) {
            throw new ConversionException("Conversion failed, encryption key is missing!");
        }

        return self::decrypt($value, self::$key);
    }

    static function encrypt($text, $key)
    {
        return trim(
            base64_encode(
                mcrypt_encrypt(
                    MCRYPT_RIJNDAEL_256,
                    $key,
                    $text,
                    MCRYPT_MODE_ECB,
                    mcrypt_create_iv(
                        mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),
                        MCRYPT_RAND
                    )
                )
            )
        );
    }

    static function decrypt($text, $key)
    {
        return trim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_256,
                $key,
                base64_decode($text),
                MCRYPT_MODE_ECB,
                mcrypt_create_iv(
                    mcrypt_get_iv_size(
                        MCRYPT_RIJNDAEL_256,
                        MCRYPT_MODE_ECB
                    ),
                    MCRYPT_RAND
                )
            )
        );
    }

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform The currently used database platform.
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return 'cryptstring';
    }
}