<?php

namespace Creonit\PageBundle\Admin;


use Creonit\AdminBundle\Component\EditorComponent;
use Creonit\AdminBundle\Component\Request\ComponentRequest;
use Creonit\AdminBundle\Component\Response\ComponentResponse;
use Creonit\PageBundle\Model\Page;

class PageEditor extends EditorComponent
{


    /**
     * @title Страница
     * @entity Creonit\PageBundle\Model\Page
     *
     * @field title {required: true}
     * @field parent_id:external {title: 'entity.getParent().getTitle()'}
     * @field type:select {options: {1: 'Страница', 2: 'Ссылка', 3: 'Меню'}}
     *
     * @template
     *
     * {% if type.value %}
     *      {{ type | select({reload: true}) | group('Тип') }}
     * {% else %}
     *      {{ url | raw | panel('warning', ('Системная страница <b>'~name~'</b>') | raw)}}
     * {% endif %}
     *
     * {{ parent_id | external('Page.ChoosePageTable', {empty: 'Без родительской страницы', query: {page: _key} }) | group('Родительская страница') }}
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
     * {% if type.value < 2 %}
     *      {{ content_id | content | group('Содержание') }}
     *      <br>
     *      {{ (
     *          (((meta_title | text | group('Заголовок') | col(6)) ~ (meta_keywords | text | group('Ключи') | col(6))) | row) ~
     *          (meta_description | textarea | group('Описание'))
     *         ) | panel('default', 'Мета информация')
     *      }}
     * {% endif %}
     *
     *
     */
    public function schema(){
    }


    protected function retrieveEntity(ComponentRequest $request, ComponentResponse $response)
    {
        $entity = parent::retrieveEntity($request, $response);
        if($entity->isNew()){
            $entity->setType(1);
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
        if($relation = $request->query->get('relation')){
            $field = $this->getField('parent_id');
            $entity->setParentId($relation);
            $response->data->set($field->getName(), $field->load($entity));
        }

        switch($entity->getType()){
            case Page::TYPE_ROUTE:
                if($route = $this->container->get('router')->getRouteCollection()->get($entity->getName())){
                    $response->data->set('url', $route->getPath());
                }else{
                    $response->data->set('url', 'ошибка: системная страница отсутствует');
                }
                break;
            case Page::TYPE_PAGE:
                $response->data->set('url', $entity->getUri() ?: $entity->getSlug());
                $response->data->set('url_absolute', !!$entity->getUri());
                break;
            case Page::TYPE_LINK:
                break;
            case Page::TYPE_MENU:
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
        switch($request->data->get('type')){
            case Page::TYPE_PAGE:
                break;
            case Page::TYPE_LINK:
                break;
            case Page::TYPE_MENU:
                if(!$request->data->get('name')){
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
        switch($entity->getType()){
            case Page::TYPE_PAGE:
                $entity->setSlug('');
                $entity->setUri('');
                if($url = $request->data->get('url')){
                    if($request->data->has('url_absolute')){
                        $entity->setUri($this->normalizeUri($url));
                    }else{
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
    }


    public function postSave(ComponentRequest $request, ComponentResponse $response, $entity)
    {
        $this->container->get('creonit_page')->clearCache();
    }



    protected function normalizeUri($url){
        $url = '/'.trim($url, '/').'/';
        return $url == '//' ? '/' : $url;
    }

    protected function normalizeSlug($url){
        return trim($url, '/');
    }
}