<?php


namespace Creonit\PageBundle\Service;


use Creonit\PageBundle\Exception\HostResolvingException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HostResolver
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $value
     * @return array|mixed
     * @throws HostResolvingException
     */
    public function resolve($value)
    {
        if (\is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->resolve($val);
            }

            return $value;
        }

        if (!\is_string($value)) {
            return $value;
        }

        $escapedValue = preg_replace_callback('/%%|%([^%\s]++)%/', function ($match) use ($value) {
            // skip %%
            if (!isset($match[1])) {
                return '%%';
            }

            if (preg_match('/^env\((?:\w++:)*+\w++\)$/', $match[1])) {
                throw new HostResolvingException(sprintf('Using "%%%s%%" is not allowed in routing configuration.', $match[1]));
            }

            try {
                $resolved = $this->container->getParameter($match[1]);

            } catch (\InvalidArgumentException $exception) {
                throw new HostResolvingException($exception->getMessage());
            }

            if (\is_bool($resolved)) {
                $resolved = (string)(int)$resolved;
            }

            if (\is_string($resolved) || is_numeric($resolved)) {
                return (string)$this->resolve($resolved);
            }

            throw new HostResolvingException(sprintf('The container parameter "%s", used in the route configuration value "%s", must be a string or numeric, but it is of type %s.', $match[1], $value, \gettype($resolved)));
        }, $value);

        return str_replace('%%', '%', $escapedValue);
    }
}