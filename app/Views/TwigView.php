<?php
namespace CDGNG\Views;

abstract class TwigView extends InterfaceView
{
    protected $twig;

    abstract protected function getTemplateFilename();
    abstract protected function getData();

    public function __construct($model)
    {
        $twigLoader = new \Twig_Loader_Filesystem($this->getThemePath());
        $this->twig = new \Twig_Environment($twigLoader);

        $this->model = $model;
    }

    protected function getThemePath()
    {
        return 'themes/default/';
    }

    public function show()
    {
        echo $this->twig->render(
            $this->getTemplateFilename(),
            $this->getData()
        );
    }
}
