# Plugins

The following is valid for Neucore 1.42.0 and [neucore-plugin](https://github.com/tkhamez/neucore-plugin)
0.10.0 and above.

**Service plugins** are available to users from the "Services" menu. For an example see e.g.
[Neucore Discord Plugin](https://github.com/tkhamez/neucore-discord-plugin).

**General plugins** can add menu items to their own URL.

## Create a plugin

- Create a new PHP application with composer and install the neucore-plugin package:
  ```shell script
  composer init
  composer require tkhamez/neucore-plugin
  ```
- Copy `vendor/tkhamez/neucore-plugin/plugin.yml` to `plugin.yml` in the root directory of the new plugin
  and adjust values.
- Create a new PHP class that implements `Neucore\Plugin\ServiceInterface` or `Neucore\Plugin\GeneralInterface`,
  depending on what kind of plugin (general or service) you want to create. It is also possible to implement both
  in the same class.

Neucore automatically loads all classes from the namespace that is configured with the `psr4_prefix` and 
`psr4_path` values from the `plugin.yml` file.

You can also use all classes and libraries provided by the `neucore-plugin` package and by the `FactoryInterface`
object that is provided by Neucore in the plugin class constructor. However, note that the libraries can be updated 
with each Neucore release.

Besides that, **do not use** any class from Neucore or any library that Neucore provides. Those can change or
be removed without notice.

## Install a plugin

- Set the `NEUCORE_PLUGINS_INSTALL_DIR` environment variable (e.g. `/plugins`).
- Copy the plugin into that directory within its own subdirectory (so that the plugin.yml file is e.g. 
  at `/plugins/discord/plugin.yml`).
- In Neucore, go to Administration -> Plugins and add a new plugin.
- Configure the plugin, at the very least choose a plugin from the dropdown list.