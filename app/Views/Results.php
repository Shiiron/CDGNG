<?php
namespace CDGNG\Views;

class Results extends TwigView
{
    public $statistics;

    public function __construct($model, $statistics)
    {
        parent::__construct($model);
        $this->statistics = $statistics;
    }

    protected function getTemplateFilename()
    {
        return 'results.twig';
    }

    protected function getData()
    {
        return array(
            'actions' => $this->model->actions,
            'modes' => $this->model->modes,
            'errors' => $this->getErrors(),
            'statistics' => $this->statistics,
        );
    }

    protected function getErrors()
    {
        $output = array();
        foreach ($this->statistics->calendars as $name => $calendar) {
            $output[$name] = $calendar['calendar']->errors;
        }
        return $output;
    }
}
