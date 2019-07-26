<?php

namespace Corp\EiisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('corp_eiis');
        $rootNode
            ->children()
                ->arrayNode('remote')//->end()
                    ->children()
                        ->scalarNode('username')->isRequired()->end()
                        ->scalarNode('password')->isRequired()->end()
                        ->scalarNode('url')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('local')//->end()
                    ->children()
                        ->scalarNode('username')->isRequired()->end()
                        ->scalarNode('password')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('getfile')
					->children()
						->scalarNode('class')->end()
						->scalarNode('find_method')->end()
					->end()
				->end()
                ->arrayNode('objects')
                    ->prototype('array')
                    ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->scalarNode('remote_code')->isRequired()->end()
                    ->scalarNode('local_code')->isRequired()->end()
                    ->scalarNode('setter')->end()
                    ->scalarNode('getter')->end()
                    ->scalarNode('find_all_method')->end()
                    ->scalarNode('find_one_method')->isRequired()->end()
                    ->scalarNode('create_object_supported')->defaultFalse()->end()
                    ->scalarNode('delete_object_supported')->defaultFalse()->end()
        ;

        return $treeBuilder;
    }
}
