<?php

/**
 * bunq-to-lunchmoney
 * bunq to Lunch Money
 *
 * @author  Mark Jongkind <mark@backscreen.nl>
 * @license http://opensource.org/licenses/mit-license.php MIT License
 *
 * Uses the official bunq PHP SDK
 * https://github.com/bunq/sdk_php
 * 
 * 
 * bunq-to-lunchmoney callback handler
 */

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/functions.php');

try 
{
    echo '<h2>bunq to Lunch Money</h2>';
    echo '<hr />';

    restoreApiContext();

    // Get recent bunq transations
    $bunqTransactions = getBunqTransactions();

    // Upload transactions to Lunch Money
    $result = uploadLunchMoneyTransactions($bunqTransactions);

    if( $result !== false ) 
    {
        echo 'Retrieved <strong>' . count($bunqTransactions) . '</strong> transactions from bunq<br />';
        echo 'Created <strong>' . count($result['ids']) . '</strong> new transactions in Lunch Money';
    }
    else
    {
        echo 'No transactions created in Lunch Money';
    }
} 
catch (Exception $e) 
{
    echo $e->getMessage();
}

?>