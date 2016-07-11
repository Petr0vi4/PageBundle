<?php

namespace Creonit\PageBundle\Admin;

use AppBundle\Model\ProductCategoryQuery;
use AppBundle\Model\ProductCategoryRel;
use AppBundle\Model\ProductCategoryRelQuery;
use AppBundle\Model\ProductQuery;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Component\Scope\Scope;
use Creonit\AdminBundle\Component\TableComponent;
use Creonit\PageBundle\Model\PageQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\HttpFoundation\ParameterBag;

class ChoosePageTable extends TableComponent
{

    /**
     * @title Выберите страницу
     *
     * \Page
     * @entity Creonit\PageBundle\Model\Page
     * @relation parent_id > Page.id
     * @col
     * {% if type == 3 %}
     *      {% set icon = 'bars' %}
     * {% elseif type == 2 %}
     *      {% set icon = 'share' %}
     * {% elseif type == 1 %}
     *      {% set icon = 'file-text-o' %}
     * {% else %}
     *      {% set icon = 'cog' %}
     * {% endif %}
     * {{ title | icon(icon) | action('external', _key, title) | controls }}
     */
    public function schema()
    {
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param ParameterBag $data
     * @param ProductCategoryQuery $entity
     * @param Scope $scope
     * @param $relation
     * @param $relationValue
     * @param $level
     */
    protected function decorate(ComponentRequest $request, ComponentResponse $response, ParameterBag $data, $entity, Scope $scope, $relation, $relationValue, $level)
    {
        if($data->get('_key') == $request->query->get('value')){
            $data->set('_row_class', 'success');
        }
    }

    protected function filter(ComponentRequest $request, ComponentResponse $response, $query, Scope $scope, $relation, $relationValue, $level)
    {
        if($request->query->get('page') && $page = PageQuery::create()->findPk($request->query->get('page'))){
            $query->filterByPath("{$page->getSelfPath()}%", Criteria::NOT_LIKE);
            $query->filterById($page->getId(), Criteria::NOT_EQUAL);
        }
        
    }


}