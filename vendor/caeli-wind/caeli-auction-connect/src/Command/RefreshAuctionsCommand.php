<?php

declare(strict_types=1);

namespace CaeliWind\CaeliAuctionConnect\Command;

use CaeliWind\CaeliAuctionConnect\Service\AuctionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'caeli:auction:refresh',
    description: 'Refresht die Auktionsdaten manuell über die API'
)]
class RefreshAuctionsCommand extends Command
{
    public function __construct(
        private readonly AuctionService $auctionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clear-only', null, InputOption::VALUE_NONE, 'Nur Cache löschen, keine neuen Daten abrufen')
            ->addOption('url-params', null, InputOption::VALUE_OPTIONAL, 'Zusätzliche URL-Parameter für die API')
            ->setHelp('Dieser Command refresht die Auktionsdaten manuell über die API und löscht dabei den bestehenden Cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Caeli Auction Connect - Daten Refresh');

        try {
            // 0. Verzeichnisse prüfen und erstellen
            $io->section('Verzeichnisse werden überprüft...');
            $this->ensureDirectoriesExist($io);
            
            // 1. Cache löschen
            $io->section('Cache wird gelöscht...');
            $clearSuccess = $this->auctionService->clearCache();
            
            if ($clearSuccess) {
                $io->success('Cache erfolgreich gelöscht');
            } else {
                $io->warning('Cache konnte nicht vollständig gelöscht werden');
            }

            // 2. Falls nur Cache löschen gewünscht
            if ($input->getOption('clear-only')) {
                $io->note('Nur Cache gelöscht, keine neuen Daten abgerufen (--clear-only Option)');
                return Command::SUCCESS;
            }

            // 3. Neue Daten abrufen
            $io->section('Neue Auktionsdaten werden abgerufen...');
            $urlParams = $input->getOption('url-params');
            
            $auctions = $this->auctionService->getAuctions(
                filters: [],
                forceRefresh: true,
                urlParams: $urlParams
            );

            $count = count($auctions);
            $io->success("Erfolgreich {$count} Auktionen abgerufen und gecacht");

            // 4. Zusätzliche Informationen
            if ($count > 0) {
                $io->section('Daten-Übersicht:');
                
                // Bundesländer
                $bundeslaender = $this->auctionService->getAllBundeslaender();
                $io->text("Bundesländer: " . implode(', ', $bundeslaender));
                
                // Status-Werte
                $statusValues = $this->auctionService->getUniqueStatusValues();
                $io->text("Status-Werte: " . implode(', ', $statusValues));
                
                // Property-Werte
                $propertyValues = $this->auctionService->getUniquePropertyValues();
                $io->text("Property-Typen: " . implode(', ', $propertyValues));
            }

        } catch (\Exception $e) {
            $io->error('Fehler beim Refresh der Auktionsdaten: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function ensureDirectoriesExist(SymfonyStyle $io): void
    {
        // Korrekte Pfade verwenden
        $projectRoot = getcwd(); // Aktuelles Arbeitsverzeichnis (Projekt-Root)
        
        $directories = [
            $projectRoot . '/var/caeli-auction-data',
            $projectRoot . '/files/auction',
            $projectRoot . '/files/auction/images'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (@mkdir($dir, 0755, true)) {
                    $io->text("✓ Verzeichnis erstellt: {$dir}");
                } else {
                    $io->warning("⚠ Konnte Verzeichnis nicht erstellen: {$dir}");
                }
            } else {
                $io->text("✓ Verzeichnis existiert: {$dir}");
            }

            // Berechtigungen prüfen
            if (!is_writable($dir)) {
                $io->warning("⚠ Verzeichnis nicht beschreibbar: {$dir}");
            }
        }
        
        $io->text("Debug Info:");
        $io->text("Project Root: {$projectRoot}");
    }
} 