<?php

namespace Vich\UploaderBundle\Adapter\ORM;

use Vich\UploaderBundle\Adapter\AdapterInterface;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Proxy\Proxy;

/**
 * PropelORMAdapter.
 * 
 * There is no adapter needed for propel, so this class
 * contains only stubs.
 *
 * @author Dustin Dobervich <ddobervich@gmail.com>
 */
class PropelORMAdapter implements AdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getObjectFromArgs(EventArgs $e)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function recomputeChangeSet(EventArgs $e)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getReflectionClass($obj)
    {
        return new \ReflectionClass($obj);
    }
}
