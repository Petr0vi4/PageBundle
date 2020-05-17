<?php


namespace Creonit\PageBundle\Admin;


use Creonit\AdminBundle\Component\EditorComponent;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\PageBundle\Model\Map\PageSiteTableMap;
use Creonit\PageBundle\Model\PageSite;
use Creonit\PageBundle\Service\HostResolver;
use Creonit\PageBundle\Service\PageHostService;
use Creonit\PageBundle\Service\PageService;

class PageSiteEditor extends EditorComponent
{
    /**
     * @var bool
     */
    protected $needFixHost;

    /**
     * @title Сайт
     *
     * @entity Creonit\PageBundle\Model\PageSite
     *
     * @field title {required: true}
     * @field host {required: true}
     *
     * @template
     * {{ title | text | group('Название') }}
     * {{ host | text | group('Домен') }}
     */
    public function schema()
    {
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @return PageSite
     */
    protected function retrieveEntity(ComponentRequest $request, ComponentResponse $response)
    {
        $entity = parent::retrieveEntity($request, $response);

        if ($entity->isNew() and $host = $request->query->get('host')) {
            $entity->setHost($host);
        }

        return $entity;
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param PageSite $entity
     */
    public function validate(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        if ($host = $request->data->get('host')) {
            try {
                $this->container->get(HostResolver::class)->resolve($host);

            } catch (\RuntimeException | \InvalidArgumentException $exception) {
                $response->flushError('Некорректное значение для домена', 'host');
            }
        }
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param PageSite $entity
     */
    public function preSave(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        if ($entity->isColumnModified(PageSiteTableMap::COL_HOST)) {
            $this->needFixHost = true;
        }
    }

    public function postSave(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        if ($this->needFixHost) {
            $this->container->get(PageHostService::class)->fixHostForAllPages();
            $this->container->get(PageService::class)->clearCache();
        }
    }
}