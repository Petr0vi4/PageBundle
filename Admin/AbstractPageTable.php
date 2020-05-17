<?php


namespace Creonit\PageBundle\Admin;


use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Component\Scope\ListRowScope;
use Creonit\AdminBundle\Component\Scope\ListRowScopeRelation;
use Creonit\AdminBundle\Component\Scope\Scope;
use Creonit\AdminBundle\Component\TableComponent;
use Creonit\PageBundle\Exception\HostResolvingException;
use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Model\PageQuery;
use Creonit\PageBundle\Model\PageSite;
use Creonit\PageBundle\Model\PageSiteQuery;
use Creonit\PageBundle\Service\HostResolver;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class AbstractPageTable extends TableComponent
{
    /**
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $routeCollection;

    /**
     * @var HostResolver
     */
    protected $hostResolver;

    /**
     * @var array
     */
    protected $sites;

    /**
     * @var object|\Symfony\Bundle\FrameworkBundle\Routing\Router|null
     */
    protected $router;

    public function schema()
    {
    }

    protected function loadData(ComponentRequest $request, ComponentResponse $response)
    {
        $this->router = $this->container->get('router');
        $this->routeCollection = $this->router->getRouteCollection();
        $this->hostResolver = $this->container->get(HostResolver::class);

        $hosts = [];

        $pages = PageQuery::create()
            ->filterByParentId(null, Criteria::ISNULL)
            ->filterByType([Page::TYPE_ROUTE, Page::TYPE_PAGE, Page::TYPE_MENU])
            ->filterByType(Page::TYPE_ROUTE)
            ->_or()->filterByPageSiteId(null, Criteria::ISNOTNULL)
            ->find();

        foreach ($pages as $page) {
            if ($page->isTypeRoute()) {
                if (!$route = $this->routeCollection->get($page->getName())) {
                    continue;
                }

                $host = $route->getHost();

            } else {
                try {
                    $host = $this->hostResolver->resolve($page->getHost());

                } catch (HostResolvingException $exception) {
                    continue;
                }
            }

            if (!$host) {
                continue;
            }

            $hosts[$host][] = $page->getId();
        }

        $this->sites = [];
        $siteMap = [];
        foreach (PageSiteQuery::create()->find() as $site) {
            try {
                $host = $this->hostResolver->resolve($site->getHost());
                $siteMap[$host] = $site;

                if (!isset($hosts[$host])) {
                    $hosts[$host] = [];
                }

            } catch (HostResolvingException $exception) {
                continue;
            }
        }

        foreach ($hosts as $host => $pages) {
            $site = $siteMap[$host] ?? null;
            $id = $site ? $site->getId() : md5($host);
            $this->sites[$id] = [
                'id' => $id,
                'host' => $host,
                'pages' => $pages,
                'site' => $site
            ];
        }

        uasort($this->sites, function ($a, $b) {
            $a = $a['site'] ? $a['site']->getSortableRank() : PHP_INT_MAX;
            $b = $b['site'] ? $b['site']->getSortableRank() : PHP_INT_MAX;

            return $a <=> $b;
        });

        parent::loadData($request, $response);
    }

    protected function load(ComponentRequest $request, ComponentResponse $response, ListRowScope $scope, $relation = null, $relationValue = null, $level = 0)
    {
        if ($scope->getName() === 'Site') {
            $data = [];
            foreach ($this->sites as $site) {
                $data[] = [
                    '_key' => $site['id'],
                    'title' => $site['site'] ? $site['site']->getTitle() : $site['host'],
                    'host' => $site['host'],
                    'site_id' => $site['site'] ? $site['site']->getId() : null,
                ];
            }

            if ($data) {
                $data[] = [
                    '_key' => 'default',
                    'title' => 'Страницы для всех сайтов',
                ];
            }

            return $data;
        }

        return parent::load($request, $response, $scope, $relation, $relationValue, $level);
    }


    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param PageQuery $query
     * @param Scope $scope
     * @param ListRowScopeRelation|null $relation
     * @param $relationValue
     * @param $level
     */
    protected function filter(ComponentRequest $request, ComponentResponse $response, $query, Scope $scope, $relation, $relationValue, $level)
    {
        if ($scope->getName() === 'Page') {
            if ($relation->getTargetScope()->getName() === 'Page') {
                if ($this->sites and $relationValue === null) {
                    $query->where('1<>1');
                }
            } else if ($relation->getTargetScope()->getName() === 'Site') {
                if ($relationValue === 'default') {
                    $ids = array_unique(array_reduce($this->sites, function ($accumulator, $item) {
                        return array_merge($accumulator, $item['pages']);
                    }, []));

                    $query->filterById($ids, Criteria::NOT_IN);
                    $query->filterByParentId(null, Criteria::ISNULL);

                } else {
                    $query->filterById($this->sites[$relationValue]['pages']);
                }
            }
        }
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param ParameterBag $data
     * @param Page|PageSite $entity
     * @param Scope $scope
     * @param ListRowScopeRelation|null $relation
     * @param $relationValue
     * @param $level
     */
    protected function decorate(ComponentRequest $request, ComponentResponse $response, ParameterBag $data, $entity, Scope $scope, $relation, $relationValue, $level)
    {
        if ($scope->getName() === 'Page') {
            if ($relation->getSourceScope()->getName() === 'Site') {
                $data->set('relation_id', $relationValue);
            }

            $data->set('icon', $this->resolveIcon($entity));

            switch ($entity->getType()) {
                case Page::TYPE_ROUTE:
                    if ($route = $this->routeCollection->get($entity->getName())) {
                        $data->set('url', $route->getPath());
                        $data->set('host', $route->getHost());

                        try {
                            $data->set('page_url', $this->router->generate($entity->getName()));
                        } catch (\Exception $exception) {
                        }

                    } else {
                        $data->set('url', 'ошибка');
                    }
                    break;

                case Page::TYPE_PAGE:
                case Page::TYPE_LINK:
                    $data->set('url', $entity->getUrl());
                    $data->set('page_url', $entity->getUrl());
                    break;
                case Page::TYPE_MENU:
                    break;
            }
        }
    }

    protected function resolveIcon(Page $page)
    {
        switch ($page->getType()) {
            case Page::TYPE_ROUTE:
                return 'cog';

            case Page::TYPE_LINK:
                return 'share';

            case Page::TYPE_MENU:
                return 'bars';

            case Page::TYPE_PAGE:
            default:
                return 'file-text-o';
        }
    }
}