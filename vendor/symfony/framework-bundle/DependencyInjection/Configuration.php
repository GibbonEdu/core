<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Cache\Cache;
use Symfony\Bundle\FullStack;
use Symfony\Component\Asset\Package;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * FrameworkExtension configuration structure.
 *
 * @author Jeremy Mikola <jmikola@gmail.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * @param bool $debug Whether debugging is enabled or not
     */
    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('framework');

        $rootNode
            ->beforeNormalization()
                ->ifTrue(function ($v) { return !isset($v['assets']) && isset($v['templating']) && class_exists(Package::class); })
                ->then(function ($v) {
                    $v['assets'] = array();

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('secret')->end()
                ->scalarNode('http_method_override')
                    ->info("Set true to enable support for the '_method' request parameter to determine the intended HTTP method on POST requests. Note: When using the HttpCache, you need to call the method in your front controller instead")
                    ->defaultTrue()
                ->end()
                ->scalarNode('ide')->defaultNull()->end()
                ->booleanNode('test')->end()
                ->scalarNode('default_locale')->defaultValue('en')->end()
                ->arrayNode('trusted_hosts')
                    ->beforeNormalization()->ifString()->then(function ($v) { return array($v); })->end()
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        $this->addCsrfSection($rootNode);
        $this->addFormSection($rootNode);
        $this->addEsiSection($rootNode);
        $this->addSsiSection($rootNode);
        $this->addFragmentsSection($rootNode);
        $this->addProfilerSection($rootNode);
        $this->addWorkflowSection($rootNode);
        $this->addRouterSection($rootNode);
        $this->addSessionSection($rootNode);
        $this->addRequestSection($rootNode);
        $this->addTemplatingSection($rootNode);
        $this->addAssetsSection($rootNode);
        $this->addTranslatorSection($rootNode);
        $this->addValidationSection($rootNode);
        $this->addAnnotationsSection($rootNode);
        $this->addSerializerSection($rootNode);
        $this->addPropertyAccessSection($rootNode);
        $this->addPropertyInfoSection($rootNode);
        $this->addCacheSection($rootNode);
        $this->addPhpErrorsSection($rootNode);
        $this->addWebLinkSection($rootNode);
        $this->addLockSection($rootNode);
        $this->addMessengerSection($rootNode);

        return $treeBuilder;
    }

    private function addCsrfSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('csrf_protection')
                    ->treatFalseLike(array('enabled' => false))
                    ->treatTrueLike(array('enabled' => true))
                    ->treatNullLike(array('enabled' => true))
                    ->addDefaultsIfNotSet()
                    ->children()
                        // defaults to framework.session.enabled && !class_exists(FullStack::class) && interface_exists(CsrfTokenManagerInterface::class)
                        ->booleanNode('enabled')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addFormSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('form')
                    ->info('form configuration')
                    ->{!class_exists(FullStack::class) && class_exists(Form::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->arrayNode('csrf_protection')
                            ->treatFalseLike(array('enabled' => false))
                            ->treatTrueLike(array('enabled' => true))
                            ->treatNullLike(array('enabled' => true))
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultNull()->end() // defaults to framework.csrf_protection.enabled
                                ->scalarNode('field_name')->defaultValue('_token')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addEsiSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('esi')
                    ->info('esi configuration')
                    ->canBeEnabled()
                ->end()
            ->end()
        ;
    }

    private function addSsiSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('ssi')
                    ->info('ssi configuration')
                    ->canBeEnabled()
                ->end()
            ->end();
    }

    private function addFragmentsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('fragments')
                    ->info('fragments configuration')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('path')->defaultValue('/_fragment')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addProfilerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('profiler')
                    ->info('profiler configuration')
                    ->canBeEnabled()
                    ->children()
                        ->booleanNode('collect')->defaultTrue()->end()
                        ->booleanNode('only_exceptions')->defaultFalse()->end()
                        ->booleanNode('only_master_requests')->defaultFalse()->end()
                        ->scalarNode('dsn')->defaultValue('file:%kernel.cache_dir%/profiler')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addWorkflowSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('workflow')
            ->children()
                ->arrayNode('workflows')
                    ->canBeEnabled()
                    ->beforeNormalization()
                        ->always(function ($v) {
                            if (true === $v['enabled']) {
                                $workflows = $v;
                                unset($workflows['enabled']);

                                if (1 === \count($workflows) && isset($workflows[0]['enabled'])) {
                                    $workflows = array();
                                }

                                $v = array(
                                    'enabled' => true,
                                    'workflows' => $workflows,
                                );
                            }

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->arrayNode('workflows')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->fixXmlConfig('support')
                                ->fixXmlConfig('place')
                                ->fixXmlConfig('transition')
                                ->children()
                                    ->arrayNode('audit_trail')
                                        ->canBeEnabled()
                                    ->end()
                                    ->enumNode('type')
                                        ->values(array('workflow', 'state_machine'))
                                        ->defaultValue('state_machine')
                                    ->end()
                                    ->arrayNode('marking_store')
                                        ->fixXmlConfig('argument')
                                        ->children()
                                            ->enumNode('type')
                                                ->values(array('multiple_state', 'single_state'))
                                            ->end()
                                            ->arrayNode('arguments')
                                                ->beforeNormalization()
                                                    ->ifString()
                                                    ->then(function ($v) { return array($v); })
                                                ->end()
                                                ->requiresAtLeastOneElement()
                                                ->prototype('scalar')
                                                ->end()
                                            ->end()
                                            ->scalarNode('service')
                                                ->cannotBeEmpty()
                                            ->end()
                                        ->end()
                                        ->validate()
                                            ->ifTrue(function ($v) { return isset($v['type']) && isset($v['service']); })
                                            ->thenInvalid('"type" and "service" cannot be used together.')
                                        ->end()
                                        ->validate()
                                            ->ifTrue(function ($v) { return !empty($v['arguments']) && isset($v['service']); })
                                            ->thenInvalid('"arguments" and "service" cannot be used together.')
                                        ->end()
                                    ->end()
                                    ->arrayNode('supports')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) { return array($v); })
                                        ->end()
                                        ->prototype('scalar')
                                            ->cannotBeEmpty()
                                            ->validate()
                                                ->ifTrue(function ($v) { return !class_exists($v) && !interface_exists($v); })
                                                ->thenInvalid('The supported class or interface "%s" does not exist.')
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('support_strategy')
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('initial_place')
                                        ->defaultNull()
                                    ->end()
                                    ->arrayNode('places')
                                        ->beforeNormalization()
                                            ->always()
                                            ->then(function ($places) {
                                                // It's an indexed array of shape  ['place1', 'place2']
                                                if (isset($places[0]) && \is_string($places[0])) {
                                                    return array_map(function (string $place) {
                                                        return array('name' => $place);
                                                    }, $places);
                                                }

                                                // It's an indexed array, we let the validation occur
                                                if (isset($places[0]) && \is_array($places[0])) {
                                                    return $places;
                                                }

                                                foreach ($places as $name => $place) {
                                                    if (\is_array($place) && array_key_exists('name', $place)) {
                                                        continue;
                                                    }
                                                    $place['name'] = $name;
                                                    $places[$name] = $place;
                                                }

                                                return array_values($places);
                                            })
                                        ->end()
                                        ->isRequired()
                                        ->requiresAtLeastOneElement()
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('name')
                                                    ->isRequired()
                                                    ->cannotBeEmpty()
                                                ->end()
                                                ->arrayNode('metadata')
                                                    ->normalizeKeys(false)
                                                    ->defaultValue(array())
                                                    ->example(array('color' => 'blue', 'description' => 'Workflow to manage article.'))
                                                    ->prototype('variable')
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('transitions')
                                        ->beforeNormalization()
                                            ->always()
                                            ->then(function ($transitions) {
                                                // It's an indexed array, we let the validation occur
                                                if (isset($transitions[0]) && \is_array($transitions[0])) {
                                                    return $transitions;
                                                }

                                                foreach ($transitions as $name => $transition) {
                                                    if (\is_array($transition) && array_key_exists('name', $transition)) {
                                                        continue;
                                                    }
                                                    $transition['name'] = $name;
                                                    $transitions[$name] = $transition;
                                                }

                                                return $transitions;
                                            })
                                        ->end()
                                        ->isRequired()
                                        ->requiresAtLeastOneElement()
                                        ->prototype('array')
                                            ->children()
                                                ->scalarNode('name')
                                                    ->isRequired()
                                                    ->cannotBeEmpty()
                                                ->end()
                                                ->scalarNode('guard')
                                                    ->cannotBeEmpty()
                                                    ->info('An expression to block the transition')
                                                    ->example('is_fully_authenticated() and has_role(\'ROLE_JOURNALIST\') and subject.getTitle() == \'My first article\'')
                                                ->end()
                                                ->arrayNode('from')
                                                    ->beforeNormalization()
                                                        ->ifString()
                                                        ->then(function ($v) { return array($v); })
                                                    ->end()
                                                    ->requiresAtLeastOneElement()
                                                    ->prototype('scalar')
                                                        ->cannotBeEmpty()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('to')
                                                    ->beforeNormalization()
                                                        ->ifString()
                                                        ->then(function ($v) { return array($v); })
                                                    ->end()
                                                    ->requiresAtLeastOneElement()
                                                    ->prototype('scalar')
                                                        ->cannotBeEmpty()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('metadata')
                                                    ->normalizeKeys(false)
                                                    ->defaultValue(array())
                                                    ->example(array('color' => 'blue', 'description' => 'Workflow to manage article.'))
                                                    ->prototype('variable')
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('metadata')
                                        ->normalizeKeys(false)
                                        ->defaultValue(array())
                                        ->example(array('color' => 'blue', 'description' => 'Workflow to manage article.'))
                                        ->prototype('variable')
                                        ->end()
                                    ->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return $v['supports'] && isset($v['support_strategy']);
                                    })
                                    ->thenInvalid('"supports" and "support_strategy" cannot be used together.')
                                ->end()
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !$v['supports'] && !isset($v['support_strategy']);
                                    })
                                    ->thenInvalid('"supports" or "support_strategy" should be configured.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addRouterSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('router')
                    ->info('router configuration')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('resource')->isRequired()->end()
                        ->scalarNode('type')->end()
                        ->scalarNode('http_port')->defaultValue(80)->end()
                        ->scalarNode('https_port')->defaultValue(443)->end()
                        ->scalarNode('strict_requirements')
                            ->info(
                                "set to true to throw an exception when a parameter does not match the requirements\n".
                                "set to false to disable exceptions when a parameter does not match the requirements (and return null instead)\n".
                                "set to null to disable parameter checks against requirements\n".
                                "'true' is the preferred configuration in development mode, while 'false' or 'null' might be preferred in production"
                            )
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addSessionSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('session')
                    ->info('session configuration')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('storage_id')->defaultValue('session.storage.native')->end()
                        ->scalarNode('handler_id')->defaultValue('session.handler.native_file')->end()
                        ->scalarNode('name')
                            ->validate()
                                ->ifTrue(function ($v) {
                                    parse_str($v, $parsed);

                                    return implode('&', array_keys($parsed)) !== (string) $v;
                                })
                                ->thenInvalid('Session name %s contains illegal character(s)')
                            ->end()
                        ->end()
                        ->scalarNode('cookie_lifetime')->end()
                        ->scalarNode('cookie_path')->end()
                        ->scalarNode('cookie_domain')->end()
                        ->booleanNode('cookie_secure')->end()
                        ->booleanNode('cookie_httponly')->defaultTrue()->end()
                        ->booleanNode('use_cookies')->end()
                        ->scalarNode('gc_divisor')->end()
                        ->scalarNode('gc_probability')->defaultValue(1)->end()
                        ->scalarNode('gc_maxlifetime')->end()
                        ->scalarNode('save_path')->defaultValue('%kernel.cache_dir%/sessions')->end()
                        ->integerNode('metadata_update_threshold')
                            ->defaultValue('0')
                            ->info('seconds to wait between 2 session metadata updates')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addRequestSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('request')
                    ->info('request configuration')
                    ->canBeEnabled()
                    ->fixXmlConfig('format')
                    ->children()
                        ->arrayNode('formats')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifTrue(function ($v) { return \is_array($v) && isset($v['mime_type']); })
                                    ->then(function ($v) { return $v['mime_type']; })
                                ->end()
                                ->beforeNormalization()
                                    ->ifTrue(function ($v) { return !\is_array($v); })
                                    ->then(function ($v) { return array($v); })
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addTemplatingSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('templating')
                    ->info('templating configuration')
                    ->canBeEnabled()
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return false === $v || \is_array($v) && false === $v['enabled']; })
                        ->then(function () { return array('enabled' => false, 'engines' => false); })
                    ->end()
                    ->children()
                        ->scalarNode('hinclude_default_template')->defaultNull()->end()
                        ->scalarNode('cache')->end()
                        ->arrayNode('form')
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('resource')
                            ->children()
                                ->arrayNode('resources')
                                    ->addDefaultChildrenIfNoneSet()
                                    ->prototype('scalar')->defaultValue('FrameworkBundle:Form')->end()
                                    ->validate()
                                        ->ifTrue(function ($v) {return !\in_array('FrameworkBundle:Form', $v); })
                                        ->then(function ($v) {
                                            return array_merge(array('FrameworkBundle:Form'), $v);
                                        })
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->fixXmlConfig('engine')
                    ->children()
                        ->arrayNode('engines')
                            ->example(array('twig'))
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->canBeUnset()
                            ->beforeNormalization()
                                ->ifTrue(function ($v) { return !\is_array($v) && false !== $v; })
                                ->then(function ($v) { return array($v); })
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                    ->fixXmlConfig('loader')
                    ->children()
                        ->arrayNode('loaders')
                            ->beforeNormalization()
                                ->ifTrue(function ($v) { return !\is_array($v); })
                                ->then(function ($v) { return array($v); })
                             ->end()
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addAssetsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('assets')
                    ->info('assets configuration')
                    ->{!class_exists(FullStack::class) && class_exists(Package::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->fixXmlConfig('base_url')
                    ->children()
                        ->scalarNode('version_strategy')->defaultNull()->end()
                        ->scalarNode('version')->defaultNull()->end()
                        ->scalarNode('version_format')->defaultValue('%%s?%%s')->end()
                        ->scalarNode('json_manifest_path')->defaultNull()->end()
                        ->scalarNode('base_path')->defaultValue('')->end()
                        ->arrayNode('base_urls')
                            ->requiresAtLeastOneElement()
                            ->beforeNormalization()
                                ->ifTrue(function ($v) { return !\is_array($v); })
                                ->then(function ($v) { return array($v); })
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return isset($v['version_strategy']) && isset($v['version']);
                        })
                        ->thenInvalid('You cannot use both "version_strategy" and "version" at the same time under "assets".')
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return isset($v['version_strategy']) && isset($v['json_manifest_path']);
                        })
                        ->thenInvalid('You cannot use both "version_strategy" and "json_manifest_path" at the same time under "assets".')
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return isset($v['version']) && isset($v['json_manifest_path']);
                        })
                        ->thenInvalid('You cannot use both "version" and "json_manifest_path" at the same time under "assets".')
                    ->end()
                    ->fixXmlConfig('package')
                    ->children()
                        ->arrayNode('packages')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->fixXmlConfig('base_url')
                                ->children()
                                    ->scalarNode('version_strategy')->defaultNull()->end()
                                    ->scalarNode('version')
                                        ->beforeNormalization()
                                        ->ifTrue(function ($v) { return '' === $v; })
                                        ->then(function ($v) { return; })
                                        ->end()
                                    ->end()
                                    ->scalarNode('version_format')->defaultNull()->end()
                                    ->scalarNode('json_manifest_path')->defaultNull()->end()
                                    ->scalarNode('base_path')->defaultValue('')->end()
                                    ->arrayNode('base_urls')
                                        ->requiresAtLeastOneElement()
                                        ->beforeNormalization()
                                            ->ifTrue(function ($v) { return !\is_array($v); })
                                            ->then(function ($v) { return array($v); })
                                        ->end()
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return isset($v['version_strategy']) && isset($v['version']);
                                    })
                                    ->thenInvalid('You cannot use both "version_strategy" and "version" at the same time under "assets" packages.')
                                ->end()
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return isset($v['version_strategy']) && isset($v['json_manifest_path']);
                                    })
                                    ->thenInvalid('You cannot use both "version_strategy" and "json_manifest_path" at the same time under "assets" packages.')
                                ->end()
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return isset($v['version']) && isset($v['json_manifest_path']);
                                    })
                                    ->thenInvalid('You cannot use both "version" and "json_manifest_path" at the same time under "assets" packages.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addTranslatorSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('translator')
                    ->info('translator configuration')
                    ->{!class_exists(FullStack::class) && class_exists(Translator::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->fixXmlConfig('fallback')
                    ->fixXmlConfig('path')
                    ->children()
                        ->arrayNode('fallbacks')
                            ->beforeNormalization()->ifString()->then(function ($v) { return array($v); })->end()
                            ->prototype('scalar')->end()
                            ->defaultValue(array('en'))
                        ->end()
                        ->booleanNode('logging')->defaultValue(false)->end()
                        ->scalarNode('formatter')->defaultValue('translator.formatter.default')->end()
                        ->scalarNode('default_path')
                            ->info('The default path used to load translations')
                            ->defaultValue('%kernel.project_dir%/translations')
                        ->end()
                        ->arrayNode('paths')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addValidationSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('validation')
                    ->info('validation configuration')
                    ->{!class_exists(FullStack::class) && class_exists(Validation::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->validate()
                        ->ifTrue(function ($v) { return isset($v['strict_email']) && isset($v['email_validation_mode']); })
                        ->thenInvalid('"strict_email" and "email_validation_mode" cannot be used together.')
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return isset($v['strict_email']); })
                        ->then(function ($v) {
                            @trigger_error('The "framework.validation.strict_email" configuration key has been deprecated in Symfony 4.1. Use the "framework.validation.email_validation_mode" configuration key instead.', E_USER_DEPRECATED);

                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return isset($v['strict_email']) && !isset($v['email_validation_mode']); })
                        ->then(function ($v) {
                            $v['email_validation_mode'] = $v['strict_email'] ? 'strict' : 'loose';
                            unset($v['strict_email']);

                            return $v;
                        })
                    ->end()
                    ->children()
                        ->scalarNode('cache')->end()
                        ->booleanNode('enable_annotations')->{!class_exists(FullStack::class) && class_exists(Annotation::class) ? 'defaultTrue' : 'defaultFalse'}()->end()
                        ->arrayNode('static_method')
                            ->defaultValue(array('loadValidatorMetadata'))
                            ->prototype('scalar')->end()
                            ->treatFalseLike(array())
                            ->validate()
                                ->ifTrue(function ($v) { return !\is_array($v); })
                                ->then(function ($v) { return (array) $v; })
                            ->end()
                        ->end()
                        ->scalarNode('translation_domain')->defaultValue('validators')->end()
                        ->booleanNode('strict_email')->end()
                        ->enumNode('email_validation_mode')->values(array('html5', 'loose', 'strict'))->end()
                        ->arrayNode('mapping')
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('path')
                            ->children()
                                ->arrayNode('paths')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addAnnotationsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('annotations')
                    ->info('annotation configuration')
                    ->{class_exists(Annotation::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->scalarNode('cache')->defaultValue(interface_exists(Cache::class) ? 'php_array' : 'none')->end()
                        ->scalarNode('file_cache_dir')->defaultValue('%kernel.cache_dir%/annotations')->end()
                        ->booleanNode('debug')->defaultValue($this->debug)->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addSerializerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('serializer')
                    ->info('serializer configuration')
                    ->{!class_exists(FullStack::class) && class_exists(Serializer::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->booleanNode('enable_annotations')->{!class_exists(FullStack::class) && class_exists(Annotation::class) ? 'defaultTrue' : 'defaultFalse'}()->end()
                        ->scalarNode('name_converter')->end()
                        ->scalarNode('circular_reference_handler')->end()
                        ->scalarNode('max_depth_handler')->end()
                        ->arrayNode('mapping')
                            ->addDefaultsIfNotSet()
                            ->fixXmlConfig('path')
                            ->children()
                                ->arrayNode('paths')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addPropertyAccessSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('property_access')
                    ->addDefaultsIfNotSet()
                    ->info('Property access configuration')
                    ->children()
                        ->booleanNode('magic_call')->defaultFalse()->end()
                        ->booleanNode('throw_exception_on_invalid_index')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addPropertyInfoSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('property_info')
                    ->info('Property info configuration')
                    ->{!class_exists(FullStack::class) && interface_exists(PropertyInfoExtractorInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end()
        ;
    }

    private function addCacheSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('cache')
                    ->info('Cache configuration')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('pool')
                    ->children()
                        ->scalarNode('prefix_seed')
                            ->info('Used to namespace cache keys when using several apps with the same shared backend')
                            ->example('my-application-name')
                        ->end()
                        ->scalarNode('app')
                            ->info('App related cache pools configuration')
                            ->defaultValue('cache.adapter.filesystem')
                        ->end()
                        ->scalarNode('system')
                            ->info('System related cache pools configuration')
                            ->defaultValue('cache.adapter.system')
                        ->end()
                        ->scalarNode('directory')->defaultValue('%kernel.cache_dir%/pools')->end()
                        ->scalarNode('default_doctrine_provider')->end()
                        ->scalarNode('default_psr6_provider')->end()
                        ->scalarNode('default_redis_provider')->defaultValue('redis://localhost')->end()
                        ->scalarNode('default_memcached_provider')->defaultValue('memcached://localhost')->end()
                        ->arrayNode('pools')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('adapter')->defaultValue('cache.app')->end()
                                    ->booleanNode('public')->defaultFalse()->end()
                                    ->integerNode('default_lifetime')->end()
                                    ->scalarNode('provider')
                                        ->info('The service name to use as provider when the specified adapter needs one.')
                                    ->end()
                                    ->scalarNode('clearer')->end()
                                ->end()
                            ->end()
                            ->validate()
                                ->ifTrue(function ($v) { return isset($v['cache.app']) || isset($v['cache.system']); })
                                ->thenInvalid('"cache.app" and "cache.system" are reserved names')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addPhpErrorsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('php_errors')
                    ->info('PHP errors handling configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('log')
                            ->info('Use the application logger instead of the PHP logger for logging PHP errors.')
                            ->example('"true" to use the default configuration: log all errors. "false" to disable. An integer bit field of E_* constants.')
                            ->defaultValue($this->debug)
                            ->treatNullLike($this->debug)
                            ->validate()
                                ->ifTrue(function ($v) { return !(\is_int($v) || \is_bool($v)); })
                                ->thenInvalid('The "php_errors.log" parameter should be either an integer or a boolean.')
                            ->end()
                        ->end()
                        ->booleanNode('throw')
                            ->info('Throw PHP errors as \ErrorException instances.')
                            ->defaultValue($this->debug)
                            ->treatNullLike($this->debug)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addLockSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('lock')
                    ->info('Lock configuration')
                    ->{!class_exists(FullStack::class) && class_exists(Lock::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->beforeNormalization()
                        ->ifString()->then(function ($v) { return array('enabled' => true, 'resources' => $v); })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(function ($v) { return \is_array($v) && !isset($v['resources']); })
                        ->then(function ($v) {
                            $e = $v['enabled'];
                            unset($v['enabled']);

                            return array('enabled' => $e, 'resources' => $v);
                        })
                    ->end()
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('resource')
                    ->children()
                        ->arrayNode('resources')
                            ->requiresAtLeastOneElement()
                            ->defaultValue(array('default' => array(class_exists(SemaphoreStore::class) && SemaphoreStore::isSupported() ? 'semaphore' : 'flock')))
                            ->beforeNormalization()
                                ->ifString()->then(function ($v) { return array('default' => $v); })
                            ->end()
                            ->beforeNormalization()
                                ->ifTrue(function ($v) { return \is_array($v) && array_keys($v) === range(0, \count($v) - 1); })
                                ->then(function ($v) { return array('default' => $v); })
                            ->end()
                            ->prototype('array')
                                ->beforeNormalization()->ifString()->then(function ($v) { return array($v); })->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addWebLinkSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('web_link')
                    ->info('web links configuration')
                    ->{!class_exists(FullStack::class) && class_exists(HttpHeaderSerializer::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                ->end()
            ->end()
        ;
    }

    private function addMessengerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('messenger')
                    ->info('Messenger configuration')
                    ->{!class_exists(FullStack::class) && interface_exists(MessageBusInterface::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->fixXmlConfig('transport')
                    ->fixXmlConfig('bus', 'buses')
                    ->children()
                        ->arrayNode('routing')
                            ->useAttributeAsKey('message_class')
                            ->beforeNormalization()
                                ->always()
                                ->then(function ($config) {
                                    if (!\is_array($config)) {
                                        return array();
                                    }

                                    $newConfig = array();
                                    foreach ($config as $k => $v) {
                                        if (!\is_int($k)) {
                                            $newConfig[$k] = array(
                                                'senders' => $v['senders'] ?? (\is_array($v) ? array_values($v) : array($v)),
                                                'send_and_handle' => $v['send_and_handle'] ?? false,
                                            );
                                        } else {
                                            $newConfig[$v['message-class']]['senders'] = array_map(
                                                function ($a) {
                                                    return \is_string($a) ? $a : $a['service'];
                                                },
                                                array_values($v['sender'])
                                            );
                                            $newConfig[$v['message-class']]['send-and-handle'] = $v['send-and-handle'] ?? false;
                                        }
                                    }

                                    return $newConfig;
                                })
                            ->end()
                            ->prototype('array')
                                ->children()
                                    ->arrayNode('senders')
                                        ->requiresAtLeastOneElement()
                                        ->prototype('scalar')->end()
                                    ->end()
                                    ->booleanNode('send_and_handle')->defaultFalse()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('serializer')
                            ->{!class_exists(FullStack::class) && class_exists(Serializer::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('format')->defaultValue('json')->end()
                                ->arrayNode('context')
                                    ->normalizeKeys(false)
                                    ->useAttributeAsKey('name')
                                    ->defaultValue(array())
                                    ->prototype('variable')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('encoder')->defaultValue('messenger.transport.serializer')->end()
                        ->scalarNode('decoder')->defaultValue('messenger.transport.serializer')->end()
                        ->arrayNode('transports')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function (string $dsn) {
                                        return array('dsn' => $dsn);
                                    })
                                ->end()
                                ->fixXmlConfig('option')
                                ->children()
                                    ->scalarNode('dsn')->end()
                                    ->arrayNode('options')
                                        ->normalizeKeys(false)
                                        ->defaultValue(array())
                                        ->prototype('variable')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('default_bus')->defaultValue(null)->end()
                        ->arrayNode('buses')
                            ->defaultValue(array('messenger.bus.default' => array('default_middleware' => true, 'middleware' => array())))
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('default_middleware')->defaultTrue()->end()
                                    ->arrayNode('middleware')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function (string $middleware) {
                                                return array($middleware);
                                            })
                                        ->end()
                                        ->defaultValue(array())
                                        ->prototype('array')
                                            ->beforeNormalization()
                                                ->always()
                                                ->then(function ($middleware): array {
                                                    if (!\is_array($middleware)) {
                                                        return array('id' => $middleware);
                                                    }
                                                    if (isset($middleware['id'])) {
                                                        return $middleware;
                                                    }
                                                    if (\count($middleware) > 1) {
                                                        throw new \InvalidArgumentException(sprintf('There is an error at path "framework.messenger" in one of the buses middleware definitions: expected a single entry for a middleware item config, with factory id as key and arguments as value. Got "%s".', json_encode($middleware)));
                                                    }

                                                    return array(
                                                        'id' => key($middleware),
                                                        'arguments' => current($middleware),
                                                    );
                                                })
                                            ->end()
                                            ->fixXmlConfig('argument')
                                            ->children()
                                                ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                                                ->arrayNode('arguments')
                                                    ->normalizeKeys(false)
                                                    ->defaultValue(array())
                                                    ->prototype('variable')
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
