<?php

namespace Chronomail\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\Version\VersionParser;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\ProcessExecutor;

/**
 * @category Plugins
 * @package  PluginInstaller
 * @author   Till Klampaeckel <till@php.net>
 * @author   Thomas Bruederli <thomas@chronomail.net>
 * @license  GPL-3.0+
 * @version  GIT: <git_id>
 * @link     http://github.com/chronomail/plugin-installer
 */
class PluginInstaller extends LibraryInstaller
{
    const INSTALLER_TYPE = 'chronomail-plugin';

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        static $vendorDir;
        if ($vendorDir === null) {
            $vendorDir = $this->getVendorDir();
        }

        return sprintf('%s/%s', $vendorDir, $this->getPluginName($package));
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->chronomailVersionCheck($package);
        parent::install($repo, $package);

        // post-install: activate plugin in Chronomail config
        $config_file = $this->chronomailConfigFile();
        $plugin_name = $this->getPluginName($package);
        $plugin_dir = $this->getVendorDir() . DIRECTORY_SEPARATOR . $plugin_name;
        $extra = $package->getExtra();
        $plugin_name = $this->getPluginName($package);

        if (is_writeable($config_file) && php_sapi_name() == 'cli') {
            $answer = $this->io->askConfirmation("Do you want to activate the plugin $plugin_name? [N|y] ", false);
            if (true === $answer) {
                $this->chronomailAlterConfig($plugin_name, true);
            }
        }

        // copy config.inc.php.dist -> config.inc.php
        if (is_file($plugin_dir . DIRECTORY_SEPARATOR . 'config.inc.php.dist') && !is_file($plugin_dir . DIRECTORY_SEPARATOR . 'config.inc.php') && is_writeable($plugin_dir)) {
            $this->io->write("<info>Creating plugin config file</info>");
            copy($plugin_dir . DIRECTORY_SEPARATOR . 'config.inc.php.dist', $plugin_dir . DIRECTORY_SEPARATOR . 'config.inc.php');
        }

        // initialize database schema
        if (!empty($extra['chronomail']['sql-dir'])) {
            if ($sqldir = realpath($plugin_dir . DIRECTORY_SEPARATOR . $extra['chronomail']['sql-dir'])) {
                $this->io->write("<info>Running database initialization script for $plugin_name</info>");
                system(getcwd() . "/vendor/bin/chronomailinitdb.sh --package=$plugin_name --dir=$sqldir");
            }
        }

