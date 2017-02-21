<?php
namespace CDGNG\Statistics;

class Day extends Statistic
{
    protected function getData($calendar)
    {
        return $calendar->getData('day');
    }

    protected function getSlotName()
    {
        return('Jour (YYYY/MM/DD)');
    }
}
