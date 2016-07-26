<?php

namespace Creonit\PageBundle\Model;

use Creonit\PageBundle\Model\Base\Page as BasePage;

/**
 * Skeleton subclass for representing a row from the 'page' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Page extends BasePage
{

    const TYPE_ROUTE = 0;
    const TYPE_PAGE = 1;
    const TYPE_LINK = 2;
    const TYPE_MENU = 3;

    public function getUrl(){

        if($this->slug){
            return (($this->parent_id and $parent = $this->getParent() and in_array($parent->getType(), [self::TYPE_PAGE, self::TYPE_ROUTE]))? $this->getParent()->getUrl() : '/') . $this->slug . '/';
        }else if($this->uri){
            return $this->uri;
        }else{
            return '';
        }
    }

}
