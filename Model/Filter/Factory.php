<?php

namespace Semknox\Productsearch\Model\Filter;

class Factory
{

    protected $_objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create  filter
     *
     * @param string $className
     * @param array $data
     * @return \Magento\Catalog\Model\Layer\Filter\Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($sxClass, $data)
    {
        $path = explode('\\', get_class($sxClass));
        $className = array_pop($path);
        $className = '\Semknox\Productsearch\Model\Filter\\' . $className;

        if(!class_exists($className)) return false;

        $filter = $this->_objectManager->create($className, $data);

        if (stripos('Option', $className) === false && !$filter instanceof \Semknox\Productsearch\Model\Filter\AbstractFilter) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('%1 doesn\'t extends \Semknox\Productsearch\Model\Filter\AbstractFilter', $className)
            );
        }
        return $filter;
    }
}
