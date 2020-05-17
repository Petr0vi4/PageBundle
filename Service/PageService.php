<?php

namespace Creonit\PageBundle\Service;

use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Model\PageQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class PageService
{
    protected $router;
    protected $cacheDir;
    protected $ignoreRoute;
    protected $ignorePath;

    public function __construct(Router $router, $cacheDir, $ignoreRoute, $ignorePath)
    {
        $this->router = $router;
        $this->cacheDir = $cacheDir;
        $this->ignoreRoute = $ignoreRoute;
        $this->ignorePath = $ignorePath;
    }

    public function synchronizeRoutePages()
    {
        if ($ignoreRoute = $this->ignoreRoute) {
            $ignoreRoute = str_replace('/', '\/', $ignoreRoute);
        }
        if ($ignorePath = $this->ignorePath) {
            $ignorePath = str_replace('/', '\/', $ignorePath);
        }

        $pageIds = [];
        foreach ($this->router->getRouteCollection()->all() as $routeName => $route) {
            if (!$path = $route->getPath()) continue;
            if ($routeName[0] == '_') continue;
            if ($methods = $route->getMethods() and !in_array('GET', $methods)) continue;
            if ($ignoreRoute and preg_match('/' . $ignoreRoute . '/usi', $routeName)) continue;
            if ($ignorePath and preg_match('/' . $ignorePath . '/usi', $path)) continue;

            if (!$page = PageQuery::create()->findOneByName($routeName)) {
                $page = new Page();
                $page->setTitle($routeName);
                $page->setName($routeName);
            }

            if ($page->getType() == Page::TYPE_PAGE) {
                $page->setType(Page::TYPE_ROUTE);
            }

            $page->setUri($path);
            $page->save();

            $pageIds[] = $page->getId();
        }

        PageQuery::create()->filterById($pageIds, Criteria::NOT_IN)->filterByType(Page::TYPE_ROUTE)->update(['Type' => Page::TYPE_PAGE]);
    }

    public function clearCache()
    {
        $cacheFile = $this->cacheDir . '/' . $this->router->getOption('matcher_cache_class') . '.php';
        if (is_file($cacheFile)) {
            unlink($cacheFile);
        }

        return $this;
    }
}