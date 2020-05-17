<?php

namespace Creonit\PageBundle\Admin;


use Creonit\AdminBundle\Component\EditorComponent;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\PageBundle\Model\Map\PageTableMap;
use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Service\PageHostService;

class PageEditor extends EditorComponent
{
    /**
     * @var bool
     */
    protected $needHostFixing;

    /**
     * @title Страница
     * @entity Creonit\PageBundle\Model\Page
     *
     * @field title {required: true}
     * @field parent:external
     * @field type:select {options: {1: 'Страница', 2: 'Ссылка', 3: 'Меню'}}
     *
     * @template
     * {% if type.value %}
     *      {{ type | select({reload: true}) | group('Тип') }}
     * {% else %}
     *      {{ url | raw | panel('warning', ('Системная страница <b>'~name~'</b>') | raw)}}
     * {% endif %}
     *
     * {{ parent | external('ChoosePageTable', {empty: 'Без родителя', query: {page: _key} }) | group('Родительский элемент') }}
     * {{ title | text | group('Заголовок') }}
     *
     * {% if type.value == 3 %}
     *      {{ name | text | group('Идентификатор') }}
     * {% elseif type.value == 2 %}
     *      {{ uri | text | group('Ссылка') }}
     * {% elseif type.value == 1 %}
     *      {{ url | text | group('Ссылка', {notice: (url_absolute | checkbox('Абсолютная ссылка'))}) }}
     *      {{ name | text | group('Идентификатор') }}
     * {% endif %}
     *
     * {{ hide | checkbox('Не показывать в меню') | group }}
     *
     * {% if type.value < 2 %}
     *      {{ content_id | content | group('Содержание') }}
     *      <br>
     *      {{ (
     *          (((meta_title | text | group('Заголовок') | col(6)) ~ (meta_keywords | text | group('Ключи') | col(6))) | row) ~
     *          (meta_description | textarea | group('Описание'))
     *         ) | panel('default', 'Мета информация')
     *      }}
     * {% endif %}
     */
    public function schema()
    {
    }

    protected function retrieveEntity(ComponentRequest $request, ComponentResponse $response)
    {
        $entity = parent::retrieveEntity($request, $response);
        if ($entity->isNew()) {
            $entity->setType(Page::TYPE_PAGE);
        }

        return $entity;
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param Page $entity
     */
    public function decorate(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        $parentField = $this->getField('parent');

        if ($relation = $request->query->get('relation')) {
            $this->resolveParent($entity, $relation);
        }

        if ($pageParent = $entity->getParent()) {
            $response->data->set($parentField->getName(), ['value' => 'p_' . $pageParent->getId(), 'title' => $pageParent->getTitle()]);

        } else if ($parentSite = $entity->getPageSite()) {
            $response->data->set($parentField->getName(), ['value' => 's_' . $parentSite->getId(), 'title' => '[Сайт] ' . $parentSite->getTitle()]);

        } else {
            $response->data->set($parentField->getName(), ['value' => '', 'title' => 'Без родителя']);
        }

        switch ($entity->getType()) {
            case Page::TYPE_ROUTE:
                if ($route = $this->container->get('router')->getRouteCollection()->get($entity->getName())) {
                    $response->data->set('url', $route->getPath());
                } else {
                    $response->data->set('url', 'ошибка: системная страница отсутствует');
                }
                break;
            case Page::TYPE_PAGE:
                $response->data->set('url', $entity->getUri() ?: $entity->getSlug());
                $response->data->set('url_absolute', !!$entity->getUri());
                break;
            case Page::TYPE_MENU:
            case Page::TYPE_LINK:
                break;
        }
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param Page $entity
     */
    public function validate(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        switch ($request->data->get('type')) {
            case Page::TYPE_LINK:
            case Page::TYPE_PAGE:
                break;
            case Page::TYPE_MENU:
                if (!$request->data->get('name')) {
                    $response->error('Заполните поле', 'name');
                }
                break;
        }
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param Page $entity
     */
    public function preSave(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        switch ($entity->getType()) {
            case Page::TYPE_PAGE:
                $entity->setSlug('');
                $entity->setUri('');
                if ($url = $request->data->get('url')) {
                    if ($request->data->has('url_absolute')) {
                        $entity->setUri($this->normalizeUri($url));
                    } else {
                        $entity->setSlug($this->normalizeSlug($url));
                    }
                }

                break;
            case Page::TYPE_LINK:
                $entity->setName('');
                $entity->setSlug('');
                break;
            case Page::TYPE_MENU:
                $entity->setUri('');
                $entity->setSlug('');
                break;
        }

        $this->resolveParent($entity, $request->data->get('parent'));

        if ($entity->isColumnModified(PageTableMap::COL_PARENT_ID) or $entity->isColumnModified(PageTableMap::COL_PAGE_SITE_ID)) {
            $this->needHostFixing = true;
        }
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param Page $entity
     */
    public function postSave(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        if ($this->needHostFixing === true) {
            $this->container->get(PageHostService::class)->fixHostForPage($entity);
        }

        $this->container->get('creonit_page')->clearCache();
    }

    protected function normalizeUri($url)
    {
        $url = '/' . trim($url, "/ \t") . '/';
        return $url == '//' ? '/' : $url;
    }

    protected function normalizeSlug($url)
    {
        return trim($url, "/ \t");
    }

    /**
     * @param Page $entity
     * @param $parentId
     */
    protected function resolveParent($entity, $parentId)
    {
        $entity->setPageSiteId(null);
        $entity->setParentId(null);

        if (!$parentId) {
            return;
        }

        list($type, $id) = explode('_', $parentId);

        if ($type == 'p') {
            $entity->setParentId($id);

        } else if ($type == 's') {
            $entity->setPageSiteId($id);
        }
    }
}