        // run post-install script
        if (!empty($extra['chronomail']['post-install-script'])) {
            $this->chronomailRunScript($extra['chronomail']['post-install-script'], $package);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->chronomailVersionCheck($target);
        parent::update($repo, $initial, $target);

        $extra = $target->getExtra();

        // trigger updatedb.sh
        if (!empty($extra['chronomail']['sql-dir'])) {
            $plugin_name = $this->getPluginName($target);
            $plugin_dir = $this->getVendorDir() . DIRECTORY_SEPARATOR . $plugin_name;

            if ($sqldir = realpath($plugin_dir . DIRECTORY_SEPARATOR . $extra['chronomail']['sql-dir'])) {
                $this->io->write("<info>Updating database schema for $plugin_name</info>");
                system(getcwd() . "/bin/updatedb.sh --package=$plugin_name --dir=$sqldir", $res);
            }
        }

        // run post-update script
        if (!empty($extra['chronomail']['post-update-script'])) {
            $this->chronomailRunScript($extra['chronomail']['post-update-script'], $target);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::uninstall($repo, $package);

        // post-uninstall: deactivate plugin
        $plugin_name = $this->getPluginName($package);
        $this->chronomailAlterConfig($plugin_name, false);

        // run post-uninstall script
        $extra = $package->getExtra();
        if (!empty($extra['chronomail']['post-uninstall-script'])) {
            $this->chronomailRunScript($extra['chronomail']['post-uninstall-script'], $package);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === self::INSTALLER_TYPE;
    }

    /**
     * Setup vendor directory to one of these two:
     *  ./plugins
     *
     * @return string
     */
    public function getVendorDir()
    {
        $pluginDir  = getcwd();
        $pluginDir .= '/plugins';

        return $pluginDir;
    }

    /**
     * Extract the (valid) plugin name from the package object
     */
    private function getPluginName(PackageInterface $package)
    {
        @list($vendor, $pluginName) = explode('/', $package->getPrettyName());

        return strtr($pluginName, '-', '_');
    }

    /**
     * Check version requirements from the "extra" block of a package
     * against the local Chronomail version
     */
    private function chronomailVersionCheck($package)
    {
        $parser = new VersionParser;

        // read chronomail version from iniset
        $rootdir = getcwd();
        $iniset = @file_get_contents($rootdir . '/program/include/iniset.php');
        if (preg_match('/define\(.RCMAIL_VERSION.,\s*.([0-9.]+[a-z-]*)?/', $iniset, $m)) {
            $chronomailVersion = $parser->normalize(str_replace('-git', '.999', $m[1]));
        } else {
            throw new \Exception("Unable to find a Chronomail installation in $rootdir");
        }

        $extra = $package->getExtra();

        if (!empty($extra['chronomail'])) {
            foreach (array('min-version' => '>=', 'max-version' => '<=') as $key => $operator) {
                if (!empty($extra['chronomail'][$key])) {
                    $version = $parser->normalize(str_replace('-git', '.999', $extra['chronomail'][$key]));
                    if (!self::versionCompare($chronomailVersion, $version, $operator)) {
                        throw new \Exception("Version check failed! " . $package->getName() . " requires Chronomail version $operator $version, $chronomailVersion was detected.");
                    }
                }
            }
        }
    }

    /**
     * Add or remove the given plugin to the list of active plugins in the Chronomail config.
     */
    private function chronomailAlterConfig($plugin_name, $add)
    {
        $config_file = $this->chronomailConfigFile();
        @include($config_file);
        $success = false;
        $varname = '$config';

        if (empty($config) && !empty($chronomail_config)) {
            $config  = $chronomail_config;
            $varname = '$chronomail_config';
        }

        if (is_array($config) && is_writeable($config_file)) {
            $config_templ   = @file_get_contents($config_file) ?: '';
            $config_plugins = !empty($config['plugins']) ? ((array) $config['plugins']) : array();
            $active_plugins = $config_plugins;

            if ($add && !in_array($plugin_name, $active_plugins)) {
                $active_plugins[] = $plugin_name;
            } elseif (!$add && ($i = array_search($plugin_name, $active_plugins)) !== false) {
                unset($active_plugins[$i]);
            }

            if ($active_plugins != $config_plugins) {
                $count      = 0;
                $var_export = "array(\n\t'" . join("',\n\t'", $active_plugins) . "',\n);";
                $new_config = preg_replace(
                    "/(\\$varname\['plugins'\])\s+=\s+(.+);/Uims",
                    "\\1 = " . $var_export,
                    $config_templ, -1, $count);

                // 'plugins' option does not exist yet, add it...
                if (!$count) {
                    $var_txt    = "\n{$varname}['plugins'] = $var_export;\n";
                    $new_config = str_replace('?>', $var_txt . '?>', $config_templ, $count);

                    if (!$count) {
                        $new_config = $config_templ . $var_txt;
                    }
                }

                $success = file_put_contents($config_file, $new_config);
            }
        }

        if ($success && php_sapi_name() == 'cli') {
            $this->io->write("<info>Updated local config at $config_file</info>");
        }

        return $success;
    }

    /**
     * Helper method to get an absolute path to the local Chronomail config file
     */
    private function chronomailConfigFile()
    {
        return realpath(getcwd() . '/config/config.inc.php');
    }

    /**
     * Run the given script file
     */
    private function chronomailRunScript($script, PackageInterface $package)
    {
        $plugin_name = $this->getPluginName($package);
        $plugin_dir = $this->getVendorDir() . DIRECTORY_SEPARATOR . $plugin_name;

        // check for executable shell script
        if (($scriptfile = realpath($plugin_dir . DIRECTORY_SEPARATOR . $script)) && is_executable($scriptfile)) {
            $script = $scriptfile;
        }

        // run PHP script in Chronomail context
        if ($scriptfile && preg_match('/\.php$/', $scriptfile)) {
            $incdir = realpath(getcwd() . '/program/include');
            include_once($incdir . '/iniset.php');
            include($scriptfile);
        }
        // attempt to execute the given string as shell commands
        else {
            $process = new ProcessExecutor($this->io);
            $exitCode = $process->execute($script, null, $plugin_dir);
            if ($exitCode !== 0) {
                throw new \RuntimeException('Error executing script: '. $process->getErrorOutput(), $exitCode);
            }
        }
    }

    /**
     * version_compare() wrapper, originally from composer/semver
     */
    private static function versionCompare($a, $b, $operator, $compareBranches = false)
    {
        $aIsBranch = 'dev-' === substr($a, 0, 4);
        $bIsBranch = 'dev-' === substr($b, 0, 4);

        if ($aIsBranch && $bIsBranch) {
            return $operator === '==' && $a === $b;
        }

        // when branches are not comparable, we make sure dev branches never match anything
        if (!$compareBranches && ($aIsBranch || $bIsBranch)) {
            return false;
        }

        return version_compare($a, $b, $operator);
    }
}
