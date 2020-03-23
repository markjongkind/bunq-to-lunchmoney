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
 * bunq-to-lunchmoney installation, run once
 */

use bunq\Model\Generated\Endpoint\MonetaryAccountBank;

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/functions.php');

echo '<h2>bunq to Lunch Money</h2>';
echo '<hr />';

try 
{
    // Reuse existing connection
    restoreApiContext();
} 
catch (Exception $e) 
{
    // Create the bunq connection
    initBunqConnection();
}

// Get user
$user = getUser();

// Add callback URL
addCallbackUrl($user, bunqCallbackUrl);

// First get all monetary accounts for the authenticated user
$MonetaryAccountBankList = MonetaryAccountBank::listing();
$LunchMoneyTransactions = [];

echo '<strong>Bunq accounts:</strong><br />';
echo '<table><tr><th>Account ID</th><th>Description</th></tr>';

foreach ($MonetaryAccountBankList->getValue() as $MonetaryAccountBank) 
{
    if ($MonetaryAccountBank->getStatus() === 'ACTIVE') 
        echo '<tr><td><code>' . $MonetaryAccountBank->getId() . '</code></td><td>' . $MonetaryAccountBank->getDescription() . '</td></tr>';
}
echo '</table>';

echo '<hr />';

$LunchMoneyAssetList = listLunchMoneyAssets();

echo '<strong>Lunch Money assets:</strong><br />';
echo '<table><tr><th>Asset ID</th><th>Description</th></tr>';

foreach ($LunchMoneyAssetList as $LunchMoneyAsset) 
{
    echo '<tr><td><code>' . $LunchMoneyAsset['id'] . '</code></td><td>' . $LunchMoneyAsset['name'] . '</td></tr>';
}
echo '</table>';

?>