<?php

namespace Creonit\PageBundle\Routing;

use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Model\PageQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ExtraLoader implements LoaderInterface
{
    private $loaded = false;
    private $controller;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $routes = new RouteCollection();

        /** @var Page $page */
        foreach(PageQuery::create()->filterByType(Page::TYPE_PAGE)->find() as $page){
            if($page->getSlug() or $page->getUri()){
                $routes->add($page->getName() ?: "_page_{$page->getId()}", new Route($page->getUrl(), [
                    '_controller' => $this->controller,
                    'page' => $page->getId()
                ]));
            }

        }

        $this->loaded = true;

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'extra' === $type;
    }

    public function getResolver()
    {
    }

    public function setResolver(LoaderResolverInterface $resolver)
    {
    }
}