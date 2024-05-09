<?php

declare(strict_types=1);

namespace Scaffold;

use Composer\Script\Event;

class Customizer {

  public static function main(Event $event) {
    $extension_name = $event->getIO()->ask('Name', 'Your Extenstion');
    $extension_machine_name = $event->getIO()->ask('Machine Name', 'Your Extenstion');
    $extension_type = 'module';
    $extension_type = $event->getIO()->ask('Type: module or theme', $extension_type);
    $ci_provider = 'gha';
    $ci_provider = $event->getIO()->ask('CI Provider: GitHub Actions (gha) or CircleCI (circleci)', $ci_provider);
    $command_wrapper = 'ahoy';
    $command_wrapper = $event->getIO()->ask('Command wrapper: Ahoy (ahoy), Makefile (makefile), None (none)', $command_wrapper);

    // Summary

    // Handle replacement
  }
}
