<?php

namespace Creonit\PageBundle\Admin;

use Creonit\AdminBundle\Module;

class PageModule extends Module{

    protected $icon = 'fa fa-file-text-o';
    protected $title = 'Структура';
    protected $template = '<div js-component="Page.PageTable"></div>';

    public function initialize(){
        $this->addComponent(new PageEditor());
        $this->addComponent(new PageTable());
        $this->addComponent(new ChoosePageTable());
    }

} 