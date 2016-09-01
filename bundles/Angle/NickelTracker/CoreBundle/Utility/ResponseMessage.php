<?php

namespace Angle\NickelTracker\CoreBundle\Utility;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class ResponseMessage
{
    private $type;
    private $code;
    private $message;
    private $externalMessage;
    private $alert;

    // Error types / modules
    const SYMFONY = 1;
    const DOCTRINE = 2;
    const PHEANSTALK = 3;
    const CUSTOM = 4;

    // Alert types
    const ALERT_SUCCESS = 'success';
    const ALERT_INFO = 'info';
    const ALERT_WARNING = 'warning';
    const ALERT_ERROR = 'error';

    // Symfony error list
    private static $symfony = array(
        1 => array(
            'alert' => self::ALERT_INFO,
            'message' => 'General Error Description'
        ),
    );

    // Doctrine error list
    private static $doctrine = array(

    );

    // Pheanstalk error list
    private static $pheanstalk = array(
        0 => array(
            'alert' => self::ALERT_SUCCESS,
            'message' => 'The operation was successful!'
        ),
    );

    // Custom error list
    private static $custom = array(
        0 => array(
            'alert' => self::ALERT_SUCCESS,
            'message' => '¡La operación ha sido exitosa!'
        ),
        1 => array(
            'alert' => self::ALERT_ERROR,
            'message' => 'No se han podido almacenar los cambios en la base de datos, por favor verifique la información proporcionada e intente de nuevo.'
        ),
    );


    /**
     * @param integer $code
     * @param integer $type
     */
    public function __construct($type, $code)
    {
        $this->type = $type;
        $this->code = $code;

        $this->message = self::getMessageFromTypeAndCode($type, $code);
        $this->alert = self::getAlertFromTypeAndCode($type, $code);
    }

    /**
     * @param string $externalMessage
     */
    public function setExternalMessage($externalMessage)
    {
        $this->externalMessage = $externalMessage;
    }

    /**
     * @param integer $type
     * @param integer $code
     * @return string
     */
    public static function getMessageFromTypeAndCode($type, $code)
    {
        switch ($type) {
            case self::SYMFONY:
                if (array_key_exists($code, self::$symfony)) {
                    $message = self::$symfony[$code]['message'];
                } else {
                    $message = 'Unregistered Symfony error';
                }
                break;
            case self::DOCTRINE:
                if (array_key_exists($code, self::$doctrine)) {
                    $message = self::$doctrine[$code]['message'];
                } else {
                    $message = 'Unregistered Doctrine error';
                }
                break;
            case self::PHEANSTALK:
                if (array_key_exists($code, self::$pheanstalk)) {
                    $message = self::$pheanstalk[$code]['message'];
                } else {
                    $message = 'Unregistered Pheanstalk error';
                }
                break;
            case self::CUSTOM:
                if (array_key_exists($code, self::$custom)) {
                    $message = self::$custom[$code]['message'];
                } else {
                    $message = 'Unregistered Custom error';
                }
                break;
            default:
                throw new \RuntimeException("ResponseMessage type '{$type}' not registered.");
        }

        return $message;
    }

    /**
     * @param integer $type
     * @param integer $code
     * @return string
     */
    public static function getAlertFromTypeAndCode($type, $code)
    {
        switch ($type) {
            case self::SYMFONY:
                if (array_key_exists($code, self::$symfony)) {
                    $alert = self::$symfony[$code]['alert'];
                } else {
                    $alert = 'info';
                }
                break;
            case self::DOCTRINE:
                if (array_key_exists($code, self::$doctrine)) {
                    $alert = self::$doctrine[$code]['alert'];
                } else {
                    $alert = 'info';
                }
                break;
            case self::PHEANSTALK:
                if (array_key_exists($code, self::$pheanstalk)) {
                    $alert = self::$pheanstalk[$code]['alert'];
                } else {
                    $alert = 'info';
                }
                break;
            case self::CUSTOM:
                if (array_key_exists($code, self::$custom)) {
                    $alert = self::$custom[$code]['alert'];
                } else {
                    $alert = 'info';
                }
                break;
            default:
                throw new \RuntimeException("ResponseMessage type '{$type}' not registered.");
        }

        return $alert;
    }

    /**
     * @param FlashBagInterface $flashBag
     */
    public function addToFlashBag(FlashBagInterface $flashBag)
    {
        $flashBag->add(
            $this->alert,
            $this->getPrettyMessage()
        );
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getPrettyMessage()
    {
        $s = '<strong>#' . $this->type . '-' . $this->code . ':</strong> ' . $this->message;

        if ($this->externalMessage) {
            $s .= " [" . $this->externalMessage . "]";
        }

        return $s;
    }
}