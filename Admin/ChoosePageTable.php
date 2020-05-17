<?php

namespace Creonit\PageBundle\Admin;

use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Component\Scope\Scope;
use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Model\PageQuery;
use Creonit\PageBundle\Model\PageSite;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\HttpFoundation\ParameterBag;

class ChoosePageTable extends AbstractPageTable
{

    /**
     * @title Выберите страницу
     *
     * \Site
     * @data []
     * @col
     * <div class="pull-right">
     *   <div class="label label-default">{{ host }}</div>
     * </div>
     * <strong>
     * {% if site_id %}
     *   {{ title | icon('globe') | action('external', 's_' ~ _key, '[Сайт] ' ~ title) | controls }}
     * {% else %}
     *   {{ title | icon('globe') | controls }}
     * {% endif %}
     * </strong>
     *
     * @col
     *
     * \Page
     * @entity Creonit\PageBundle\Model\Page
     * @relation parent_id > Page.id
     * @relation relation_id > Site._key
     * @independent true
     * @sortable true
     *
     * @field id
     * @field parent_id
     * @field title
     *
     * @col
     * {{ title | icon(icon) | action('external', 'p_' ~ _key, title) | controls }}
     *
     * @col {{ url | raw }}
     */
    public function schema()
    {
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param ParameterBag $data
     * @param PageSite|Page $entity
     * @param Scope $scope
     * @param $relation
     * @param $relationValue
     * @param $level
     */
    protected function decorate(ComponentRequest $request, ComponentResponse $response, ParameterBag $data, $entity, Scope $scope, $relation, $relationValue, $level)
    {
        if (($scope->getName() === 'Page' ? 'p_' : 's_') . $data->get('_key') == $request->query->get('value')) {
            $data->set('_row_class', 'success');
        }

        parent::decorate($request, $response, $data, $entity, $scope, $relation, $relationValue, $level);
    }

    protected function filter(ComponentRequest $request, ComponentResponse $response, $query, Scope $scope, $relation, $relationValue, $level)
    {
        parent::filter($request, $response, $query, $scope, $relation, $relationValue, $level);

        if ($scope->getName() === 'Page') {
            if ($request->query->get('page') && $page = PageQuery::create()->findPk($request->query->get('page'))) {
                $query->filterByPath("{$page->getSelfPath()}%", Criteria::NOT_LIKE);
                $query->filterById($page->getId(), Criteria::NOT_EQUAL);
            }
        }
    }
}