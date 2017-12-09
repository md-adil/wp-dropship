<?php
namespace Composer;
class Management {

    public static function deploy($event) {
        $io = $event->getIO();
        $io->write("Deploying...");

        $composer = $event->getComposer();
        $package = $composer->getPackage();
        $version = $package->getVersion();
        $archiver = $composer->getArchiveManager();
        $io->write("Compressing...");
        $archiver->archive($package, 'zip', dirname(__DIR__));
        $io->write("Compressed");
        $io->write("Deployed");
    }
}
