# Referral Plugin for Botble CMS

Now with upto UNLIMITED LEVELS!

Easily add a multi-level referral system to Botble CMS. This powerful plugin will enable your users to refer new users to your system Compatible with any model.

## 🧐 Features

Here are some of the project's best features:

*   Unlimited Referrals
*   Unlimited Referral Levels
*   Supports Any User Model
*   Custom Referral Aliases
*   Manually assign sponsors
*   Edit/Update Aliases
*   Cookieless Tracking
*   Customizable Query Parameter
*   Customizable Expiry Date
*   Dashboard widget for latest referrals

## Requirements

- Botble core 7.0.0 or higher.
- Skillcraft Core (sc-core) v2.1.0 or higher

## Installation

<h2>🛠️ Install via Admin Panel:</h2>

<p>1. Install From Marketplace</p>

<p>2. Go to the **Admin Panel**</p>

<p>3. Click on the **Plugins** tab</p>

<p>4. Click on the "Add new" button</p>

<p>5. Find the **SC Referral** plugin</p>

<p>6. Click on the "Install" button</p>


<h2>🛠️ Install manually:</h2>

<p>1. Download the plugin</p>

<p>2. Extract the downloaded file</p>

<p>3. Upload the extracted folder to the `platform/plugins` directory</p>

<p>4. Go to **Admin** > **Plugins** and click on the **Activate** button.</p>


## Registering Your Model

```php
if (defined('REFERRAL_MODULE_SCREEN_NAME')) {
    ReferralHookManager::registerHooks(YourUserModel::class, 'name');
    ReferralHookManager::registerFormHooks(YourUserModelsFormRequest::class, 'name');
}
```
A form request is registered to apply the rules to the added alias and sponsor meta boxes added to your supported user model. 


## Available Model Macros

```php
/**
 * Get Alias
 * return Model
 */
$yourUserModel->getAlias();

/**
 * Update Alias
 * @param string $alias
 * return Model
 */
$yourUserModel->updateAlias($alias);

/**
 * Update a Models Sponsor
 * @param int $sponsor_id
 * return Model
 */
$yourUserModel->updateSponsor($sponsor_id);

/**
 * Remove Sponsor
 * return Model
 */
$yourUserModel->unHookSponsor($referralModel);

/**
 * Remove a Specific Referral
 * return Model
 */
$yourUserModel->unHookReferral($referralModel);

/**
 * Remove all Referrals
 * return void
 */
$yourUserModel->unHookAllReferrals();

/**
 * Add a randomly generated alias
 * return void
 */
$yourUserModel->addMissingAlias();

/**
 * Get a Models Sponsor
 * return ?Model
 */
$yourUserModel->getSponsor();

/**
 * Get all referrals for a model
 * 
 * @var Model $this
 * return Collection
 */
$yourUserModel->getReferrals();

/**
 * Get referral link
 * 
 * @var Model $this
 * return string
 */
$yourUserModel->getReferralLink();

/**
 * Get the X level of referrals
 * 
 * @param Model $this The sponsor model
 * @param int $level The level of referrals
 * @return Collection
 */
$yourUserModel->getSubLevelReferrals(int $level = 1);

```

### Supported Botble Plugins

Although this plugin should work with any user auth (Customers, Vendors, Etc.). The following plugins are currently supported and a plugin table and route is provided. Being supported will register the model and edit form request with the plugin automatically. This can be disabled by setting ```SKILLCRAFT_REFERRAL_ENABLE_{PLUGIN NAME}_DEFAULT=false``` in your projects env.

Feel free to request official plugin support by reaching out to me or posting an issue. skillcraft.opensource@pm.com

- Skillcraft Account - v1.0.0+
- Members v2.0.0+

## Running Tests

Before running tests locally, you may have to run to resolve plugins testing namespace properly.

```
composer dump-autoload
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email skillcraft.security@pm.com instead of using the issue tracker.

## Credits

- [Skillcraft-io](https://github.com/skillcraft-io)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
