# bunq to Lunch Money
Upload [Bunq](https://www.bunq.com/) transactions to [Lunch Money](https://lunchmoney.app/)

## Installation

```shell
$ composer install
```

## Usage

### Configuration settings
In order to start syncing transactions to Lunch Money, please update the `config.php` file.

```php
// Enter your bunq API key
// Go to bunq app and click the profile tab
// Tap 'Security & Settings' and then 'Developers'
// Go to 'API Keys' and click the plus icon
bunqApiKey = '[YOUR BUNQ API KEY]';
```
```php
// The callback URL must point to your (HTTPS) URL
bunqCallbackUrl = 'https://bunq-to-lunchmoney.example.com/callback.php';
```
```php
// Replace with your own secure location to store the API context details
bunqFileName = 'bunq.conf';
```
```php
bunqIsSandbox = true;
```
```php
bunqIsCompany = false;
```
```php
// Visit https://my.lunchmoney.app/developers to request an access token
unchMoneyAccessToken = '[YOUR LUNCH MONEY ACCESS TOKEN]';
```
```php
// It's possible to map your bunq accounts to your Lunch Money accounts (Assets)
// This way, transactions can be imported from/to the right accounts
// If you run install.php, you'll see a list of your bunq and Lunch Money accounts
lunchMoneyMapping = [];

// Example: 
lunchMoneyMapping = [
    'your_cash_bunq_accound_id' => 'your_cash_lunch_money_asset_id',
    'your_savings_bunq_accound_id' => 'your_savings_lunch_money_asset_id'
];
```

### Start bunq connection
After setting the right configuration, run `install.php` once to connect with bunq and create the callback URL.

### Callback
The callback URL will be called when a `MUTATION` event is triggered on one of your bunq accounts. The most recent transactions for each active bunq account (or mapped, see `lunchMoneyMapping`) will be uploaded to Lunch Money. Duplicate transactions will be automatically ignored by Lunch Money.
