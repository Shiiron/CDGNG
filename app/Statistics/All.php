<?php
namespace CDGNG\Statistics;

class All extends Statistic
{
    protected function getData($calendar)
    {
        return $calendar->getData();
    }
}
