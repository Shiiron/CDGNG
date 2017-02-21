<?php
namespace CDGNG\Views;

class Main extends TwigView
{
    protected function getTemplateFilename()
    {
        return 'main.twig';
    }

    protected function getData()
    {
        return array(
            'actions' => $this->model->actions,
            'modes' => $this->model->modes,
            'calendars' => $this->model->calendars,
        );
    }
}
