<?php

namespace App\Hotelbook\Object\Hotel;

/**
 * Class Price
 * @package App\Hotelbook\Object\Hotel
 */
class Price
{
    /**
     * @var
     */
    private $amount;
    /**
     * @var string
     */
    private $currency;

    /**
     * @param $amount
     * @return string
     */
    private function precise($amount)
    {
        return (string) round((float)$amount, 1);
    }

    /**
     * Price constructor.
     * @param $amount
     * @param $currency
     */
    public function __construct($amount, $currency)
    {
        $this->amount = $amount;
        $this->currency = $this->precise($currency);
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}
