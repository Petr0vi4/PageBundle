<?php

namespace Creonit\PageBundle;

use Creonit\PageBundle\Model\Page;
use Creonit\PageBundle\Model\PageQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CreonitPage
{

    protected $container;
    protected $router;
    protected $requestStack;
    protected $options = [];

    /** @var  Page */
    protected $activePage;
    protected $activePages = [];

    
    public function __construct(ContainerInterface $container, RequestStack $requestStack, Router $router, $options = [])
    {
        $this->container = $container;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->options = $options;
    }

    public function synchronizeRoutePages(){
        $pageIds = [];
        foreach($this->router->getRouteCollection()->all() as $routeName => $route){
            if(!$path = $route->getPath()) continue;
            if($routeName[0] == '_') continue;
            if($methods = $route->getMethods() and !in_array('GET', $methods)) continue;

            if(!$page = PageQuery::create()->findOneByName($routeName)){
                $page = new Page();
                $page->setTitle($routeName);
                $page->setName($routeName);
            }

            $page->setUri($path);
            $page->save();

            $pageIds[] = $page->getId();
        }

        PageQuery::create()->filterById($pageIds, Criteria::NOT_IN)->filterByType(Page::TYPE_ROUTE)->update(['Type' => Page::TYPE_PAGE, 'Name' => '']);
    }


    public function clearCache(){
        $cacheFile = $this->options['cache_dir'] . '/' . $this->options['matcher_cache_class'] . '.php';
        if(is_file($cacheFile)){
            unlink($cacheFile);
        }
        /*
        $cacheFile = $this->options['cache_dir'] . '/' . $this->options['generator_cache_class'] . '.php';
        if(is_file($cacheFile)){
            unlink($cacheFile);
        }
        */
        return $this;
    }

    public function getMenu($pageName){

        $this->getActivePage();

        if($pageName instanceof Page ? ($rootPage = $pageName) : ($rootPage = PageQuery::create()->findOneByName($pageName))){

            $routeCollection = $this->router->getRouteCollection();

            $children = [];
            foreach ($rootPage->getChildrenQuery(1)->filterByType(Page::TYPE_MENU, Criteria::NOT_EQUAL)->forList()->find() as $page) {
                $child = [
                    'title' => $page->getTitle(),
                ];

                if($page->getType() == Page::TYPE_ROUTE){
                    if($route = $routeCollection->get($page->getName())){
                        $child['url'] = $this->router->generate($page->getName());
                    }

                }else{
                    $child['url'] = $page->getUrl();
                }

                $child['active'] = in_array($page->getId(), $this->activePages);

                $children[] = $child;
            }
            return $children;

        }else{
            return [];
        }

    }

    /**
     * @return Page
     */
    public function getActivePage()
    {
        if(null !== $this->activePage){
            return $this->activePage;
        }

        $route = $this->router->matchRequest($this->requestStack->getMasterRequest());

        if(preg_match('/^_page_(\d+)$/', $route['_route'], $match)){
            $this->activePage = PageQuery::create()->findPk($match[1]);

        }else{
            $this->activePage = PageQuery::create()->findOneByName($route['_route']);

        }

        if($this->activePage){
            $this->activePages = $this->activePage->getParents(Page::PARENT_PKS | Page::INCLUDE_SELF);
        }

        return $this->activePage;
    }

    public function getHead(){

        $title = '';
        $metaDescription = '';
        $metaKeywords = '';

        if($page = $this->getActivePage()){
            $title = $page->getMetaTitle() ?: $page->getTitle();
            $metaDescription = $page->getMetaDescription();
            $metaKeywords = $page->getMetaKeywords();
        }

        return "
            <title>{$title}</title>
            <meta name=\"title\" content=\"{$title}\" />
            <meta name=\"keywords\" content=\"{$metaKeywords}\" />
            <meta name=\"description\" content=\"{$metaDescription}\" />
        ";

    }

}