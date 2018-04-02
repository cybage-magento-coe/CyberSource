<?php
/**
 * Cybage Cybersource
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is available on the World Wide Web at:
 * http://opensource.org/licenses/osl-3.0.php
 * If you are unable to access it on the World Wide Web, please send an email
 * To: Support_ecom@cybage.com.  We will send you a copy of the source file.
 *
 * @category  Cybersource_Payment_Method
 * @package   Cybage_Cybersource
 * @author    Cybage Software Pvt. Ltd. <Support_ecom@cybage.com>
 * @copyright 1995-2017 Cybage Software Pvt. Ltd., India
 *            http://www.cybage.com/pages/centers-of-excellence/ecommerce/ecommerce.aspx
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Cybage\Cybersource\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;

/**
 *
 */
class InstallSchema implements InstallSchemaInterface {

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        $quoteColumns = [
            'cybersource_token' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                [],
                'comment' => 'Cybersource Token',
            ],
        ];
        foreach ($quoteColumns as $name => $definition) {
            $connection->addColumn($installer->getTable('quote_payment'), $name, $definition);
        }

        $orderColumns = [
            'cybersource_token' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                [],
                'comment' => 'Cybersource Token',
            ],
        ];
        foreach ($orderColumns as $name => $definition) {
            $connection->addColumn($installer->getTable('sales_order_payment'), $name, $definition);
        }

        $invoiceColumns = [
            'cybersource_token' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                [],
                'comment' => 'Cybersource Token',
            ],
        ];
        foreach ($invoiceColumns as $name => $definition) {
            $connection->addColumn($installer->getTable('sales_invoice'), $name, $definition);
        }

        $creditColumns = [
            'cybersource_token' => [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '255',
                [],
                'comment' => 'Cybersource Token',
            ],
        ];
        foreach ($creditColumns as $name => $definition) {
            $connection->addColumn($installer->getTable('sales_creditmemo'), $name, $definition);
        }

        $installer->endSetup();
    }

}
