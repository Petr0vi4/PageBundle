<?php


namespace Creonit\PageBundle\Service;


use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Model\PageQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Propel;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class PageHostService
{
    /**
     * @var Router
     */
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function fixHostForAllPages()
    {
        $connection = Propel::getConnection();
        $connection->beginTransaction();

        $pages = PageQuery::create()->filterByType(Page::TYPE_ROUTE, Criteria::NOT_EQUAL)->filterByParentId(null, Criteria::ISNULL)->find();
        foreach ($pages as $page) {
            $this->fixHostForPage($page);
        }

        $pages = PageQuery::create()->filterByType(Page::TYPE_ROUTE)->orderByLevel()->find();
        foreach ($pages as $page) {
            $this->fixHostForPage($page);
        }

        $connection->commit();
    }

    public function fixHostForPage(Page $page)
    {
        $routeCollection = $this->router->getRouteCollection();

        $host = '';
        if ($page->isTypeRoute()) {
            $page->setHost('');
            $page->setPageSite(null);

            if ($route = $routeCollection->get($page->getName())) {
                $host = $route->getHost();
            }

        } else if ($parent = $page->getParent()) {
            if ($parent->isTypeRoute()) {
                if ($route = $routeCollection->get($parent->getName())) {
                    $host = $route->getHost();
                }

            } else {
                $host = $parent->getHost();
            }

            $page->setHost($host);
            $page->setPageSite(null);

        } else if ($site = $page->getPageSite()) {
            $host = $site->getHost();
            $page->setHost($host);

        } else {
            $page->setHost('');
        }

        if ($page->isModified()) {
            $page->save();
        }

        $this->spreadHostToChildren($page, $host);
    }

    public function spreadHostToChildren(Page $page, $host)
    {
        $page->getChildrenQuery()
            ->filterByType(Page::TYPE_ROUTE, Criteria::NOT_EQUAL)
            ->update([
                'Host' => $host,
                'PageSiteId' => null
            ]);
    }
}