<?php

namespace Creonit\PageBundle\Admin;

use Creonit\AdminBundle\Component\Pattern\ListPattern;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Component\Scope\Scope;
use Creonit\AdminBundle\Component\TableComponent;
use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Model\PageQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\HttpFoundation\ParameterBag;

class PageTable extends TableComponent
{
    /**
     * @title Список страниц
     * @header
     * {{ button('Добавить страницу', {size: 'sm', icon: 'file-text-o', type: 'success'}) | open('Page.PageEditor') }}
     * {{ button('Синхронизировать', {size: 'sm', icon: 'refresh'}) | action('synchronize') }}
     * @cols Заголовок, Идентификатор, URL, .
     *
     * @action synchronize(){
     *      this.request('synchronize', {}, {}, function(){
     *          alert('Синхронизация прошла успешно');
     *      });
     *      this.loadData();
     * }
     * @action copy(options){
     *      var $row = this.findRowById(options.rowId);
     *      this.request('copy', $.extend({page_id: options.key}, this.getQuery()), {state: $row.hasClass('success')});
     *      this.loadData();
     * }
     *
     * \Page
     * @entity Creonit\PageBundle\Model\Page
     * @relation parent_id > Page.id
     * @sortable true
     *
     * @field id
     * @field parent_id
     * @field title
     * @field type
     *
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
     *
     * {{ title | icon(icon) | open('Page.PageEditor', {key: _key}) | controls(
     *      (type != 2 ? button('Добавить страницу', {size: 'xs', icon: 'file-text-o'}) | open('Page.PageEditor', {relation: _key}) : '')
     * ) }}
     * @col {{ name }}
     * @col {{ url | raw }}
     * @col
     * {% if type %}
     *      {{ buttons(_visible() ~ _delete() ~ button('', {icon: 'clone', size: 'xs'})  | action('copy', {key: _key, rowId: _row_id}) ) }}
     * {% else %}
     *      {{ buttons(_visible() ~ button('', {icon: 'remove', size: 'xs', disabled: true}) ~ button('', {icon: 'clone', size: 'xs'})  | action('copy', {key: _key, rowId: _row_id}) ) }}
     * {% endif %}
     *
     *
     *
     */
    public function schema()
    {

        $this->setHandler('synchronize', function($request, $response){
            $page = $this->container->get('creonit_page');
            $page->synchronizeRoutePages();
            $page->clearCache();
        });

        $this->setHandler('copy', function (ComponentRequest $request, ComponentResponse $response) {
            $page = PageQuery::create()->findPk($request->query->get('page_id')) or $response->flushError('Страница не найдена');
            $unicalCounterName = PageQuery::create()->filterByName("%" . $page->getName() . "%", Criteria::LIKE)->count();
            $unicalCounterSlug = PageQuery::create()->filterBySlug("%" . $page->getSlug() . "%", Criteria::LIKE)->count();

            $copiedPage = $page->copy(true);
            $copiedPage
                ->setTitle($page->getTitle() .' (Копия)')
                ->setName($page->getName() . ($page->getName() ? ($unicalCounterName == 1 ? '_copy' : '_copy' . $unicalCounterName) : ''))
                ->setSlug($page->getSlug() . ($page->getSlug() ? ($unicalCounterSlug == 1 ? '_copy' : '_copy' . $unicalCounterSlug) : ''))
                ->setUri(preg_replace('/\\/$/', '_copy/', $page->getUri()))
                ->setVisible(false)
                ->save()
            ;


            $page = $this->container->get('creonit_page');
            $page->synchronizeRoutePages();
            $page->clearCache();
        });
        
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param ParameterBag $data
     * @param Page $entity
     * @param Scope $scope
     * @param $relation
     * @param $relationValue
     * @param $level
     */
    protected function decorate(ComponentRequest $request, ComponentResponse $response, ParameterBag $data, $entity, Scope $scope, $relation, $relationValue, $level)
    {
        switch($entity->getType()){
            case Page::TYPE_ROUTE:
                if($route = $this->container->get('router')->getRouteCollection()->get($entity->getName())){
                    $data->set('url', $route->getPath());
                }else{
                    $data->set('url', '<mark>ошибка</mark>');
                }
                break;
            case Page::TYPE_PAGE:
            case Page::TYPE_LINK:
                $data->set('url', $entity->getUrl());
                break;
            case Page::TYPE_MENU:
                //$data->set('_row_class', 'info');
                break;
        }
    }


}