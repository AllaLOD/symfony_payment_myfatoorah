<?php

namespace App\Service;
use App\Entity\Promise;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class Payment
{
    // Si besoin modifier la clé LIVE
    const API_LIVE_TOKEN = "...";
    const API_TEST_TOKEN = "...";
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param User $user
     * @param $amount
     * @param string $currency
     * @return RedirectResponse
     */
    public function pay(User $user, Promise $promise, string $locale, string $currency = 'SAR'): RedirectResponse {

        $env = strtolower($_ENV['APP_ENV']);

        // Tester l'env si c'est dev ou prod
        if ($env === 'dev') {
            $mfPayment = new PaymentMyfatoorahApiV2(self::API_TEST_TOKEN, true);
        }
        else {
            $mfPayment = new PaymentMyfatoorahApiV2(self::API_TEST_TOKEN, true);
            /* A remplacer lors de la validation de compte en PROD
                $mfPayment = new PaymentMyfatoorahApiV2(self::API_LIVE_TOKEN, false)
            */
        }

        // Configuration de paiement
        $fullName = ucfirst($user->getFirst()) . ' ' . strtoupper($user->getLast());
        $postFields = [
            'NotificationOption' => 'Lnk',
            'InvoiceValue'       => $promise->getAmount(),
            'DisplayCurrencyIso' => 'SAR',
            'Language' => strtoupper($locale), //EN, AR
            'CustomerName' => $fullName,
            'CustomerEmail' => $user->getEmail(),
            'CustomerMobile' => $user->getPhone(),
            'CallBackUrl' => $this->urlGenerator->generate('adhesion', [
                'id' => $promise->getId() //, '_locale' => $locale
                ], UrlGeneratorInterface::ABSOLUTE_URL) . '?paymentStatus=success',
            'ErrorUrl' => $this->urlGenerator->generate('adhesion', [
                'id' => $promise->getId() //, '_locale' => $locale
                ], UrlGeneratorInterface::ABSOLUTE_URL) . '?paymentStatus=failure',
        ];

        
        $data = $mfPayment->getInvoiceURL($postFields);
        $invoiceId   = $data['invoiceId'];
        
        // Le lien de paiement vers lequel on sera redirigé
        $paymentLink = $data['invoiceURL'];

        // Redirection vers la page de paiement
        return new RedirectResponse($paymentLink);
    }
}
