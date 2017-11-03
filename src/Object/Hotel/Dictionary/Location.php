<?php
/**
 * Created by Roquie.
 * E-mail: roquie0@gmail.com
 * GitHub: Roquie
 * Date: 27.04.16
 * Project: provider
 */

declare(strict_types=1);

namespace App\Hotelbook\Object\Hotel\Dictionary;

use App\Hotelbook\Object\Hotel\Distance;

/**
 * Class Location
 *
 * Список объектов, которые находятся рядом с отелем.
 *
 * @package Hive\Common\Object\Hotel
 */
class Location
{
    use Distance;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $cityId;

    /**
     * @var bool
     */
    protected $global;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * @param int $cityId
     * @return $this
     */
    public function setCityId(int $cityId)
    {
        $this->cityId = $cityId;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isGlobal()
    {
        return $this->global;
    }

    /**
     * @param boolean $global
     * @return $this
     */
    public function setGlobal(bool $global)
    {
        $this->global = $global;

        return $this;
    }
}
