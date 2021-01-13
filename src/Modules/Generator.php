<?php
namespace Starbug\Composer\Modules;

use Composer\Installer\InstallationManager;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;
use MJS\TopSort\Implementations\GroupedStringSort;

class Generator {
  protected $config;
  public function __construct(array $config) {
    $this->config = $config + [
      "types" => [],
      "parameters" => []
    ];
  }
  public function dump($vendorDir, InstallationManager $installationManager, InstalledRepositoryInterface $repo) {
    $packages = $this->getModules($repo, array_keys($this->config["types"]));
    $index = $this->getSort($packages);
    $modules = [];
    foreach ($index as $module) {
      $modules[] = $this->getModuleEntry($packages[$module], $installationManager);
    }
    $this->writeToFile($modules, $vendorDir);
  }

  protected function getModules(InstalledRepositoryInterface $repo, array $types) {
    $packages = $repo->getPackages();
    $modules = [];
    foreach ($packages as $package) {
      if (in_array($package->getType(), $types)) {
        $modules[$package->getName()] = $package;
      }
    }
    return $modules;
  }

  protected function getSort($modules) {
    $sorter = new GroupedStringSort();
    foreach ($modules as $name => $package) {
      $requires = array_filter(array_keys($package->getRequires()), function ($entry) use ($modules) {
        return isset($modules[$entry]);
      });
      $sorter->add($name, $this->config["types"][$package->getType()], $requires);
    }
    return $sorter->sort();
  }

  protected function getModuleEntry(PackageInterface $package, InstallationManager $installationManager) {
    $name = $package->getPrettyName();
    if (strpos($name, '/') !== false) {
        $name = explode('/', $name)[1];
    }
    $type = $this->config["types"][$package->getType()];
    $path = rtrim($installationManager->getInstallPath($package), "/");
    $path = $this->replaceCwd($path);
    $entry = '"'.$name."\" => [".
      "\n    \"type\" => \"".$type."\",".
      "\n    \"path\" => \"".$path."\"";
    foreach ($this->config["parameters"] as $parameter) {
      $value = $package->getExtra()[$parameter];
      $entry .= ",\n    \"".$parameter."\" => \"".$value."\"";
    }
    $entry .= "\n  ]";
    return $entry;
  }

  protected function writeToFile($modules, $vendorDir) {
    $content = $this->getOutputPrefix();
    $content .= "\n  ".implode(",\n  ", $modules)."\n";
    $content .= $this->getOutputSuffix();
    $this->getFilesystem()
      ->filePutContentsIfModified($vendorDir . "/modules.php", $content);
  }

  protected function replaceCwd($path) {
    $cwd = getcwd() . "/";
    if (substr($path, 0, strlen($cwd)) == $cwd) {
      $path = substr($path, strlen($cwd));
    }
    return $path;
  }

  protected function getFilesystem() {
    return new Filesystem();
  }

  protected function getOutputPrefix() {
    return <<<PHP
<?php
return [
PHP;
  }

  protected function getOutputSuffix() {
    return <<<PHP
];
PHP;
  }
}
