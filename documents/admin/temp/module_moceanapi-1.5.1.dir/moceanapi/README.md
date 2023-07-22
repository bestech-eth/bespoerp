# MOCEANAPI FOR [DOLIBARR ERP CRM](https://www.bespo.et)

## Features

- Send SMS to Contacts, Third Party associated with Projects, Invoice, Supplier Order
- Send Bulk SMS to specific third parties (prospect, customer, countries and many more)
- Automate SMS notification when Contacts are created or disabled/enabled
- Automate SMS notification to Third Party when Invoice is created, validated, updated, paid
- Automate SMS notification to Third Party when Project lead status changes to prospection, qualification, proposal, negotiation
- Automate SMS notification to Third Party when Supplier Order is created, validated, approved, refused, dispatched

## Screenshots

Screenshots are available [here](./_screenshots/readme.md)

## Translations

Translations can be completed manually by editing files into directories *langs*.


## Installation

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

- Clone the repository in https://github.com/MoceanAPI/moceanapi-dolibarr

```sh
cd /path/to/dolibarr/custom
git clone https://github.com/MoceanAPI/moceanapi-dolibarr
```

### Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

## Licenses

### Main code

GPLv3

<a href="https://www.flaticon.com/free-icons/sms" title="sms icons">Sms icons created by Pixel perfect - Flaticon</a>

### Documentation

All texts and readmes are licensed under GFDL.

### Support & Feature Request

Raise a support ticket to support@moceanapi.com
