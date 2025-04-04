<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Google News Fetcher.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-google-news-fetch
 */

namespace CaeliWind\CaeliGoogleNewsFetch\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\Adapter;
use Contao\NewsArchiveModel;
use Contao\DataContainer;
use Contao\Controller;
use Contao\System;
use Contao\Input;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use CaeliWind\CaeliGoogleNewsFetch\Controller\GoogleNewsFetchController;
use Contao\Message;
use CaeliWind\CaeliGoogleNewsFetch\Model\CaeliGooglenewsModel;
use Contao\Environment;
use Contao\AccessDeniedException;
use Contao\StringUtil;
use Contao\NewsModel;
use Contao\Database;

class GoogleNewsFetcher
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly ?GoogleNewsFetchController $controller
    ) {
    }

    /**
     * Gibt alle verfügbaren News-Archive zurück.
     */
    public function getNewsArchives(): array
    {
        $this->framework->initialize();

        $archives = [];

        /** @var Adapter<NewsArchiveModel> $adapter */
        $adapter = $this->framework->getAdapter(NewsArchiveModel::class);
        $objArchives = $adapter->findAll();

        if (null !== $objArchives) {
            while ($objArchives->next()) {
                $archives[$objArchives->id] = $objArchives->title;
            }
        }

        return $archives;
    }

    /**
     * Callback-Funktion für den "Fetch News"-Button
     */
    public function fetchNewsCallback($dc)
    {
        // Diese Methode wird nicht mehr verwendet
        // Wir nutzen jetzt stattdessen onLoadCallback
        return '';
    }

    /**
     * Fügt einen benutzerdefinierten Button zum Formular hinzu
     */
    public function addCustomButton($arrButtons, DataContainer $dc)
    {
        // Nur wenn wir uns im Edit-Modus befinden und eine ID haben
        if (Input::get('act') === 'edit' && $dc->id > 0) {
            // Nur CSS einbinden, kein JavaScript mehr
            $GLOBALS['TL_CSS'][] = 'bundles/caeliwindcaeligooglenewsfetch/css/backend.css';

            // HTML für den Button erstellen - nur noch den "Google News abrufen"-Button, kein "Custom Routine starten" mehr
            $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

            // Direkter Link mit JavaScript-Reload für harten Neulade-Effekt
            $button = '<a href="' . Environment::get('base') . 'contao/caeli_googlenews/fetch/' . $dc->id . '?_token=' . $token . '"
                class="tl_submit"
                onclick="setTimeout(function() { window.location.reload(true); }, 2000);">
                Google News abrufen
            </a>';

            // Container für Statusmeldungen
            $button .= '<div id="caeli_googlenews_status" class="caeli_googlenews_status"></div>';

            $arrButtons['googlenews_fetch'] = $button;
        }

        return $arrButtons;
    }

    /**
     * Generiert die Tabelle für aktuelle News
     */
    private function generateCurrentNewsTable(CaeliGooglenewsModel $model): string
    {
        // Direkt aus der JSON-Datei laden
        $currentNews = $this->loadNewsFromJsonFile($model->id, 'current');

        $html = '<div class="caeli_news_section current_news">';
        $html .= '<h3>Aktuelle Google News</h3>';

        if (empty($currentNews)) {
            $html .= '<div class="tl_info"><p>Keine neuen Beiträge verfügbar.</p></div>';
        } else {
            $html .= $this->generateNewsTable($currentNews, $model->id, false);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generiert die Tabelle für archivierte News
     */
    private function generateArchivedNewsTable(CaeliGooglenewsModel $model): string
    {
        // Direkt aus der JSON-Datei laden
        $archivedNews = $this->loadNewsFromJsonFile($model->id, 'archived');

        $html = '<div class="caeli_news_section archived_news">';
        $html .= '<h3>Archivierte Google News</h3>';

        if (empty($archivedNews)) {
            $html .= '<div class="tl_info"><p>Keine archivierten News vorhanden.</p></div>';
        } else {
            $html .= $this->generateNewsTable($archivedNews, $model->id, true);

            // Nur einen Button zum Zurücksetzen des Archivs mit JavaScript-Reload
            $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
            $html .= '<div class="caeli_archive_actions">';
            $html .= '<a href="' . Environment::get('base') . 'contao/caeli_googlenews/reset/' . $model->id . '?_token=' . $token . '"
                class="tl_submit"
                onclick="if(confirm(\'Möchten Sie wirklich das Archiv zurücksetzen? Diese Aktion kann nicht rückgängig gemacht werden.\')) {
                    setTimeout(function() { window.location.reload(true); }, 2000);
                    return true;
                } else {
                    return false;
                }">
                Archiv zurücksetzen
            </a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Lädt News-Daten aus einer JSON-Datei
     */
    private function loadNewsFromJsonFile(int $configId, string $type): array
    {
        // JSON-Datei-Pfad
        $jsonDir = System::getContainer()->getParameter('kernel.project_dir') . '/var/caeli_googlenews';
        $filePath = $jsonDir . '/news_' . $configId . '_' . $type . '.json';

        if (!file_exists($filePath)) {
            return [];
        }

        $jsonData = file_get_contents($filePath);
        if ($jsonData === false) {
            return [];
        }

        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $data ?: [];
    }

    /**
     * Generiert eine Tabelle mit News-Artikeln
     */
    private function generateNewsTable(array $items, int $configId, bool $isArchive): string
    {
        $html = '<div class="caeli_news_table">';
        $html .= '<table class="tl_listing">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Quelle</th>
                    <th>Titel & Beschreibung</th>
                    <th class="tl_right_nowrap">Aktion</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($items as $index => $item) {
            // Datum formatieren
            $pubDate = '';
            if (!empty($item['pubDate'])) {
                try {
                    $date = new \DateTime($item['pubDate']);
                    $pubDate = $date->format('d.m.Y H:i');
                } catch (\Exception $e) {
                    $pubDate = $item['pubDate'];
                }
            }

            // Quelle ermitteln
            $source = $item['source'] ?? parse_url($item['link'], PHP_URL_HOST);
            $source = preg_replace('/^www\./', '', $source);

            // Beschreibung vorbereiten - nutze die beste verfügbare Beschreibung
            if (!empty($item['description'])) {
                $description = strip_tags($item['description']);
            } else {
                $description = $item['title']; // Fallback auf Titel, wenn keine Beschreibung vorhanden
            }

            // Beschreibung kürzen, falls zu lang
            if (strlen($description) > 300) {
                $description = substr($description, 0, 297) . '...';
            }

            // CSS-Klasse für Zeile
            $rowClass = ($index % 2 === 0) ? 'even' : 'odd';

            // CSRF-Token generieren
            $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

            // Zeile ausgeben mit Formular für die Veröffentlichung statt JavaScript
            $html .= '<tr class="' . $rowClass . '">
                <td class="tl_file_list">' . $pubDate . '</td>
                <td class="tl_file_list">' . $source . '</td>
                <td class="tl_file_list">
                    <div><a href="' . $item['link'] . '" target="_blank" class="news-title">' . $item['title'] . '</a></div>
                    <div class="news-teaser" style="margin-top:5px; padding:3px 5px; color:#333; border-left:3px solid #28a745; background-color:#f9f9f9;">' . $description . '</div>
                </td>
                <td class="tl_file_list tl_right_nowrap">
                    <a href="' . Environment::get('base') . 'contao/caeli_googlenews/publish/' . $configId . '/' . $index . '?_token=' . $token . '" class="tl_submit">Veröffentlichen</a>
                </td>
            </tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Callback für den "Google News abrufen"-Button
     */
    public function generateFetchButtonCallback(DataContainer $dc): string
    {
        $id = $dc->id ?: Input::get('id');
        $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        // HTML für die Benutzeroberfläche
        $html = '<div class="caeli_googlenews_container">';

        // Link-Button mit JavaScript-Reload für harten Neulade-Effekt
        $html .= '<div class="caeli_googlenews_button">';
        $html .= '<a href="' . Environment::get('base') . 'contao/caeli_googlenews/fetch/' . $id . '?_token=' . $token . '"
            class="tl_submit"
            onclick="setTimeout(function() { window.location.reload(true); }, 2000);">
            Google News abrufen
        </a>';
        $html .= '</div>';

        // Status wird über Contao-Message-System ausgegeben
        $html .= '<div id="caeli_googlenews_status" class="caeli_googlenews_status"></div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Zeigt eine Vorschau der abgerufenen News mit Mehrfachauswahl an
     */
    public function previewViewCallback(DataContainer $dc)
    {
        // Lade aktuelle und archivierte News-Artikel
        $rawCurrentNewsItems = $this->loadNewsFromJsonFile((int)$dc->id, 'current');
        $rawArchivedNewsItems = $this->loadNewsFromJsonFile((int)$dc->id, 'archived');

        // Bereite die Daten für die Sortierung vor und behalte den ursprünglichen Index
        $currentNewsItems = [];
        foreach ($rawCurrentNewsItems as $index => $item) {
            $currentNewsItems[] = ['original_index' => $index, 'data' => $item];
        }

        $archivedNewsItems = [];
        foreach ($rawArchivedNewsItems as $index => $item) {
            $archivedNewsItems[] = ['original_index' => $index, 'data' => $item];
        }

        // Sortiere die Artikel nach Datum in absteigender Reihenfolge (neuste zuerst)
        $sortByDateDesc = function ($a, $b) {
            // Greife auf die Daten innerhalb der Struktur zu
            $dateA = $this->getTimestampFromItem($a['data']);
            $dateB = $this->getTimestampFromItem($b['data']);
            return $dateB <=> $dateA; // Sicherer Vergleich für Sortierung
        };

        if (!empty($currentNewsItems)) {
            usort($currentNewsItems, $sortByDateDesc);
        }

        if (!empty($archivedNewsItems)) {
            usort($archivedNewsItems, $sortByDateDesc);
        }

        // Wenn keine News vorhanden sind, Hinweis anzeigen
        if (empty($currentNewsItems) && empty($archivedNewsItems)) {
            return '<div class="tl_info">Keine News-Artikel gefunden. Bitte zuerst den "Google News abrufen"-Button verwenden.</div>';
        }

        // Grundlegendes CSS - neutralere Farben für Dark-Mode-Kompatibilität
        $output = '<style>
        /* Vereinfachtes CSS für Dark-Mode-Kompatibilität */
        .caeli-news-table {
            margin-top: 15px;
            width: 100%;
            border-collapse: collapse;
        }
        .caeli-news-table th,
        .caeli-news-table td {
            padding: 6px 10px;
        }
        .caeli-news-header {
            margin: 24px 0 8px;
            font-weight: 600;
            font-size: 14px;
        }
        .caeli-news-controls {
            margin-bottom: 15px;
            overflow: hidden;
        }
        .caeli-news-count {
            font-size: 0.9em;
            float: left;
            margin-top: 4px;
        }
        .caeli-news-actions {
            float: right;
            margin-bottom: 10px;
        }
        .news-imported {
            opacity: 0.7;
        }
        .news-imported:after {
            content: " ✓";
        }
        .news-keyword {
            display: inline-block;
            padding: 1px 5px;
            font-size: 0.9em;
            border-radius: 3px;
            margin-top: 5px;
        }
        .news-title {
            margin: 0 0 8px 0;
            font-size: 1.1em;
            line-height: 1.4;
            height: auto;
            font-weight: 600;
        }
        .news-desc {
            margin-bottom: 8px;
        }
        .news-meta {
            font-size: 0.9em;
            margin-top: 4px;
        }
        .news-image {
            float: left;
            margin-right: 15px;
            margin-bottom: 5px;
            max-width: 120px;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }
        .news-content-wrapper {
            overflow: hidden;
        }
        /* Import-Overlay */
        .import-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .import-box {
            background: var(--content-bg, #fff);
            border-radius: 8px;
            padding: 20px;
            width: 50%;
            max-width: 600px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }
        .import-progress-container {
            margin: 15px 0;
            border-radius: 4px;
            overflow: hidden;
            height: 24px;
        }
        .import-progress-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        .import-log {
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 0.9em;
        }
        /* Responsive Anpassungen */
        @media (max-width: 1023px) {
            .news-image {
                max-width: 100px;
            }
        }
        @media (max-width: 767px) {
            .news-image {
                float: none;
                margin-right: 0;
                margin-bottom: 10px;
                max-width: 100%;
                width: 100%;
                height: auto;
            }
        }
        </style>';

        // CSRF-Token für Aktionen
        $token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        // Lade das Konfigurationsmodell für die Archiv-ID
        $configModel = CaeliGooglenewsModel::findById($dc->id);

        // BEGINN: AKTUELLE NEWS AUSGABE
        if (!empty($currentNewsItems)) {
            $output .= '<h3 class="caeli-news-header">Aktuelle News-Artikel (' . count($currentNewsItems) . ')</h3>';

            // Steuerelemente
            $output .= '<div class="caeli-news-controls tl_listing_container">';

            // Anzahl anzeigen
            $output .= '<div class="caeli-news-count">' . count($currentNewsItems) . ' neue Artikel gefunden</div>';

            // Aktionen (rechts)
            $output .= '<div class="caeli-news-actions">';
            $output .= '<button type="button" id="import-selected-current" class="tl_submit">Ausgewählte importieren</button> ';
            $output .= '<a href="' . Environment::get('base') . 'contao/caeli_googlenews/reset/' . $dc->id . '?_token=' . $token . '" class="tl_submit" onclick="return confirm(\'Möchten Sie wirklich alle News zurücksetzen?\');">News zurücksetzen</a>';
            $output .= '</div>';

            $output .= '</div>'; // Ende controls

            // Tabelle im Contao-Stil
            $output .= '<div class="tl_listing_container list_view">';
            $output .= '<table class="tl_listing caeli-news-table">
                <thead>
                    <tr>
                        <th class="tl_folder_tlist" style="width:30px;"><input type="checkbox" id="check-all-current" onclick="Backend.toggleCheckboxGroup(this, \'currentItems\')"></th>
                        <th class="tl_folder_tlist" style="width:100px;">Datum</th>
                        <th class="tl_folder_tlist" style="width:100px;">Quelle</th>
                        <th class="tl_folder_tlist">Artikel</th>
                        <th class="tl_folder_tlist tl_right_nowrap" style="width:120px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($currentNewsItems as $entry) { // Iteriere über die sortierte Struktur
                $originalIndex = $entry['original_index'];
                $item = $entry['data'];

                // Daten aufbereiten
                $hasImage = !empty($item['imageUrl']);
                $source = $item['source'] ?? 'Unbekannte Quelle';

                // Datum formatieren
                if (empty($item['pubDate'])) {
                    $pubDate = date('d.m.Y H:i', time());
                } else {
                    if (is_numeric($item['pubDate'])) {
                        $pubDate = date('d.m.Y H:i', (int)$item['pubDate']);
                    } else {
                        try {
                            $dateObj = new \DateTime($item['pubDate']);
                            $pubDate = $dateObj->format('d.m.Y H:i');
                        } catch (\Exception $e) {
                            $pubDate = date('d.m.Y H:i', time());
                        }
                    }
                }

                $description = $item['description'] ?? '';
                $link = $item['link'] ?? '#';
                $title = $item['title'] ?? 'Ohne Titel';
                $keyword = $item['keyword'] ?? '';
                $imageUrl = $item['imageUrl'] ?? '';

                // Prüfen, ob bereits importiert (verwende $item)
                $isImported = $this->isNewsImported($item, (int)$configModel->newsArchive);

                // Publish-Link mit CSRF-Token, verwende $originalIndex!
                $publishUrl = Environment::get('base') . 'contao/caeli_googlenews/publish/' . $dc->id . '/' . $originalIndex . '?_token=' . $token;

                // CSS-Klassen festlegen (Index für even/odd egal, Hauptsache $isImported stimmt)
                // Wir verwenden hier den originalIndex für Konsistenz, obwohl auch ein Laufindex ginge.
                $rowClass = ($originalIndex % 2 === 0 ? 'even' : 'odd') . ($isImported ? ' news-imported' : '');

                // Zeile ausgeben
                $output .= '<tr class="' . $rowClass . '">';

                // Checkbox-Spalte (verwende $originalIndex!)
                $output .= '<td class="tl_file_list">';
                if (!$isImported) {
                    $output .= '<input type="checkbox" name="currentItems[]" id="current_' . $originalIndex . '" class="tl_checkbox" value="' . $originalIndex . '">';
                }
                $output .= '</td>';

                // Datum-Spalte
                $output .= '<td class="tl_file_list">' . $pubDate . '</td>';

                // Quellen-Spalte
                $output .= '<td class="tl_file_list">' . htmlspecialchars($source) . '</td>';

                // Content-Spalte mit Bild
                $output .= '<td class="tl_file_list">';
                $output .= '<div class="news-content-wrapper">';

                // Bild anzeigen, wenn vorhanden
                if ($hasImage) {
                    // Prüfen, ob das Bild eine problematische Handelsblatt-URL ist
                    $handelsblattDetected = false;
                    if (
                        stripos($imageUrl, 'handelsblatt.com') !== false ||
                        stripos($imageUrl, 'channelizer.handelsblatt') !== false ||
                        stripos($imageUrl, 'opengraph_default_logo') !== false ||
                        stripos($imageUrl, 'formatOriginal.png') !== false
                    ) {
                        $handelsblattDetected = true;
                        // Wenn Thumbnail verfügbar ist, dieses nutzen
                        if (!empty($item['thumbnail'])) {
                            $imageUrl = $item['thumbnail'];
                        }
                    }

                    $output .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($title) . '" class="news-image' . ($handelsblattDetected ? ' handelsblatt-replaced' : '') . '">';
                }

                // Titel und Beschreibung
                $output .= '<div class="news-title"><a href="' . $link . '" target="_blank">' . htmlspecialchars($title) . '</a></div>';

                // Kurzbeschreibung
                $shortDesc = mb_strlen($description) > 200 ? mb_substr($description, 0, 200) . '...' : $description;
                $output .= '<div class="news-desc">' . htmlspecialchars($shortDesc) . '</div>';

                // Meta-Informationen
                $output .= '<div class="news-meta">';
                if (!empty($keyword)) {
                    $output .= '<span class="news-keyword">' . htmlspecialchars($keyword) . '</span> ';
                }
                $output .= '</div>';

                $output .= '</div>'; // Ende news-content-wrapper
                $output .= '</td>';

                // Aktionen-Spalte - jetzt mit AJAX-Veröffentlichung
                $output .= '<td class="tl_file_list tl_right_nowrap">';
                if (!$isImported) {
                    $output .= '<button type="button" class="tl_submit publish-single" data-url="' . $publishUrl . '">Veröffentlichen</button>';
                } else {
                    $output .= '<span class="tl_gray">Bereits importiert</span>';
                }
                $output .= '</td>';

                $output .= '</tr>';
            }

            $output .= '</tbody></table>';
            $output .= '</div>'; // Ende tl_listing_container
        } else {
            $output .= '<h3 class="caeli-news-header">Aktuelle News-Artikel</h3>';
            $output .= '<div class="tl_info"><p>Keine neuen Beiträge verfügbar.</p></div>';
        }

        // BEGINN: ARCHIVIERTE NEWS AUSGABE
        if (!empty($archivedNewsItems)) {
            $output .= '<h3 class="caeli-news-header">Archivierte News-Artikel (' . count($archivedNewsItems) . ')</h3>';

            // Steuerelemente
            $output .= '<div class="caeli-news-controls tl_listing_container">';

            // Anzahl anzeigen
            $output .= '<div class="caeli-news-count">' . count($archivedNewsItems) . ' Artikel im Archiv</div>';

            // Aktionen (rechts)
            $output .= '<div class="caeli-news-actions">';
            $output .= '<button type="button" id="import-selected-archive" class="tl_submit">Ausgewählte importieren</button> ';
            $output .= '<a href="' . Environment::get('base') . 'contao/caeli_googlenews/reset/' . $dc->id . '?_token=' . $token . '" class="tl_submit" onclick="return confirm(\'Möchten Sie wirklich alle News zurücksetzen?\');">News zurücksetzen</a>';
            $output .= '</div>';

            $output .= '</div>'; // Ende controls

            // Tabelle im Contao-Stil
            $output .= '<div class="tl_listing_container list_view">';
            $output .= '<table class="tl_listing caeli-news-table">
                <thead>
                    <tr>
                        <th class="tl_folder_tlist" style="width:30px;"><input type="checkbox" id="check-all-archive" onclick="Backend.toggleCheckboxGroup(this, \'archiveItems\')"></th>
                        <th class="tl_folder_tlist" style="width:100px;">Datum</th>
                        <th class="tl_folder_tlist" style="width:100px;">Quelle</th>
                        <th class="tl_folder_tlist">Artikel</th>
                        <th class="tl_folder_tlist tl_right_nowrap" style="width:120px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($archivedNewsItems as $entry) { // Iteriere über die sortierte Struktur
                $originalIndex = $entry['original_index'];
                $item = $entry['data'];

                // Daten aufbereiten
                $hasImage = !empty($item['imageUrl']);
                $source = $item['source'] ?? 'Unbekannte Quelle';

                // Datum formatieren
                if (empty($item['pubDate'])) {
                    $pubDate = date('d.m.Y H:i', time());
                } else {
                    if (is_numeric($item['pubDate'])) {
                        $pubDate = date('d.m.Y H:i', (int)$item['pubDate']);
                    } else {
                        try {
                            $dateObj = new \DateTime($item['pubDate']);
                            $pubDate = $dateObj->format('d.m.Y H:i');
                        } catch (\Exception $e) {
                            $pubDate = date('d.m.Y H:i', time());
                        }
                    }
                }

                $description = $item['description'] ?? '';
                $link = $item['link'] ?? '#';
                $title = $item['title'] ?? 'Ohne Titel';
                $keyword = $item['keyword'] ?? '';
                $imageUrl = $item['imageUrl'] ?? '';

                // Prüfen, ob bereits importiert (verwende $item)
                $isImported = $this->isNewsImported($item, (int)$configModel->newsArchive);

                // Publish-Link mit CSRF-Token (verwende $originalIndex!)
                $publishUrl = Environment::get('base') . 'contao/caeli_googlenews/publish/' . $dc->id . '/' . $originalIndex . '?_token=' . $token . '&source=archive';

                // CSS-Klassen festlegen (verwende $originalIndex für even/odd)
                $rowClass = ($originalIndex % 2 === 0 ? 'even' : 'odd') . ($isImported ? ' news-imported' : '');

                // Zeile ausgeben
                $output .= '<tr class="' . $rowClass . '">';

                // Checkbox-Spalte (verwende $originalIndex!)
                $output .= '<td class="tl_file_list">';
                if (!$isImported) {
                    $output .= '<input type="checkbox" name="archiveItems[]" id="archive_' . $originalIndex . '" class="tl_checkbox" value="' . $originalIndex . '">';
                }
                $output .= '</td>';

                // Datum-Spalte
                $output .= '<td class="tl_file_list">' . $pubDate . '</td>';

                // Quellen-Spalte
                $output .= '<td class="tl_file_list">' . htmlspecialchars($source) . '</td>';

                // Content-Spalte mit Bild
                $output .= '<td class="tl_file_list">';
                $output .= '<div class="news-content-wrapper">';

                // Bild anzeigen, wenn vorhanden
                if ($hasImage) {
                    // Prüfen, ob das Bild eine problematische Handelsblatt-URL ist
                    $handelsblattDetected = false;
                    if (
                        stripos($imageUrl, 'handelsblatt.com') !== false ||
                        stripos($imageUrl, 'channelizer.handelsblatt') !== false ||
                        stripos($imageUrl, 'opengraph_default_logo') !== false ||
                        stripos($imageUrl, 'formatOriginal.png') !== false
                    ) {
                        $handelsblattDetected = true;
                        // Wenn Thumbnail verfügbar ist, dieses nutzen
                        if (!empty($item['thumbnail'])) {
                            $imageUrl = $item['thumbnail'];
                        }
                    }

                    $output .= '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($title) . '" class="news-image' . ($handelsblattDetected ? ' handelsblatt-replaced' : '') . '">';
                }

                // Titel und Beschreibung
                $output .= '<div class="news-title"><a href="' . $link . '" target="_blank">' . htmlspecialchars($title) . '</a></div>';

                // Kurzbeschreibung
                $shortDesc = mb_strlen($description) > 200 ? mb_substr($description, 0, 200) . '...' : $description;
                $output .= '<div class="news-desc">' . htmlspecialchars($shortDesc) . '</div>';

                // Meta-Informationen
                $output .= '<div class="news-meta">';
                if (!empty($keyword)) {
                    $output .= '<span class="news-keyword">' . htmlspecialchars($keyword) . '</span> ';
                }
                $output .= '</div>';

                $output .= '</div>'; // Ende news-content-wrapper
                $output .= '</td>';

                // Aktionen-Spalte - jetzt mit AJAX-Veröffentlichung
                $output .= '<td class="tl_file_list tl_right_nowrap">';
                if (!$isImported) {
                    $output .= '<button type="button" class="tl_submit publish-single" data-url="' . $publishUrl . '">Veröffentlichen</button>';
                } else {
                    $output .= '<span class="tl_gray">Bereits importiert</span>';
                }
                $output .= '</td>';

                $output .= '</tr>';
            }

            $output .= '</tbody></table>';
            $output .= '</div>'; // Ende tl_listing_container
        }

        // JavaScript für AJAX Import und Scroll-Position
        $output .= '<script>
        // DOCUMENT READY
        document.addEventListener("DOMContentLoaded", function() {
            console.log("DOM geladen, initialisiere Google News Fetcher...");

            // Einzelne Artikel veröffentlichen via AJAX
            document.querySelectorAll(".publish-single").forEach(function(button) {
                if (button) {
                    button.addEventListener("click", function() {
                        var url = this.getAttribute("data-url");
                        console.log("Veröffentliche Artikel via AJAX:", url);
                        publishSingleArticle(url);
                    });
                }
            });

            // AJAX-Import für einen einzelnen Artikel
            function publishSingleArticle(url) {
                try {
                    // Erstelle Overlay mit Fortschrittsanzeige
                    var overlay = document.createElement("div");
                    overlay.className = "import-overlay";

                    var importBox = document.createElement("div");
                    importBox.className = "import-box";

                    var title = document.createElement("h3");
                    title.textContent = "Artikel wird importiert...";

                    var progressContainer = document.createElement("div");
                    progressContainer.className = "import-progress-container";

                    var progressBar = document.createElement("div");
                    progressBar.className = "import-progress-bar";
                    progressBar.style.width = "0%";

                    var statusText = document.createElement("div");
                    statusText.id = "ajax-progress-text";
                    statusText.textContent = "Import gestartet...";

                    var logContainer = document.createElement("div");
                    logContainer.className = "import-log";

                    progressContainer.appendChild(progressBar);
                    importBox.appendChild(title);
                    importBox.appendChild(progressContainer);
                    importBox.appendChild(statusText);
                    importBox.appendChild(logContainer);
                    overlay.appendChild(importBox);
                    document.body.appendChild(overlay);

                    // Fortschritt simulieren
                    setTimeout(function() {
                        progressBar.style.width = "30%";
                        statusText.textContent = "Artikel-Daten werden verarbeitet...";
                        logContainer.innerHTML += "<div>Import gestartet...</div>";
                    }, 200);

                    // Artikel via Fetch API importieren
                    fetch(url)
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error("Netzwerkfehler oder Serverfehler: " + response.status);
                            }

                            progressBar.style.width = "60%";
                            statusText.textContent = "Daten werden importiert...";
                            logContainer.innerHTML += "<div>Artikel-Daten erfolgreich abgerufen.</div>";

                            return response.text();
                        })
                        .then(function(data) {
                            progressBar.style.width = "90%";
                            statusText.textContent = "Import abgeschlossen!";
                            logContainer.innerHTML += "<div>✓ Import erfolgreich abgeschlossen.</div>";

                            // Verzögerung vor Abschluss
                            setTimeout(function() {
                                progressBar.style.width = "100%";

                                // OK-Button hinzufügen
                                var okButton = document.createElement("button");
                                okButton.textContent = "OK";
                                okButton.className = "tl_submit";
                                okButton.style.marginTop = "15px";

                                okButton.addEventListener("click", function() {
                                    // Seite neu laden mit Timestamp für Cache-Busting
                                    location.reload();
                                });

                                importBox.appendChild(okButton);
                            }, 500);
                        })
                        .catch(function(error) {
                            console.error("Fehler beim Importieren:", error);
                            progressBar.style.width = "100%";
                            progressBar.style.background = "#d9534f";
                            statusText.textContent = "Fehler beim Import!";
                            logContainer.innerHTML += "<div style=\'color:#d9534f\'>✗ Fehler: " + error.message + "</div>";

                            // OK-Button zum Schließen hinzufügen
                            var okButton = document.createElement("button");
                            okButton.textContent = "OK";
                            okButton.className = "tl_submit";
                            okButton.style.marginTop = "15px";

                            okButton.addEventListener("click", function() {
                                // Overlay entfernen
                                document.body.removeChild(overlay);
                            });

                            importBox.appendChild(okButton);
                        });
                } catch (error) {
                    console.error("Allgemeiner Fehler:", error);
                    alert("Fehler beim Starten des Imports: " + error.message);
                }
            }

            // AJAX-Import für ausgewählte Artikel (aktuelle News)
            var currentButton = document.getElementById("import-selected-current");
            if (currentButton) {
                console.log("Button für aktuelle Artikel gefunden");
                currentButton.addEventListener("click", function() {
                    importSelectedArticles(false);
                });
            } else {
                console.log("Button für aktuelle Artikel nicht gefunden");
            }

            // AJAX-Import für ausgewählte Artikel (archivierte News)
            var archiveButton = document.getElementById("import-selected-archive");
            if (archiveButton) {
                console.log("Button für archivierte Artikel gefunden");
                archiveButton.addEventListener("click", function() {
                    importSelectedArticles(true);
                });
            } else {
                console.log("Button für archivierte Artikel nicht gefunden");
            }

            // Funktion zum Importieren ausgewählter Artikel via AJAX
            function importSelectedArticles(isArchive) {
                try {
                    console.log("Import gestartet für " + (isArchive ? "archivierte" : "aktuelle") + " Artikel");

                    // Sammle ausgewählte Checkboxen
                    var checkboxName = isArchive ? "archiveItems[]" : "currentItems[]";
                    console.log("Suche Checkboxen mit Namen: " + checkboxName);
                    var checkboxes = document.querySelectorAll("input[name=\'" + checkboxName + "\']:checked");
                    console.log("Gefundene ausgewählte Checkboxen: " + checkboxes.length);

                    if (checkboxes.length === 0) {
                        alert("Bitte wählen Sie mindestens einen Artikel aus.");
                        return;
                    }

                    // Erstelle Overlay mit Fortschrittsanzeige
                    var overlay = document.createElement("div");
                    overlay.className = "import-overlay";

                    var importBox = document.createElement("div");
                    importBox.className = "import-box";

                    var title = document.createElement("h3");
                    title.textContent = "Artikel werden importiert...";

                    var progressContainer = document.createElement("div");
                    progressContainer.className = "import-progress-container";

                    var progressBar = document.createElement("div");
                    progressBar.className = "import-progress-bar";
                    progressBar.style.width = "0%";

                    var statusText = document.createElement("div");
                    statusText.id = "ajax-progress-text";
                    statusText.textContent = "0/" + checkboxes.length + " Artikel importiert";

                    var logContainer = document.createElement("div");
                    logContainer.className = "import-log";

                    progressContainer.appendChild(progressBar);
                    importBox.appendChild(title);
                    importBox.appendChild(progressContainer);
                    importBox.appendChild(statusText);
                    importBox.appendChild(logContainer);
                    overlay.appendChild(importBox);
                    document.body.appendChild(overlay);

                    // Artikel-Indizes sammeln
                    var articleIndices = [];
                    checkboxes.forEach(function(checkbox) {
                        articleIndices.push(checkbox.value);
                    });

                    // Variablen für den Import
                    var processed = 0;
                    var successful = 0;
                    var failed = 0;

                    // Funktion zum Loggen
                    function log(message, isError) {
                        var logEntry = document.createElement("div");
                        logEntry.textContent = message;
                        if (isError) {
                            logEntry.style.color = "#d9534f";
                        } else {
                            logEntry.style.color = "#5cb85c";
                        }
                        logContainer.appendChild(logEntry);
                        logContainer.scrollTop = logContainer.scrollHeight;
                    }

                    // Funktion zum Aktualisieren des Fortschritts
                    function updateProgress() {
                        var percent = (processed / articleIndices.length) * 100;
                        progressBar.style.width = percent + "%";
                        statusText.textContent =
                            processed + "/" + articleIndices.length + " Artikel importiert, " +
                            successful + " erfolgreich, " + failed + " fehlgeschlagen";
                    }

                    // Funktion zum Abschließen des Imports
                    function finishImport() {
                        log("Import abgeschlossen!", false);

                        // Füge OK-Button hinzu, um Overlay zu schließen und Seite neu zu laden
                        var okButton = document.createElement("button");
                        okButton.textContent = "OK";
                        okButton.className = "tl_submit";
                        okButton.style.marginTop = "15px";

                        okButton.addEventListener("click", function() {
                            // Seite neu laden mit Timestamp für Cache-Busting
                            location.reload();
                        });

                        importBox.appendChild(okButton);
                    } 

                    // Importiere sequentiell
                    function importNextArticle() {
                        try {
                            if (processed >= articleIndices.length) {
                                finishImport();
                                return;
                            }

                            var index = articleIndices[processed];
                            console.log("Importiere Artikel mit Index: " + index);
                            var url = "' . Environment::get('base') . 'contao/caeli_googlenews/publish/' . $dc->id . '/" + index + "?_token=' . $token . '";

                            // Füge Archivparameter hinzu wenn nötig
                            if (isArchive) {
                                url += "&source=archive";
                            }

                            console.log("Import-URL: " + url);
                            log("Importiere Artikel #" + (processed + 1) + "...", false);

                            fetch(url)
                                .then(function(response) {
                                    processed++;

                                    if (response.ok) {
                                        successful++;
                                        log("✓ Artikel #" + processed + " erfolgreich importiert", false);
                                    } else {
                                        failed++;
                                        log("✗ Fehler beim Importieren von Artikel #" + processed, true);
                                    }

                                    updateProgress();
                                    // Importiere den nächsten Artikel mit einer kurzen Verzögerung
                                    setTimeout(importNextArticle, 500);
                                })
                                .catch(function(error) {
                                    processed++;
                                    failed++;
                                    log("✗ Fehler beim Importieren von Artikel #" + processed + ": " + error.message, true);
                                    updateProgress();
                                    setTimeout(importNextArticle, 500);
                                });
                        } catch (error) {
                            console.error("Fehler beim Importieren des nächsten Artikels:", error);
                            log("✗ Interner Fehler: " + error.message, true);
                            processed++;
                            failed++;
                            updateProgress();
                            setTimeout(importNextArticle, 500);
                        }
                    }

                    // Starte den Import-Prozess
                    importNextArticle();
                } catch (error) {
                    console.error("Allgemeiner Fehler beim Import-Prozess:", error);
                    alert("Fehler beim Starten des Imports: " + error.message);
                }
            }
        });
        </script>';

        return $output;
    }

    /**
     * Callback-Methode, die aufgerufen wird, wenn das DCA geladen wird
     */
    public function onLoadCallback(mixed $dc = null): void
    {
        // Prüfen, ob Keyword-Änderungen vorliegen und ob es sich nicht um eine Mehrfachveröffentlichung handelt
        if (is_object($dc) && $dc->id > 0) {
            $model = CaeliGooglenewsModel::findById($dc->id);
            $request = System::getContainer()->get('request_stack')->getCurrentRequest();

            // Nur ausführen, wenn es sich wirklich um ein Edit des Hauptformulars handelt
            // und nicht um eine Aktion wie publish oder publish-multiple
            if ($model
                && Input::post('FORM_SUBMIT') === 'tl_caeli_googlenews'
                && !$request->query->has('_token') // Keine Token-basierte Aktion
                && !Input::get('act') === 'publishMultiple' // Keine Mehrfachveröffentlichung
            ) {
                // Wenn sich die Keywords geändert haben, Archiv zurücksetzen
                $blacklistKeywords = Input::post('blacklistKeywords');

                if ($blacklistKeywords !== $model->blacklistKeywords) {
                    // Beim Ändern der Keywords das Archiv in der JSON-Datei zurücksetzen
                    $jsonDir = System::getContainer()->getParameter('kernel.project_dir') . '/var/caeli_googlenews';
                    $filePath = $jsonDir . '/news_' . $dc->id . '_archived.json';

                    // Verzeichnis erstellen falls es nicht existiert
                    if (!is_dir($jsonDir)) {
                        mkdir($jsonDir, 0755, true);
                    }

                    // Leere Archived-JSON-Datei schreiben
                    file_put_contents($filePath, json_encode([]));

                    Message::addInfo('Die Keywords wurden geändert. Das Archiv wurde zurückgesetzt.');

                    // Aktualisiere auch die aktuelle Vorschau basierend auf den neuen Keywords
                    $this->refreshFilteredPreview($dc->id);
                    Message::addInfo('Die aktuelle Vorschau wurde basierend auf den neuen Keywords aktualisiert.');
                }
            }
        }
    }

    /**
     * Diese Methode wird aufgerufen, wenn die Keyword-Einstellungen geändert werden.
     * Aktualisiert die gefilterte Vorschau in der JSON-Datei.
     */
    public function updatePreviewWithFilteredKeywords($dc): void
    {
        if (!($dc instanceof DataContainer)) {
            return;
        }

        $id = $dc->id;
        $model = CaeliGooglenewsModel::findByPk($id);

        if (!$model) {
            return;
        }

        // Die aktuellen Keyword-Werte aus dem POST-Request holen
        $blacklistKeywords = Input::post('blacklistKeywords');

        // Prüfen, ob sich die Keywords geändert haben
        if ($blacklistKeywords !== $model->blacklistKeywords) {
            // Speichert die Änderung im Modell
            $model->blacklistKeywords = $blacklistKeywords;
            $model->save();

            // Aktualisiert die gefilterte Vorschau
            $this->refreshFilteredPreview($id);
        }
    }

    /**
     * Aktualisiert die gefilterte Vorschau in der JSON-Datei basierend auf den Keywords
     */
    protected function refreshFilteredPreview(int $id): void
    {
        $model = CaeliGooglenewsModel::findById($id);
        if (!$model) {
            return;
        }

        // Lädt die aktuellen News-Items
        $newsItems = $this->loadNewsFromJsonFile($id, 'current');
        if (empty($newsItems)) {
            return;
        }

        // Extrahiert die Blacklist-Keywords
        $blacklistKeywords = [];
        if (!empty($model->blacklistKeywords)) {
            $blacklistKeywords = array_filter(array_map('trim', explode("\n", $model->blacklistKeywords)));
        }

        // Filtert die News-Items basierend auf den Keywords
        $filteredItems = [];
        foreach ($newsItems as $item) {
            $title = $item['title'] ?? '';
            $description = $item['description'] ?? '';
            $content = strtolower($title . ' ' . $description);

            // Prüft, ob Blacklist-Keywords enthalten sind
            $containsBlacklisted = false;
            foreach ($blacklistKeywords as $keyword) {
                if (!empty($keyword) && stripos($content, $keyword) !== false) {
                    $containsBlacklisted = true;
                    break;
                }
            }

            // Fügt das Item zur gefilterten Liste hinzu, wenn es kein Blacklist-Keyword enthält
            if (!$containsBlacklisted) {
                $filteredItems[] = $item;
            }
        }

        // Speichert die gefilterten Items direkt in die JSON-Datei
        $jsonDir = System::getContainer()->getParameter('kernel.project_dir') . '/var/caeli_googlenews';
        if (!is_dir($jsonDir)) {
            mkdir($jsonDir, 0755, true);
        }
        $filePath = $jsonDir . '/news_' . $id . '_current.json';
        file_put_contents($filePath, json_encode($filteredItems, JSON_PRETTY_PRINT));
    }

    /**
     * Prüft, ob ein News-Artikel bereits importiert wurde (vereinfachte Version)
     */
    public function isNewsImported(array $item, int $archiveId): bool
    {
        // Prüfen, ob eine URL zum Vergleichen vorhanden ist
        if (empty($item['link'])) {
            error_log('CaeliGoogleNewsFetch [WARNING][isNewsImported]: Artikel hat keinen Link zur Überprüfung.');
            return false;
        }

        // Direkte Datenbankabfrage für bestehende Einträge
        try {
            $this->framework->initialize();
            $db = \Contao\Database::getInstance();

            $conditions = [];
            $params = [];

            // Immer nach der korrekten Archiv-ID filtern
            $params[] = $archiveId;

            // URL-Bedingung hinzufügen
            $conditions[] = "url = ?";
            $params[] = $item['link'];

            // WHERE-Klausel explizit nur mit URL zusammenbauen
            $whereClause = "pid = ? AND " . $conditions[0];

            // Ausführen der Abfrage
            $result = $db->prepare("SELECT id FROM tl_news WHERE " . $whereClause)
                         ->execute(...$params);

            $count = $result->numRows;

            // Wenn mindestens ein Artikel gefunden wurde, ist der Artikel bereits importiert
            if ($count > 0) {
                return true;
            }

            // Kein Match gefunden
            return false;

        } catch (\Exception $e) {
            // Bei Fehlern sicheres Verhalten: nicht als importiert markieren
            error_log("CaeliGoogleNewsFetch [ERROR]: Fehler beim Prüfen auf Import-Status via DB: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hilfsfunktion zum Extrahieren des Zeitstempels aus einem News-Item
     */
    private function getTimestampFromItem(array $item): int
    {
        if (empty($item['pubDate'])) {
            return time();
        }

        if (is_numeric($item['pubDate'])) {
            return (int)$item['pubDate'];
        }

        try {
            $dateObj = new \DateTime($item['pubDate']);
            return $dateObj->getTimestamp();
        } catch (\Exception $e) {
            return time();
        }
    }
}
