<?php
namespace Starbug\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Starbug\Composer\Modules\Generator;

class ModulesPlugin implements PluginInterface, EventSubscriberInterface {

  /**
   * Composer object.
   *
   * @var Composer
   */
  protected $composer;
  /**
   * Facade for IO.
   *
   * @var IOInterface
   */
  protected $ioFacade;

  public function activate(Composer $composer, IOInterface $ioFacade) {
    $this->composer = $composer;
    $this->ioFacade = $ioFacade;
  }

  public function deactivate(Composer $composer, IOInterface $ioFacade) {
  }

  public function uninstall(Composer $composer, IOInterface $ioFacade) {
  }

  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_INSTALL_CMD => "dumpModules",
      ScriptEvents::POST_UPDATE_CMD => "dumpModules"
    ];
  }

  public function dumpModules(Event $event) {
    $vendorDir = $this->composer->getConfig()->get('vendor-dir');
    $installationManager = $this->composer->getInstallationManager();
    $repo = $this->composer->getRepositoryManager()->getLocalRepository();
    $rootExtra = $this->composer->getPackage()->getExtra();
    $types = $rootExtra["modules-plugin"] ?? [];
    $generator = new Generator($types);
    $generator->dump($vendorDir, $installationManager, $repo);
  }
}
