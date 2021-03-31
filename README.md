<p align="center">
  <img src="https://helloextend-static-assets.s3.amazonaws.com/extend-shield-logo.png" />
  <h1 align="center">Extend - Magento M2 Extension</h1>
</p>

Extend provides an easy way for any merchant to offer extended protection plans, generating new revenue, increasing purchase conversion, and dramatically improving the customer experience. 

## Useful Links

- General Questions: [hello@extend.com](hello@extend.com)

- Integration Questions: [integrations@extend.com](integrations@extend.com)

- Create an Extend Account: [https://merchants.extend.com/onboarding/signup/platform](https://merchants.extend.com/onboarding/signup/platform)

- Extend Merchant Portal:

    - Production: [https://merchants.extend.com](https://merchants.extend.com)

    - Sandbox: [https://demo.merchants.extend.com](https://demo.merchants.extend.com)

# Requirements 

## Version Support
- The module is built for Magento 2 versions: 
    - Magento 2.2.x
    - Magento 2.3.x
    - Magento 2.4.x

## Browser Support
- Internet Explorer 11 or later, Microsoft Edge, latest–1
- Firefox latest, latest–1 (any operating system)
- Chrome latest, latest–1 (any operating system)
- Safari latest, latest–1 (Mac OS only)
- Safari Mobile for iPad 2, iPad Mini, iPad with Retina Display (iOS 7 or later), for desktop storefront
- Safari Mobile for iPhone 4 or later; iOS 7 or later, for mobile storefront
- Chrome for mobile latest–1 (Android 4 or later) for the mobile storefront

## Not Supported
- The Extend module does not support bundled, grouped, virtual, or downloadable Magento products without customization
- The Extend Module does not support gift cards

## Language
- The Extend module is only available in the English Language at this time

# Installation

## Install via SSH
1. Download and unzip package from Magento’s marketplace
2. Using SSH or sFTP move folders /app/code and /lib to Magento’s root folder
3. Using a shell on Magento’s root folder, the following commands must be executed: 
```zsh
$ php bin/magento module:enable Extend_Warranty
$ php bin/magento setup:upgrade
$ php bin/magento cache:clean
```

## Install via Composer
To install/update the extension with Composer, the following commands must be executed:
```zsh
$ composer config repositories.extend git “https://github.com/helloextend/magento-extension/”
$ composer require extend/module-warranty:dev-client/mpex
$ php bin/magento setup:upgrade
```

If you have version compatibility problems or requirements when installing via composer it would help to execute the command with:
```zsh
$ COMPOSER_MEMORY_LIMIT=-1 composer require extend/module-warranty:dev-client/mpex --ignore-platform-reqs
```

Please contact your Merchant Services Manager for the M2 extension user guide or if you have any additional questions.
