<?php

declare(strict_types=1);

namespace App\Controller;

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Contao\MemberModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Contao\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\PaymentIntent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Event;
use App\Service\PlunkService;

#[Route('/stripe')]
class StripeWebhookController extends AbstractController implements ServiceSubscriberInterface
{
    private ContaoFramework $framework;
    private PlunkService $plunkService;
    private LoggerInterface $logger;
    private string $webhookSecret;
    private string $stripeSecretKey;

    public function __construct(
        ContainerInterface $container,
        string             $webhookSecret,
        string             $stripeSecretKey,
        ContaoFramework    $framework,
        PlunkService       $plunkService,
        LoggerInterface    $logger
    )
    {
        $this->container = $container;
        $this->webhookSecret = $webhookSecret;
        $this->stripeSecretKey = $stripeSecretKey;
        $this->framework = $framework;
        $this->plunkService = $plunkService;
        $this->logger = $logger;
    }


    #[Route('/check-user', name: 'stripe_check_user', methods: ['POST'])]
    public function checkUser(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? '';
            $username = $data['username'] ?? '';

            $this->framework->initialize();

            $existingEmail = MemberModel::findByEmail($email);
            $existingUsername = MemberModel::findByUsername($username);

            // Wenn beide nicht existieren, ist alles OK
            if (!$existingEmail && !$existingUsername) {
                return new JsonResponse([
                    'valid' => true
                ]);
            }

            // Wenn beide existieren, müssen sie zum gleichen Benutzer gehören
            if ($existingEmail && $existingUsername) {
                $valid = ($existingEmail->id === $existingUsername->id);
                return new JsonResponse([
                    'valid' => $valid,
                    'message' => $valid ? 'ok' : 'Die E-Mail-Adresse und der Benutzername gehören zu verschiedenen Konten. Bitte verwenden Sie entweder beide Daten eines bestehenden Kontos oder geben Sie komplett neue Daten ein.'
                ]);
            }

            // Wenn nur einer existiert, ist es nicht OK
            return new JsonResponse([
                'valid' => false,
                'message' => 'Bitte verwenden Sie entweder beide Daten eines bestehenden Kontos oder geben Sie komplett neue Daten ein.'
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecretKey);

            $payload = $request->getContent();
            $payloadData = json_decode($payload, true);
            $sigHeader = $request->headers->get('Stripe-Signature');

            $this->logger->debug('Webhook received', [
                'event_type' => $payloadData['type'] ?? 'unknown',
                'event_id' => $payloadData['id'] ?? 'unknown',
                'payload' => $payloadData // Logging des kompletten Payloads für Debugging
            ]);

            if ($sigHeader) {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $this->webhookSecret
                );
            } else {
                return new Response('Test successful', Response::HTTP_OK);
            }

            switch ($event->type ?? null) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $metadata = $paymentIntent->metadata->toArray();

                    try {
                        $personalData = !empty($metadata['personalData']) ?
                            json_decode($metadata['personalData'], true) : null;

                        // Rechnung direkt erstellen ohne vorherige Prüfung
                        $invoice = \Stripe\Invoice::create([
                            'customer' => $paymentIntent->customer,
                            'collection_method' => 'send_invoice',
                            'days_until_due' => 0,
                            'auto_advance' => true,
                            'metadata' => [
                                'send_email' => true,
                                'payment_intent_id' => $paymentIntent->id
                            ]
                        ]);

                        // Rechnungsposition hinzufügen
                        \Stripe\InvoiceItem::create([
                            'customer' => $paymentIntent->customer,
                            'amount' => $paymentIntent->amount,
                            'currency' => $paymentIntent->currency,
                            'invoice' => $invoice->id,
                            'description' => $metadata['productTitle'] ?? 'Produkt'
                        ]);

                        // Finalisieren und als bezahlt markieren
                        $invoice->finalizeInvoice();
                        $invoice->pay(['paid_out_of_band' => true]);

                        // Rechnung senden
                        $invoice = \Stripe\Invoice::retrieve($invoice->id);
                        $invoice->sendInvoice();

                        $this->logger->info('Invoice created and sent', [
                            'invoice_id' => $invoice->id,
                            'payment_intent_id' => $paymentIntent->id,
                            'customer_email' => $personalData['email'] ?? 'unknown',
                            'amount' => $paymentIntent->amount / 100 . ' ' . strtoupper($paymentIntent->currency)
                        ]);

                        // Rest der Logik für Benutzer und E-Mails bleibt unverändert
                        if ($personalData && !empty($personalData['email'])) {
                            $existingMember = MemberModel::findByEmail($personalData['email']);

                            if ($existingMember) {
                                $this->handleSubscriptionPayment($paymentIntent, $personalData['email']);
                            } else {
                                if (!empty($metadata['createUser']) && $metadata['createUser'] === 'true') {
                                    $this->createContaoUser($metadata);
                                }
                            }

                            if (!empty($metadata['productData'])) {
                                $productData = json_decode($metadata['productData'], true);
                                if (!empty($productData['eventName'])) {
                                    $this->sendPlunkEmail($metadata, $personalData);
                                }
                            }
                        }

                    } catch (\Exception $e) {
                        $this->logger->error('Error processing payment intent', [
                            'error' => $e->getMessage(),
                            'metadata' => $metadata,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                    break;

                case 'payment_intent.canceled':
                    $paymentIntent = $event->data->object;
                    $this->logger->warning('Payment Intent canceled', [
                        'payment_intent_id' => $paymentIntent->id,
                        'cancellation_reason' => $paymentIntent->cancellation_reason,
                        'last_payment_error' => $paymentIntent->last_payment_error
                    ]);

                    try {
                        // Finde zugehörige Rechnungen
                        $invoices = \Stripe\Invoice::all([
                            'customer' => $paymentIntent->customer,
                            'metadata' => ['payment_intent_id' => $paymentIntent->id]
                        ]);

                        foreach ($invoices->data as $invoice) {
                            if ($invoice->status !== 'void') {
                                $invoice->void_invoice();
                                $this->logger->info('Invoice voided', [
                                    'invoice_id' => $invoice->id,
                                    'payment_intent_id' => $paymentIntent->id
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Error voiding invoice', [
                            'error' => $e->getMessage(),
                            'payment_intent_id' => $paymentIntent->id
                        ]);
                    }
                    break;
            }

            return new Response('Webhook processed', Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('Webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new Response('Webhook error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function createContaoUser(array $metadata): void
    {
        $this->framework->initialize();

        try {
            $container = System::getContainer();
            $logger = $container->get('monolog.logger.contao');

            $personalData = json_decode($metadata['personalData'], true);
            $productData = json_decode($metadata['productData'], true);

            // Verwende duration statt subscription_duration
            $duration = isset($productData['duration']) ? (int)$productData['duration'] : 0;

            // Start ist jetzt
            $startTime = time();

            // Berechne stopTime
            if ($duration > 0) {
                $stopTime = strtotime("+{$duration} months", $startTime);
            } else {
                $stopTime = 0;
            }

            $logger->error('Before saving member', [
                'context' => ContaoContext::GENERAL,
                'username' => $personalData['username'],
                'start' => date('Y-m-d H:i:s', $startTime),
                'stop' => $stopTime ? date('Y-m-d H:i:s', $stopTime) : 'not set',
                'duration' => $duration  // Geändert von subscription_duration
            ]);
            // Neuen Benutzer anlegen
            $member = new MemberModel();
            $member->tstamp = time();
            $member->dateAdded = time();
            $member->username = $personalData['username'];
            $member->password = password_hash(base64_decode($personalData['password']), PASSWORD_DEFAULT);
            $member->email = $personalData['email'];
            $member->firstname = $personalData['firstname'] ?? '';
            $member->lastname = $personalData['lastname'] ?? '';
            $member->gender = $this->mapSalutation($personalData['salutation'] ?? '');
            $member->street = $personalData['street'] ?? '';
            $member->postal = $personalData['postal'] ?? '';
            $member->city = $personalData['city'] ?? '';
            $member->phone = $personalData['phone'] ?? '';
            $member->company = $personalData['company'] ?? '';
            $member->login = true;
            $member->start = $startTime;
            $member->stop = $stopTime;
            $member->dateEnd = $stopTime ? date('Y-m-d', $stopTime) : null; // Für die E-Mail
            $member->groups = serialize([(int)$metadata['memberGroup']]);

            $member->save();

            // Nach erfolgreicher Speicherung E-Mail und Plunk-Event senden
            try {
                $this->sendRegistrationEmail($member);
                $logger->info('Registration email sent successfully', [
                    'context' => ContaoContext::GENERAL,
                    'username' => $member->username,
                    'email' => $member->email
                ]);

            } catch (\Exception $e) {
                $logger->error('Failed to send registration email or Plunk event', [
                    'context' => ContaoContext::GENERAL,
                    'username' => $member->username,
                    'error' => $e->getMessage()
                ]);
            }

            $logger->error('User created successfully', [
                'context' => ContaoContext::GENERAL,
                'username' => $member->username
            ]);

        } catch (\Exception $e) {
            $logger->error('Error in user creation', [
                'context' => ContaoContext::GENERAL,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function mapSalutation(string $salutation): string
    {
        return match ($salutation) {
            'Herr' => 'male',
            'Frau' => 'female',
            default => 'other'
        };
    }

    private function sendRegistrationEmail(MemberModel $member): void
    {
        try {
            $container = System::getContainer();
            $logger = $container->get('monolog.logger.contao');

            // Email-Konfiguration aus der config.yml holen
            $config = $container->get('contao.framework')->getAdapter(\Contao\Config::class);
            $emailFrom = $config->get('adminEmail');

            if (empty($emailFrom)) {
                throw new \Exception('No admin email configured in Contao settings');
            }

            $email = new \Contao\Email();
            $email->from = $emailFrom;
            $email->fromName = $config->get('websiteTitle') ?: 'Ihre Website';
            $email->subject = 'Ihre Registrierung wurde erfolgreich abgeschlossen';

            // Debug-Log der Email-Konfiguration
            $logger->info('Email configuration', [
                'context' => ContaoContext::GENERAL,
                'from' => $emailFrom,
                'fromName' => $email->fromName
            ]);

            // HTML-Email Template
            $html = sprintf(
                '<h2>Willkommen bei uns!</h2>
            <p>Sehr geehrte(r) %s %s,</p>
            <p>Ihr Benutzerkonto wurde erfolgreich angelegt.</p>
            <p>Ihre Zugangsdaten:</p>
            <ul>
                <li>Benutzername: %s</li>
                <li>E-Mail: %s</li>
            </ul>
            <p>Sie können sich nun mit Ihrem Benutzernamen und Passwort einloggen.</p>',
                $member->firstname,
                $member->lastname,
                $member->username,
                $member->email
            );

            // Überprüfen Sie, ob stop ein Integer ist und größer als 0
            if ($member->stop && is_numeric($member->stop) && (int)$member->stop > 0) {
                $stopTimestamp = (int)$member->stop;
                $html .= sprintf(
                    '<p>Ihr Zugang ist gültig bis zum %s.</p>',
                    date('d.m.Y', $stopTimestamp)
                );
            }

            $html .= '<p>Mit freundlichen Grüßen<br>Ihr Team</p>';

            $email->html = $html;
            $email->text = strip_tags($html);

            // Debug-Log vor dem Senden
            $logger->info('Attempting to send registration email', [
                'context' => ContaoContext::GENERAL,
                'to' => $member->email,
                'from' => $email->from,
                'subject' => $email->subject,
                'stop_time' => $member->stop
            ]);

            $email->sendTo($member->email);

            $logger->info('Email sent successfully', [
                'context' => ContaoContext::GENERAL,
                'to' => $member->email
            ]);

        } catch (\Exception $e) {
            $logger->error('Error sending registration email', [
                'context' => ContaoContext::GENERAL,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    #[Route('/create-payment-intent', name: 'stripe_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $personalData = $data['personalData'];
            $productData = $data['productData'];

            if (empty($data['amount']) || !is_numeric($data['amount'])) {
                throw new \Exception('Invalid amount');
            }

            \Stripe\Stripe::setApiKey($this->stripeSecretKey);

            // Kunde erstellen oder aktualisieren
            $customer = $this->getOrCreateCustomer($personalData);

            // PaymentIntent erstellen
// PaymentIntent erstellen
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'customer' => $customer->id,
                'automatic_payment_methods' => [
                    'enabled' => true
                ],
                // Entferne payment_method_types, da wir automatic_payment_methods nutzen
                'metadata' => [
                    'productId' => $data['productId'],
                    'productTitle' => $data['productTitle'],
                    'elementId' => $data['elementId'],
                    'personalData' => json_encode($personalData),
                    'createUser' => $data['createUser'] ? 'true' : 'false',
                    'memberGroup' => (string)($data['memberGroup'] ?? ''),
                    'productData' => json_encode([
                        'eventName' => $productData['eventName'] ?? null,
                        'duration' => $productData['duration'] ?? 0
                    ])
                ]
            ]);
            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Payment Intent Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function getOrCreateCustomer(array $personalData): \Stripe\Customer
    {
        try {
            // Suche nach bestehendem Kunden per E-Mail
            $customers = \Stripe\Customer::all([
                'email' => $personalData['email'],
                'limit' => 1
            ]);

            if (!empty($customers->data)) {
                $customer = $customers->data[0];
                // Update der Kundendaten für die Rechnung
                $customer->update($customer->id, [
                    'address' => [
                        'line1' => $personalData['street'] ?? null,
                        'postal_code' => $personalData['postal'] ?? null,
                        'city' => $personalData['city'] ?? null,
                        'country' => $personalData['country'] ?? 'DE',
                    ],
                    'name' => $personalData['firstname'] . ' ' . $personalData['lastname'],
                    'phone' => $personalData['phone'] ?? null,
                    'metadata' => [
                        'company' => $personalData['company'] ?? null,
                        'vat_id' => $personalData['vat_id'] ?? null // Falls benötigt
                    ]
                ]);
                return $customer;
            }

            // Neuen Kunden erstellen
            return \Stripe\Customer::create([
                'email' => $personalData['email'],
                'name' => $personalData['firstname'] . ' ' . $personalData['lastname'],
                'phone' => $personalData['phone'] ?? null,
                'address' => [
                    'line1' => $personalData['street'] ?? null,
                    'postal_code' => $personalData['postal'] ?? null,
                    'city' => $personalData['city'] ?? null,
                    'country' => $personalData['country'] ?? 'DE',
                ],
                'metadata' => [
                    'company' => $personalData['company'] ?? null,
                    'vat_id' => $personalData['vat_id'] ?? null // Falls benötigt
                ],
                'invoice_settings' => [
                    'default_payment_method' => null, // Wird später gesetzt
                    'custom_fields' => null,
                    'footer' => null,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error creating/updating customer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    #[Route('/get-price', name: 'stripe_get_price', methods: ['POST'])]
    public function getPrice(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }

            // Validierung der Eingabedaten
            if (empty($data['amount']) || !is_numeric($data['amount'])) {
                throw new \Exception('Invalid amount');
            }
            if (empty($data['currency']) || !in_array(strtolower($data['currency']), ['eur', 'usd', 'gbp'])) {
                throw new \Exception('Invalid currency');
            }

            \Stripe\Stripe::setApiKey($this->stripeSecretKey);

            // Liste alle aktiven Preise
            $prices = \Stripe\Price::all([
                'active' => true,
                'type' => 'one_time',
                'currency' => strtolower($data['currency'])
            ]);

            // Suche nach passendem Preis
            foreach ($prices->data as $price) {
                if ($price->unit_amount == $data['amount']) {
                    return new JsonResponse(['priceId' => $price->id]);
                }
            }

            // Wenn kein passender Preis gefunden wurde, erstelle einen neuen
            $price = \Stripe\Price::create([
                'unit_amount' => (int)$data['amount'],
                'currency' => strtolower($data['currency']),
                'product_data' => [
                    'name' => 'One-time purchase'
                ]
            ]);

            return new JsonResponse(['priceId' => $price->id]);

        } catch (\Exception $e) {
            $this->logger->error('Price creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/create-setup-intent', name: 'stripe_create_setup_intent', methods: ['POST'])]
    public function createSetupIntent(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            \Stripe\Stripe::setApiKey($this->stripeSecretKey);
            \Stripe\Stripe::setApiVersion('2023-10-16');

            // Kunde erstellen oder abrufen
            $customer = $this->getOrCreateCustomer($data['personalData']);

            // Setup Intent erstellen
            $setupIntent = \Stripe\SetupIntent::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'product_id' => $data['productId'],
                    'product_title' => $data['productTitle'],
                    'element_id' => $data['elementId'],
                    'price_id' => $data['priceId']
                ]
            ]);

            // Nach erfolgreicher Bestätigung des SetupIntent wird die Subscription erstellt
            $this->session->set('pending_subscription', [
                'customer_id' => $customer->id,
                'price_id' => $data['priceId'],
                'product_id' => $data['productId'],
                'product_title' => $data['productTitle'],
                'element_id' => $data['elementId']
            ]);

            return new JsonResponse([
                'clientSecret' => $setupIntent->client_secret
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Setup intent creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }


    private function handleSubscriptionPayment($paymentIntent, string $customerEmail): void
    {
        $this->framework->initialize();
        $container = System::getContainer();
        $logger = $container->get('monolog.logger.contao');

        try {
            $member = MemberModel::findByEmail($customerEmail);
            if (!$member) {
                throw new \Exception('No member found for email: ' . $customerEmail);
            }

            $metadata = $paymentIntent->metadata ?? null;
            if (!$metadata) {
                throw new \Exception('No metadata found in payment intent');
            }

            $productData = !empty($metadata['productData']) ?
                json_decode($metadata['productData'], true) : [];

            // Dauer aus productData holen
            $duration = isset($productData['duration']) ? (int)$productData['duration'] : 0;

            // Wenn keine Dauer angegeben ist, keine Verlängerung durchführen
            if ($duration <= 0) {
                $logger->info('No duration specified, skipping subscription update');
                return;
            }

            $startTime = time();
            $currentEnd = null;

            if ($member->stop && is_numeric($member->stop) && (int)$member->stop > time()) {
                $currentEnd = (int)$member->stop;
                $newEnd = strtotime("+{$duration} months", $currentEnd);
            } else {
                $newEnd = strtotime("+{$duration} months", $startTime);
            }

            $member->disable = 0;
            $member->start = $startTime;
            $member->stop = $newEnd;
            $member->dateEnd = date('Y-m-d', $newEnd);

            if (empty($member->groups) && !empty($metadata['memberGroup'])) {
                $member->groups = serialize([(int)$metadata['memberGroup']]);
            }

            $member->save();

            $logger->info('Membership updated successfully', [
                'email' => $member->email,
                'validUntil' => date('Y-m-d', $newEnd),
                'duration' => $duration . ' months'
            ]);

        } catch (\Exception $e) {
            $logger->error('Error handling payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    private function sendPlunkEmail(array $metadata, array $personalData): void
    {
        try {
            if (empty($metadata['productData'])) {
                return;
            }

            $productData = is_string($metadata['productData']) ?
                json_decode($metadata['productData'], true) : $metadata['productData'];

            if (empty($productData['eventName'])) {
                return;
            }

            $emailAddress = $personalData['email'];

            $eventData = [
                'firstName' => strval($personalData['firstname'] ?? ''),
                'lastName' => strval($personalData['lastname'] ?? ''),
                'productTitle' => strval($metadata['productTitle'] ?? 'Unbekannt'),
                'username' => strval($personalData['username'] ?? ''),
                'validUntil' => date('Y-m-d H:i:s'),
                'purchaseDate' => date('Y-m-d H:i:s'),
                'company' => strval($personalData['company'] ?? ''),
                'phone' => strval($personalData['phone'] ?? ''),
                'productId' => strval($metadata['productId'] ?? ''),
                'amount' => isset($metadata['amount']) ? strval($metadata['amount']) : '',
                'currency' => isset($metadata['currency']) ? strval($metadata['currency']) : 'EUR'
            ];

            $success = $this->plunkService->trackEvent($productData['eventName'], [
                'email' => $emailAddress,
                'data' => $eventData
            ]);

            if (!$success) {
                throw new \Exception('Failed to track Plunk event');
            }

        } catch (\Exception $e) {
            $this->logger->error('Error in sendPlunkEmail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}