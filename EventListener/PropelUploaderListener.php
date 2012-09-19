<?php

namespace Vich\UploaderBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Vich\UploaderBundle\Storage\StorageInterface;
use Vich\UploaderBundle\Adapter\AdapterInterface;
use Vich\UploaderBundle\Injector\FileInjectorInterface;
use Vich\UploaderBundle\Driver\AnnotationDriver;

/**
 * UploaderListener.
 *
 * @author Dustin Dobervich <ddobervich@gmail.com>
 */
class PropelUploaderListener
{
    /**
     * @var \Vich\UploaderBundle\Adapter\AdapterInterface $adapter
     */
    protected $adapter;

    /**
     * @var \Vich\UploaderBundle\Driver\AnnotationDriver $driver
     */
    protected $driver;

    /**
     * @var \Vich\UploaderBundle\Storage\StorageInterface $storage
     */
    protected $storage;

    /**
     * @var \Vich\UploaderBundle\Injector\FileInjectorInterface $injector
     */
    protected $injector;

    /**
     * Constructs a new instance of UploaderListener.
     *
     * @param \Vich\UploaderBundle\Adapter\AdapterInterface       $adapter  The adapter.
     * @param \Vich\UploaderBundle\Driver\AnnotationDriver        $driver   The driver.
     * @param \Vich\UploaderBundle\Storage\StorageInterface       $storage  The storage.
     * @param \Vich\UploaderBundle\Injector\FileInjectorInterface $injector The injector.
     */
    public function __construct(AdapterInterface $adapter, AnnotationDriver $driver, StorageInterface $storage, FileInjectorInterface $injector)
    {
        $this->adapter = $adapter;
        $this->driver = $driver;
        $this->storage = $storage;
        $this->injector = $injector;
    }
    
    
    /**
     * Checks for for file to upload.
     *
     * @param \Doctrine\Common\EventArgs $args The event arguments.
     */
    public function preSave(Event $event)
    {
        $obj = $event->getSubject();
        if ($this->isUploadable($obj)) {
            $this->storage->upload($obj);
            $fileName = $obj->getFile();
            $obj->setFile(null);
            $obj->setFile($fileName);
        }
    }

    /**
     * Update the file and file name if necessary.
     *
     * @param EventArgs $args The event arguments.
     */
    public function preUpdate(Event $event)
    {
        $obj = $event->getSubject();
        if ($this->isUploadable($obj)) {
            $this->storage->upload($obj);
            //$this->adapter->recomputeChangeSet($args);
        }
    }

    /**
     * Populates uploadable fields from filename properties
     * if necessary.
     *
     * @param \Doctrine\Common\EventArgs $args
     */
    public function postHydrate(Event $event)
    {
        $obj = $event->getSubject();
        if ($this->isUploadable($obj)) {
            $this->injector->injectFiles($obj);
        }
    }

    /**
     * Removes the file if necessary.
     *
     * @param EventArgs $args The event arguments.
     */
    public function postDelete(Event $event)
    {
        $obj = $event->getSubject();
        if ($this->isUploadable($obj)) {
            $this->storage->remove($obj);
        }
    }

    /**
     * Tests if the object is Uploadable.
     *
     * @param  object  $obj The object.
     * @return boolean True if uploadable, false otherwise.
     */
    protected function isUploadable($obj)
    {
        $class = $this->adapter->getReflectionClass($obj);

        return null !== $this->driver->readUploadable($class);
    }
}
