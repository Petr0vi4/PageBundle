<?php

namespace Creonit\PageBundle\Admin;

use Creonit\AdminBundle\Module;

class PageModule extends Module{

    protected function configure()
    {
        $this
            ->setTitle('Структура')
            ->setIcon('file-text-o')
            ->setTemplate('PageTable')
            ->setPermission('ROLE_ADMIN_PAGE')
        ;
    }


    public function initialize(){
        $this->addComponent(new PageEditor());
        $this->addComponent(new PageTable());
        $this->addComponent(new ChoosePageTable());
    }

} 