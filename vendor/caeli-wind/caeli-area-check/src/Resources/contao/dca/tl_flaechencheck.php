<?php

use Contao\DC_Table;

// tl_flaechencheck dca
$GLOBALS['TL_DCA']['tl_flaechencheck'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['tstamp DESC'],
            'panelLayout' => 'filter;search,limit',
            'flag' => 6, // Nach Datum gruppieren
        ],
        'label' => [
            'fields' => ['tstamp', 'name', 'vorname', 'searched_address'],
            'label_callback' => ['tl_flaechencheck_callbacks', 'formatLabel']
        ],
        'global_operations' => [
            'export' => [
                'label' => &$GLOBALS['TL_LANG']['tl_flaechencheck']['export'],
                'button_callback' => ['tl_flaechencheck_callbacks', 'exportButton'],
                'class' => 'header_theme_import',
                'icon' => 'tablewizard.svg'
            ],
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_flaechencheck']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_flaechencheck']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? 'Wollen Sie diesen Eintrag wirklich löschen?') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_flaechencheck']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ]
    ],
    'palettes' => [
        '__selector__' => [],
        'default' => 'name,vorname,phone,email,searched_address,geometry,park_id,park_rating,status,error_message,uuid'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'label' => ['Zeitstempel', ''],
            'flag' => 6,
            'sorting' => true,
            'eval' => ['rgxp' => 'datim'],
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'name' => [
            'label' => ['Nachname', ''],
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['mandatory'=>true, 'maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'vorname' => [
            'label' => ['Vorname', ''],
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['mandatory'=>true, 'maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'phone' => [
            'label' => ['Telefon', ''],
            'inputType' => 'text',
            'eval' => ['maxlength'=>64],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'email' => [
            'label' => ['E-Mail', ''],
            'inputType' => 'text',
            'eval' => ['rgxp'=>'email', 'maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'searched_address' => [
            'label' => ['Gesuchte Adresse', ''],
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'geometry' => [
            'label' => ['Geometrie', 'GeoJSON oder Koordinaten'],
            'inputType' => 'textarea',
            'eval' => ['maxlength'=>2048, 'allowHtml'=>true, 'decodeEntities'=>true, 'style'=>'height:80px'],
            'sql' => "text NULL"
        ],
        'park_id' => [
            'label' => ['Park-ID', ''],
            'inputType' => 'text',
            'eval' => ['maxlength'=>64],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'park_rating' => [
            'label' => ['Park-Bewertung', ''],
            'inputType' => 'textarea',
            'eval' => ['maxlength'=>2048, 'allowHtml'=>true, 'decodeEntities'=>true, 'style'=>'height:80px'],
            'sql' => "text NULL"
        ],
        'status' => [
            'label' => ['Status', ''],
            'inputType' => 'select',
            'options' => ['success' => 'Erfolgreich', 'failed' => 'Fehlgeschlagen', 'failed_with_rating' => 'Fehlgeschlagen mit Bewertung'],
            'sorting' => true,
            'filter' => true,
            'eval' => ['maxlength'=>32],
            'sql' => "varchar(32) NOT NULL default 'success'"
        ],
        'error_message' => [
            'label' => ['Fehlermeldung', ''],
            'inputType' => 'text',
            'eval' => ['maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'uuid' => [
            'label' => ['Eindeutige ID', 'Zufällige UUID für sichere URL-Parameter'],
            'inputType' => 'text',
            'eval' => ['maxlength'=>36, 'unique'=>true, 'doNotCopy'=>true],
            'sql' => "varchar(36) NOT NULL default ''"
        ]
    ]
];

/**
 * Callback-Klasse für Flächencheck-Listing
 */
class tl_flaechencheck_callbacks
{
    /**
     * Formatiert das Label für die Backend-Liste mit Datum-Gruppierung
     */
    public function formatLabel($row, $label)
    {
        $time = date('H:i', $row['tstamp']);

        // Fallback für alte Einträge ohne status-Spalte
        if (isset($row['status'])) {
            if ($row['status'] === 'success') {
                $status = '✅ Erfolgreich';
                $statusColor = 'green';
            } elseif ($row['status'] === 'failed_with_rating') {
                $status = '🔍 Bewertung verfügbar';
                $statusColor = 'orange';
            } else {
                $status = '❌ Fehlgeschlagen';
                $statusColor = 'red';
            }

            return sprintf(
                '<strong>%s Uhr</strong> - %s %s (%s) <span style="color: %s; font-weight: bold;">[%s]</span>',
                $time,
                $row['vorname'] ?: 'Unbekannt', 
                $row['name'] ?: 'Unbekannt', 
                $row['searched_address'] ?: 'Keine Adresse',
                $statusColor,
                $status
            );
        } else {
            // Alte Format ohne Status (für Rückwärtskompatibilität)
            return sprintf(
                '<strong>%s Uhr</strong> - %s %s (%s)', 
                $time,
                $row['vorname'] ?: 'Unbekannt', 
                $row['name'] ?: 'Unbekannt', 
                $row['searched_address'] ?: 'Keine Adresse'
            );
                }
    }

    /**
     * Exportiert Flächencheck-Daten als CSV
     */
    public function exportData()
    {
        // Prüfe, ob Daten direkt gesendet wurden (über JavaScript)
        $startDate = \Contao\Input::get('start_date') ?: \Contao\Input::post('start_date');
        $endDate = \Contao\Input::get('end_date') ?: \Contao\Input::post('end_date');
        $statusFilter = \Contao\Input::get('status_filter') ?: \Contao\Input::post('status_filter');
        
        // Wenn Datums-Parameter fehlen, Fehler zurückgeben
        if (!$startDate || !$endDate) {
            header('Location: ' . \Contao\Environment::get('base') . 'contao?do=flaechencheck&error=missing_dates');
            exit;
        }

        $container = \Contao\System::getContainer();
        $connection = $container->get('database_connection');

        // Datum-Strings in Timestamps umwandeln
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        if (!$startTimestamp || !$endTimestamp || $startTimestamp > $endTimestamp) {
            \Contao\System::log('Ungültiger Zeitraum beim Export: ' . $startDate . ' bis ' . $endDate, __METHOD__, TL_ERROR);
            header('Location: ' . \Contao\Environment::get('base') . 'contao?do=flaechencheck&error=invalid_date');
            exit;
        }

        // Daten aus der Datenbank abfragen mit optionalem Status-Filter
        if ($statusFilter) {
            $stmt = $connection->prepare("
                SELECT * FROM tl_flaechencheck 
                WHERE tstamp >= ? AND tstamp <= ? AND status = ?
                ORDER BY tstamp DESC
            ");
            $result = $stmt->executeQuery([$startTimestamp, $endTimestamp, $statusFilter]);
        } else {
            $stmt = $connection->prepare("
                SELECT * FROM tl_flaechencheck 
                WHERE tstamp >= ? AND tstamp <= ? 
                ORDER BY tstamp DESC
            ");
            $result = $stmt->executeQuery([$startTimestamp, $endTimestamp]);
        }
        $rows = $result->fetchAllAssociative();

        if (empty($rows)) {
            \Contao\System::log('Keine Daten für Export gefunden: ' . $startDate . ' bis ' . $endDate, __METHOD__, TL_INFO);
            header('Location: ' . \Contao\Environment::get('base') . 'contao?do=flaechencheck&info=no_data');
            exit;
        }

        // CSV-Download
        $filename = sprintf('flaechencheck_export_%s_bis_%s.csv', 
            date('Y-m-d', $startTimestamp), 
            date('Y-m-d', $endTimestamp)
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');

        // UTF-8 BOM für Excel-Kompatibilität
        echo "\xEF\xBB\xBF";

        // CSV schreiben
        $handle = fopen('php://output', 'w');
        
        fputcsv($handle, [
            'Datum/Zeit',
            'Nachname', 
            'Vorname',
            'Telefon',
            'E-Mail',
            'Gesuchte Adresse',
            'Status',
            'Park-ID',
            'Fehlermeldung',
            'UUID'
        ], ';');

        foreach ($rows as $row) {
            $statusText = match($row['status'] ?? '') {
                'success' => 'Erfolgreich',
                'failed_with_rating' => 'Fehlgeschlagen mit Bewertung', 
                'failed' => 'Fehlgeschlagen',
                default => $row['status'] ?: 'Unbekannt'
            };

            fputcsv($handle, [
                date('d.m.Y H:i:s', $row['tstamp']),
                $row['name'],
                $row['vorname'],
                $row['phone'],
                $row['email'],
                $row['searched_address'],
                $statusText,
                $row['park_id'],
                $row['error_message'],
                $row['uuid']
            ], ';');
        }

        fclose($handle);
        exit;
    }

    /**
     * Erstellt Export-Button mit JavaScript-Modal
     */
    public function exportButton($href, $label, $title, $class, $attributes)
    {
        $defaultStart = date('Y-m-d', strtotime('-30 days'));
        $defaultEnd = date('Y-m-d');
        $baseUrl = \Contao\Environment::get('base') . 'contao?do=flaechencheck&key=export_data';

        return sprintf('
            <a href="javascript:void(0)" class="%s" title="%s" onclick="showExportModal()"%s>%s</a>
            <script>
            function showExportModal() {
                // Overlay erstellen
                var overlay = document.createElement("div");
                overlay.id = "export-modal-overlay";
                overlay.style.cssText = "background-color: rgb(0, 0, 0); opacity: 0.7; visibility: visible; position: fixed; top: 0; left: 0; width: 100%%; height: 100%%; z-index: 1000;";
                
                // Modal erstellen
                var modal = document.createElement("div");
                modal.className = "simple-modal";
                modal.id = "export-modal";
                modal.style.cssText = "width: 600px; position: fixed; top: 50px; left: 50%%; transform: translateX(-50%%);";
                
                modal.innerHTML = `
                    <a class="close" href="#" onclick="closeExportModal(); return false;">×</a>
                    <div class="simple-modal-header">
                        <h1>📊 Flächencheck-Daten exportieren</h1>
                    </div>
                    <div class="simple-modal-body">
                        <div style="padding: 20px;">
                            <div style="margin-bottom: 20px;">
                                <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                                    <div style="flex: 1;">
                                        <label for="export_start_date" class="tl_label">Start-Datum:</label>
                                        <input type="date" class="tl_text" id="export_start_date" value="%s" required style="width: 100%%; margin-top: 5px;">
                                    </div>
                                    <div style="flex: 1;">
                                        <label for="export_end_date" class="tl_label">End-Datum:</label>
                                        <input type="date" class="tl_text" id="export_end_date" value="%s" required style="width: 100%%; margin-top: 5px;">
                                    </div>
                                </div>
                                <div>
                                    <label for="export_status_filter" class="tl_label">Status (optional):</label>
                                    <select class="tl_select" id="export_status_filter" style="width: 100%%; margin-top: 5px;">
                                        <option value="">Alle</option>
                                        <option value="success">Erfolgreich</option>
                                        <option value="failed_with_rating">Fehlgeschlagen mit Bewertung</option>
                                        <option value="failed">Fehlgeschlagen</option>
                                    </select>
                                </div>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; border-left: 4px solid #007bff;">
                                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                                    Wählen Sie den gewünschten Zeitraum für den Export der Flächencheck-Daten aus.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="simple-modal-footer">
                        <a href="#" class="btn" onclick="closeExportModal(); return false;">Abbrechen</a>
                        <a href="#" class="btn primary" onclick="startExport(); return false;">📁 CSV exportieren</a>
                    </div>
                `;
                
                document.body.appendChild(overlay);
                document.body.appendChild(modal);
            }
            
            function closeExportModal() {
                var overlay = document.getElementById("export-modal-overlay");
                var modal = document.getElementById("export-modal");
                if (overlay) overlay.remove();
                if (modal) modal.remove();
            }
            
            function startExport() {
                var startDate = document.getElementById("export_start_date").value;
                var endDate = document.getElementById("export_end_date").value;
                var statusFilter = document.getElementById("export_status_filter").value;
                
                if (!startDate || !endDate) {
                    alert("Bitte beide Datumsfelder ausfüllen.");
                    return;
                }
                
                if (new Date(startDate) > new Date(endDate)) {
                    alert("Das Start-Datum darf nicht nach dem End-Datum liegen.");
                    return;
                }
                
                // Download starten
                var url = "%s&start_date=" + startDate + "&end_date=" + endDate;
                if (statusFilter) {
                    url += "&status_filter=" + statusFilter;
                }
                window.location.href = url;
                
                // Modal schließen
                closeExportModal();
            }
            </script>
        ', $class, $title, $attributes, $label, $defaultStart, $defaultEnd, $baseUrl);
    }
}