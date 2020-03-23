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
 * bunq-to-lunchmoney configuration settings
 */

/** 
 * bunq settings
 */
const bunqApiKey = '[YOUR BUNQ API KEY]';
const bunqCallbackUrl = 'https://bunq-to-lunchmoney.example.com/callback.php';
const bunqFileName = 'bunq.conf'; // Replace with your own secure location to store the API context details
const bunqIsSandbox = true;
const bunqIsCompany = false;
const bunqDeviceDescription = 'bunq-to-lunchmoney';
const bunqPermittedIps = [];

/** 
 * Lunch Money settings
 */
const lunchMoneyAccessToken = '[YOUR LUNCH MONEY ACCESS TOKEN]';
// Map bunq MonetaryAccountBankId to the corresponding Lunch Money asset ID
const lunchMoneyMapping = [];
const lunchMoneyApiUrl = 'https://dev.lunchmoney.app/v1';

?>