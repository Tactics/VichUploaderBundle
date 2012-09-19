<?php

namespace Vich\UploaderBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Vich\UploaderBundle\DependencyInjection\Configuration;

/**
 * VichUploaderExtension.
 *
 * @author Dustin Dobervich <ddobervich@gmail.com>
 */
class VichUploaderExtension extends Extension
{
    /**
     * @var array $tagMap
     */
    protected $tagMap = array(
        'orm' => 'doctrine.event_subscriber',
        'mongodb' => 'doctrine.odm.mongodb.event_subscriber',
        'propel' => '__propel__'
    );

    /**
     * @var array $adapterMap
     */
    protected $adapterMap = array(
        'orm' => 'Vich\UploaderBundle\Adapter\ORM\DoctrineORMAdapter',
        'mongodb' => 'Vich\UploaderBundle\Adapter\ODM\MongoDB\MongoDBAdapter',
        'propel' => 'Vich\UploaderBundle\Adapter\ORM\PropelORMAdapter'
    );

    /**
     * Loads the extension.
     *
     * @param array            $configs   The configuration
     * @param ContainerBuilder $container The container builder
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $driver = strtolower($config['db_driver']);
        if (!in_array($driver, array_keys($this->tagMap))) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid "db_driver" configuration option specified: "%s"',
                    $driver
                )
            );
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $toLoad = array(
            'adapter.xml', 'listener.xml', 'storage.xml', 'injector.xml',
            'templating.xml', 'driver.xml', 'factory.xml'
        );
        foreach ($toLoad as $file) {
            $loader->load($file);
        }

        if ($config['gaufrette']) {
            $loader->load('gaufrette.xml');
        }

        if ($config['twig']) {
            $loader->load('twig.xml');
        }
        
        $mappings = isset($config['mappings']) ? $config['mappings'] : array();
        $container->setParameter('vich_uploader.mappings', $mappings);
        
        $container->setParameter('vich_uploader.storage_service', $config['storage']);
        $container->setParameter('vich_uploader.adapter.class', $this->adapterMap[$driver]);
                
        if ($driver != 'propel')
        {
            // single listener
            $container->getDefinition('vich_uploader.listener.uploader')->addTag($this->tagMap[$driver]);
        }
        else if (isset($config['propel_classes'])) 
        {
            // propel needs a listener per object class
            $uploaderListenerDefinition = $container->getDefinition('vich_uploader.listener.uploader');
            $uploaderListenerDefinition->setClass('Vich\UploaderBundle\EventListener\PropelUploaderListener');
            
            foreach ($config['propel_classes'] as $class)
            {
                $definition = clone($uploaderListenerDefinition);
                
                $definition->addTag('propel.event_listener', array('class' => $class, 'event' => 'propel.pre_save'));
                $definition->addTag('propel.event_listener', array('class' => $class, 'event' => 'propel.pre_delete'));
                $definition->addTag('propel.event_listener', array('class' => $class, 'event' => 'propel.post_hydrate'));
                                
                $container->setDefinition('vich_uploader.listener.uploader.' . strtr($class, '\\', '.'),  $definition);
            }
         
            $container->removeDefinition('vich_uploader.listener.uploader');
        }
    }
}
