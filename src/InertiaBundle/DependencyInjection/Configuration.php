<?php declare(strict_types=1);

namespace InertiaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public static $configTree = [
        'root_view' => 'default/inertia.html.twig',
        'admin' => [
            'split_view' => true,
            'edit_mode_template' => '@InertiaBundle/edit_mode.html.twig'
        ],
        'ssr' => [
            'enabled' => false,
            'url' => 'http://localhost:13714/render'
        ],
        'csrf' => [
            'enabled' => false,
            'header_name' => 'X-XSRF-TOKEN',
            'cookie_name' => 'XSRF-TOKEN',
            'expire' => 0,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'raw' => false,
            'samesite' => 'lax',
        ]
    ];

    private function addEntryToTree(NodeBuilder $node, string $name, mixed $value): NodeBuilder
    {

        if (is_float($value)) {
            return $node->floatNode($name)->defaultValue($value)->end();
        }

        if (is_int($value)) {
            return $node->integerNode($name)->defaultValue($value)->end();
        }

        if (is_bool($value)) {
            return $node->booleanNode($name)->defaultValue($value)->end();
        }

        if (is_string($value)) {
            return $node->scalarNode($name)->defaultValue($value)->end();
        }

        if (is_array($value)) {
            $node = $node->arrayNode($name)->addDefaultsIfNotSet()->children();

            foreach ($value as $k => $v) {
                $node = $this->addEntryToTree($node, $k, $v);
            }

            return $node->end()->end();
        }

        return $node->variableNode($name)->defaultValue($value)->end();
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('inertia');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode = $rootNode->children();

        foreach (self::$configTree as $key => $value) {
            $rootNode = $this->addEntryToTree($rootNode, $key, $value);
        }

        $rootNode->end();

        return $treeBuilder;
    }
}
