<?php

namespace Creonit\PageBundle\Admin;

use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\PageBundle\Service\PageHostService;

class PageTable extends AbstractPageTable
{
    /**
     * @title Список страниц
     * @header
     * {{ button('Добавить страницу', {size: 'sm', icon: 'file-text-o', type: 'success'}) | open('PageEditor') }}
     * {{ button('Управление сайтами', {size: 'sm', icon: 'globe'}) | open('PageSiteTable') }}
     * {{ button('Обновить структуру', {size: 'sm', icon: 'refresh'}) | action('synchronize') }}
     *
     * @cols Страница, Идентификатор, URL, .
     *
     * @action openUrl(pageUrl){
     *   window.open(pageUrl, '_blank');
     * }
     *
     * @action synchronize(){
     *   this.node.find('.panel-heading .fa-refresh').addClass('fa-spin').closest('button').prop('disabled', true);
     *   this.request('synchronize', {}, {}, function(){
     *     alert('Структура обновлена');
     *   });
     *   this.loadData();
     * }
     *
     * \Site
     * @data []
     * @col
     * <div class="pull-right">
     *   <div class="label label-default">{{ host }}</div>
     * </div>
     * <strong>
     * {% if site_id %}
     *   {{ title | icon('globe') | controls(button('', {size: 'xs', icon: 'file-text-o'}) | tooltip('Добавить страницу') | open('PageEditor', {relation: 's_' ~ site_id})) }}
     * {% else %}
     *   {{ title | icon('globe') | controls(_key == 'default'
     *      ? button('', {size: 'xs', icon: 'file-text-o'}) | tooltip('Добавить страницу') | open('PageEditor')
     *      : button('', {size: 'xs', icon: 'globe'}) | tooltip('Создать сайт для домена') | open('PageSiteEditor', {host: host}))
     *   }}
     * {% endif %}
     * </strong>
     *
     * @col
     * @col
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
     * @field type
     *
     * @col
     * {{ title | icon(icon) | open('PageEditor', {key: _key}) | controls(buttons(
     *      (button('', {size: 'xs', icon: 'file-text-o'}) | tooltip('Добавить страницу') | open('PageEditor', {relation: 'p_' ~ _key}))
     *      ~ (page_url ? button('', {size: 'xs', icon: 'external-link'}) | action('openUrl', page_url) : '')
     * )) }}
     * @col {{ name }}
     * @col {{ url | raw }}
     * @col
     * {% if type %}
     *      {{ buttons(_visible() ~ _delete() ) }}
     * {% else %}
     *      {{ buttons(_visible() ~ button('', {icon: 'remove', size: 'xs', disabled: true}) ) }}
     * {% endif %}
     */
    public function schema()
    {
        $this->setHandler('synchronize', function (ComponentRequest $request, ComponentResponse $response) {
            @set_time_limit(0);
            $page = $this->container->get('creonit_page');
            $page->synchronizeRoutePages();
            $this->container->get(PageHostService::class)->fixHostForAllPages();
            $page->clearCache();
        });
    }
}