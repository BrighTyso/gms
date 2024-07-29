<?php
/**
 * WhatsAppPhoneType
 *
 * PHP version 7.2
 *
 * @category Class
 * @package  Infobip
 * @author   Infobip Support
 * @link     https://www.infobip.com
 */

/**
 * Infobip Client API Libraries OpenAPI Specification
 *
 * OpenAPI specification containing public endpoints supported in client API libraries.
 *
 * Contact: support@infobip.com
 *
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * Do not edit the class manually.
 */

namespace Infobip\Model;

use Infobip\ObjectSerializer;

/**
 * WhatsAppPhoneType Class Doc Comment
 *
 * @category Class
 * @description Type of the phone number. Can be &#x60;CELL&#x60;, &#x60;MAIN&#x60;, &#x60;IPHONE&#x60;, &#x60;HOME&#x60; or &#x60;WORK&#x60;.
 * @package  Infobip
 * @author   Infobip Support
 * @link     https://www.infobip.com
 */
class WhatsAppPhoneType
{
    /**
     * Possible values of this enum
     */
    public const CELL = 'CELL';
    public const MAIN = 'MAIN';
    public const IPHONE = 'IPHONE';
    public const HOME = 'HOME';
    public const WORK = 'WORK';

    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::CELL,
            self::MAIN,
            self::IPHONE,
            self::HOME,
            self::WORK,
        ];
    }
}
