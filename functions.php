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
 * bunq-to-lunchmoney functions
 */

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Model\Core\NotificationFilterUrlUserInternal;
use bunq\Model\Generated\Object\NotificationFilterUrl;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\Payment;

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/config.php');

/**
 * Start the bunq connection
 */
function initBunqConnection()
{
    try 
    {
        if( bunqIsSandbox === false )
            $environmentType = BunqEnumApiEnvironmentType::PRODUCTION();
        else
            $environmentType = BunqEnumApiEnvironmentType::SANDBOX();

        $apiContext = ApiContext::create(
            $environmentType, 
            bunqApiKey, 
            bunqDeviceDescription, 
            bunqPermittedIps
        );

        BunqContext::loadApiContext($apiContext);

        $apiContext->save(bunqFileName);

        return $apiContext;
    } 
    catch (Exception $e) 
    {
        echo 'Problem with bunq connection: ' . $e->getMessage();
    }
}

/**
 * Restore an existing API context
 */
function restoreApiContext()
{
    $apiContext = ApiContext::restore(bunqFileName);
    $apiContext->ensureSessionActive();
    BunqContext::loadApiContext($apiContext);

    return $apiContext;
}

/**
 * Return the UserPerson or UserCompany
 */
function getUser()
{
    if( bunqIsCompany === true )
        return BunqContext::getUserContext()->getUserCompany();
    else
        return BunqContext::getUserContext()->getUserPerson();
}

/**
 * Add callback URL for the active user
 */
function addCallbackUrl($user, $callbackUrl)
{
    try 
    {
        $user = getUser();
        $allCurrentNotificationFilter = $user->getNotificationFilters();
        $callbackUrlFound = false;

        foreach( $allCurrentNotificationFilter as $notificationFilter )
        {
            if($notificationFilter->getNotificationTarget() === $callbackUrl)
                $callbackUrlFound = true;
        }

        // Add callbackUrl if not already exists
        if( $callbackUrlFound === false )
        {
            echo 'Add callback URL: ' . $callbackUrl . '<hr />';
            $NotificationFilterUrl = new NotificationFilterUrl('MUTATION', $callbackUrl);
            NotificationFilterUrlUserInternal::createWithListResponse([$NotificationFilterUrl]);
        }
    } 
    catch (Exception $e) 
    {
        echo $e->getMessage();

        print_r($e);
    }
}

/**
 * Get transactions from bunq
 */
function getBunqTransactions()
{
    try 
    {
        $Transactions = [];

        // Get all monetary accounts for the authenticated user
        $MonetaryAccountBankList = MonetaryAccountBank::listing();

        foreach( $MonetaryAccountBankList->getValue() as $MonetaryAccountBank )
        {
            if( $MonetaryAccountBank->getStatus() !== 'ACTIVE' ) 
                continue;
            
            $MonetaryAccountBankId = $MonetaryAccountBank->getId();
            
            if( 
                is_array(lunchMoneyMapping) && 
                count(lunchMoneyMapping) > 0 && 
                !array_key_exists($MonetaryAccountBankId, lunchMoneyMapping) 
            ) 
                continue;

            // Get payments for monetary account
            $PaymentsList = Payment::listing($MonetaryAccountBankId);

            foreach( $PaymentsList->getValue() as $Payment )
            {
                $CreatedDateTime = DateTime::createFromFormat('Y-m-d H:i:s.u', $Payment->getCreated() );
                
                // Only sync recent transactions 
                if( strtotime($CreatedDateTime->format('c')) < strtotime('-7 day') )
                    continue;

                // Follow the Lunch Money model for a Transaction
                $transaction = [
                    'external_id' => $Payment->getId(),
                    'date' => $CreatedDateTime->format('Y-m-d'),
                    'amount' => $Payment->getAmount()->getValue(),
                    'notes' => trim(preg_replace('/\s+/', ' ', $Payment->getDescription())),
                    'payee' => $Payment->getCounterpartyAlias()->getDisplayName(),
                ];

                if( is_array(lunchMoneyMapping) && count(lunchMoneyMapping) > 0 && isset(lunchMoneyMapping[$MonetaryAccountBankId]) )
                    $transaction['asset_id'] = lunchMoneyMapping[$MonetaryAccountBankId];

                array_push($Transactions, $transaction);
            }   
        }

        return $Transactions;
    } 
    catch (Exception $e) 
    {
        echo $e->getMessage();
    }
}

/**
 * List Lunch Money assets
 */
function listLunchMoneyAssets()
{
    try 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, lunchMoneyApiUrl . '/assets');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 
            'Authorization: Bearer ' . lunchMoneyAccessToken,
        ]);
        $response = curl_exec($ch);

        if (!$response)
            throw new Exception('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch), 1);

        $response_array = json_decode($response, true);

        if( isset($response_array['name']) && $response_array['name'] === 'Error' )
            throw new Exception($response_array['message'], 1);

        curl_close($ch);

        return $response_array['assets'];
    }
    catch (Exception $e) 
    {
        echo 'Lunch Money error: ' . $e->getMessage();
        return false;
    }
}

/**
 * Create transactions in Lunch Money
 */
function uploadLunchMoneyTransactions($transactions)
{
    try 
    {
        $json_array = [
            'transactions' => $transactions,
            'apply_rules' => true,
            'check_for_recurring' => true,
            'debit_as_negative' => true,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, lunchMoneyApiUrl . '/transactions');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . lunchMoneyAccessToken,
            'Content-Type: application/json; charset=utf-8',
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_array));

        $response = curl_exec($ch);

        if (!$response)
            throw new Exception('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch), 1);

        curl_close($ch);

        $response_array = json_decode($response, true);

        if( isset($response_array['name']) && $response_array['name'] === 'Error' )
            throw new Exception($response_array['message'], 1);

        return $response_array;
    } 
    catch (Exception $e) 
    {
        echo 'Lunch Money error: ' . $e->getMessage();
        return false;
    }
}

?>