<?php
namespace CDGNG\Statistics;

class Week extends Statistic
{
    protected function getData($calendar)
    {
        return $calendar->getData('week');
    }
}
