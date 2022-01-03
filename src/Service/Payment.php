<?php

namespace App\Service;
use App\Entity\Promise;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class Payment
{
    // Si besoin modifier la clé LIVE
    const API_LIVE_TOKEN = "Pm-yzjMesg9KCqZihdQ6lWDHKdJowlFWayDKWX85WasbOSCNqYi4SMbAgBA5jsCW_hvRBTlDUiH9TbMZ0iBqYPOmxhxzLOmf6AxA60hQxHcU5J_8iH2uUip672wA1arE4lR7qI2JW3_lR6JbX2tyc2EQIkLffDiuniHQLW4Xm6h-GGC8JgyGx3pSki7CaDyBANOgGQ9th_mzBGInoANODTTONVPumg6gmbOK4nT8HT9vsSxDrssaffhWdY8ToCciLg4ywKLPtFN3T9J7mvGIt5Vgxcd1X7bdlxfImzw_dZ9f1TfNSrWFXZQMbb2mCND6kLpREKHsjkpxhyJ6SxkZhQ1w0Xe-T6elwWQ3kdH8cj9sUpTpz4yj2W9-uLdE08oCu8jQXv0PfMiqMs_AfhAzMv_Ux9AloOz9rZ4H61oMz1dbG-rgntLtAhuCbxyOzm7waSdtLvOKxsUXq5ToFj5tkO8PvN6DfMTpV53HWkyCG2BhjV2oz4UuMBOp2-WDyvY3xalEV1Y83_ihsBQa0e_32wOy5ceWI00WJ7oqK9SBJT6mbzQ7JxFExdGpatodzS59cFXC7zyUqzyWJByfGtixlERX4mFRqbQLwkhPas2P0GNfrwhySSBKc7lJKDgfHDor2OsERQxCknnvxySrktAoYAGSgtGqAwLhbKYirwZfc3O5-acLsMvoEH7UToMJqH_fQFNavU5PcC-0PM0L5vDOuMnMlDk";
    const API_TEST_TOKEN = "rLtt6JWvbUHDDhsZnfpAhpYk4dxYDQkbcPTyGaKp2TYqQgG7FGZ5Th_WD53Oq8Ebz6A53njUoo1w3pjU1D4vs_ZMqFiz_j0urb_BH9Oq9VZoKFoJEDAbRZepGcQanImyYrry7Kt6MnMdgfG5jn4HngWoRdKduNNyP4kzcp3mRv7x00ahkm9LAK7ZRieg7k1PDAnBIOG3EyVSJ5kK4WLMvYr7sCwHbHcu4A5WwelxYK0GMJy37bNAarSJDFQsJ2ZvJjvMDmfWwDVFEVe_5tOomfVNt6bOg9mexbGjMrnHBnKnZR1vQbBtQieDlQepzTZMuQrSuKn-t5XZM7V6fCW7oP-uXGX-sMOajeX65JOf6XVpk29DP6ro8WTAflCDANC193yof8-f5_EYY-3hXhJj7RBXmizDpneEQDSaSz5sFk0sV5qPcARJ9zGG73vuGFyenjPPmtDtXtpx35A-BVcOSBYVIWe9kndG3nclfefjKEuZ3m4jL9Gg1h2JBvmXSMYiZtp9MR5I6pvbvylU_PP5xJFSjVTIz7IQSjcVGO41npnwIxRXNRxFOdIUHn0tjQ-7LwvEcTXyPsHXcMD8WtgBh-wxR8aKX7WPSsT1O8d8reb2aR7K3rkV3K82K_0OgawImEpwSvp9MNKynEAJQS6ZHe_J_l77652xwPNxMRTMASk1ZsJL";
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