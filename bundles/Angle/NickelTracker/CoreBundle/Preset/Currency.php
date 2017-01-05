<?php

namespace Angle\NickelTracker\CoreBundle\Preset;

abstract class Currency
{
    private static $currencies = array(
        1 => array(
            'name' => 'Mexican Pesos',
            'code' => 'MXN', // Currency codes using ISO 4217
            'symbol' => '$',
            'format' => 1,
        ),
        2 => array(
            'name' => 'US Dollars',
            'code' => 'USD',
            'symbol' => '$',
            'format' => 1,
        ),
    );

    /**
     * @return array
     */
    public static function availableCurrencies()
    {
        return self::$currencies;
    }

    /**
     * @return array
     */
    public static function availableCurrenciesFlat()
    {
        $a = array();
        foreach (self::$currencies as $key => $c) {
            $a[$key] = $c['name'];
        }

        return $a;
    }

    /**
     * @param int $id Currency ID
     * @return string
     */
    public static function getCurrencyName($id)
    {
        if (!array_key_exists($id, self::$currencies)) throw new \RuntimeException("Currency code '{$id}' does not exist.");

        return self::$currencies[$id]['name'];
    }

    /**
     * @param int $id Currency ID
     * @return string
     */
    public static function getCurrencySymbol($id)
    {
        if (!array_key_exists($id, self::$currencies)) throw new \RuntimeException("Currency code '{$id}' does not exist.");

        return self::$currencies[$id]['symbol'];
    }

    /**
     * @param int $id Currency ID
     * @return string Currency ISO 4217 Code
     */
    public static function getCurrencyCode($id)
    {
        if (!array_key_exists($id, self::$currencies)) throw new \RuntimeException("Currency ID '{$id}' does not exist.");

        return self::$currencies[$id]['code'];
    }

    /**
     * @param int $id Currency ID
     * @return int
     */
    public static function getCurrencyFormat($id)
    {
        if (!array_key_exists($id, self::$currencies)) throw new \RuntimeException("Currency ID '{$id}' does not exist.");

        return self::$currencies[$id]['format'];
    }

    /**
     * @param int $id Currency ID
     * @param float $amount Amount to be formatted
     * @param boolean $full Full money representation
     * @return string
     */
    public static function formatMoney($id, $amount, $full=false)
    {
        $s = '';
        switch (self::getCurrencyFormat($id)) {
            case 1:
                $s = self::getCurrencySymbol($id) . number_format($amount, 2);
                if ($amount < 0) $s = '-' . $s;
                if ($full) $s .= ' ' . self::getCurrencyCode($id);
                break;
        }

        return $s;
    }
}