<a href="https://packagist.org/packages/trace-one/composer-azure-plugin"><img src="https://poser.pugx.org/trace-one/composer-azure-plugin/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/trace-one/composer-azure-plugin"><img src="https://poser.pugx.org/trace-one/composer-azure-plugin/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/trace-one/composer-azure-plugin">
    <img src="https://poser.pugx.org/trace-one/composer-azure-plugin/license.svg" alt="License" />
  </a>

# Composer Azure Plugin
Composer Azure plugin is an attempt to use Composer with Azure DevOps artifacts, via universal packages.

## Install
Composer Azure Plugin requires [Composer 1.0.0](https://getcomposer.org/) or
newer. It should be installed globally.

```
$ composer global require trace-one/composer-azure-plugin
```

You have to be logged in via the [Azure command line interface](https://docs.microsoft.com/fr-fr/cli/azure/?view=azure-cli-latest).

## Usage
```json
{
    "require": {
        "vendor-name/my-package": "1.0.0"
    },
    "extra": {
        "azure-repositories": [
            {
                "organization": "organization-name.visualstudio.com",
                "feed": "MyFeed",
                "packages": [
                    "vendor-name/my-package"
                ]
            }
        ]
    }
}
```

## Publishing a package
Universal packages do not support vendor names, we then use a dot as separator. Once inside the folder of the package you want to publish, simply publish with the correct name.

```
az artifacts universal publish
    --organization https://organization-name.visualstudio.com/
    --feed MyFeed
    --name vendor-name.my-package
    --version 1.0.0
    --description "My PHP package"
    --path .
```

## Known limitations
This package is a very early attempt, and has a few known limitations:
* **No version management**: the version specified into the package.json file has to be the exact required version
* **No composer publish command**: you have to publish your packages using the default Azure CLI

Feel free to suggest any improvement!