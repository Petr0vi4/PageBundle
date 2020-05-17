<?php


namespace Creonit\PageBundle\Admin;


use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\AdminBundle\Component\Scope\ListRowScopeRelation;
use Creonit\AdminBundle\Component\Scope\Scope;
use Creonit\AdminBundle\Component\TableComponent;
use Creonit\PageBundle\Exception\HostResolvingException;
use Creonit\PageBundle\Model\PageSite;
use Creonit\PageBundle\Service\HostResolver;
use Symfony\Component\HttpFoundation\ParameterBag;

class PageSiteTable extends TableComponent
{
    /**
     * @title Сайты
     *
     * @event close(){
     *   if(this.parent){
     *     this.parent.loadData();
     *   }
     * }
     *
     * @header
     * {{ button('Добавить сайт', {size: 'sm', icon: 'globe', type: 'success'}) | open('PageSiteEditor') }}
     *
     * @cols Сайт, Домен, .
     *
     * \PageSite
     * @entity Creonit\PageBundle\Model\PageSite
     * @sortable true
     *
     * @field host
     *
     * @col {{ title | icon('globe') | open('PageSiteEditor', {key: _key}) | controls }}
     * @col
     * {% if host == resolved_host %}
     *   {{ host }}
     * {% elseif host_resolving_error %}
     *   <mark>домен не найден {{ host }}</mark>
     * {% else %}
     *   {{ resolved_host }}
     * {% endif %}
     * @col {{ _delete() }}
     *
     */
    public function schema()
    {
    }

    /**
     * @param ComponentRequest $request
     * @param ComponentResponse $response
     * @param ParameterBag $data
     * @param PageSite $entity
     * @param Scope $scope
     * @param ListRowScopeRelation|null $relation
     * @param $relationValue
     * @param $level
     */
    protected function decorate(ComponentRequest $request, ComponentResponse $response, ParameterBag $data, $entity, Scope $scope, $relation, $relationValue, $level)
    {
        if ($host = $entity->getHost()) {
            try {
                $data->set('resolved_host', $this->container->get(HostResolver::class)->resolve($host));

            } catch (HostResolvingException $exception) {
                $data->set('host_resolving_error', true);
                $data->set('_row_class', 'danger');
            }
        }
    }
}